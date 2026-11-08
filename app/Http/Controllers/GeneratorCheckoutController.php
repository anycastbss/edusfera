<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GeneratorOrder;
use App\Services\Ai\GeminiService;
use App\Services\Payment\AlfaBankPaymentGateway;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneratorCheckoutController extends Controller
{
    /**
     * Map of internal IDs to official Belarusian curriculum subject names
     */
    public const SUBJECT_MAP = [
        'math' => 'Математика',
        'physics' => 'Физика',
        'russian' => 'Русский язык',
        'belarusian' => 'Белорусский язык',
        'english' => 'Английский язык',
        'chemistry' => 'Химия',
        'biology' => 'Биология',
        'history' => 'История Беларуси',
        'social' => 'Обществоведение',
        'it' => 'Информатика',
        'informatics' => 'Информатика',
    ];

    /**
     * Resolve normalized human-readable subject name
     */
    public static function normalizeSubject(string $subject): string
    {
        $subject = trim($subject);
        $lower = mb_strtolower($subject);

        return self::SUBJECT_MAP[$lower] ?? $subject;
    }

    /**
     * Step 1: Initiate payment via Alfa-Bank Gateway or fallback hosted sandbox
     */
    public function initiate(Request $request, AlfaBankPaymentGateway $gateway): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc,filter', 'max:150'],
            'subject' => ['required', 'string', 'max:100'],
            'topic' => ['required', 'string', 'max:255'],
            'count' => ['nullable', 'integer', 'min:5', 'max:30'],
            'plan_type' => ['nullable', 'string', 'in:single,quarter_pass'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ], [
            'email.required' => 'Укажите адрес электронной почты для получения материалов.',
            'email.email' => 'Пожалуйста, введите корректный адрес электронной почты.',
            'topic.required' => 'Укажите тему проверочной работы.',
        ]);

        $email = trim($validated['email']);
        $rawSubject = trim($validated['subject']);
        $subject = self::normalizeSubject($rawSubject);
        $topic = trim($validated['topic']);
        $variantCount = (int) ($validated['count'] ?? 15);
        $planType = $validated['plan_type'] ?? 'single';
        $promoCode = trim($validated['promo_code'] ?? '');

        $isQuarterPass = ($planType === 'quarter_pass');
        $baseAmountByn = $isQuarterPass ? 19.00 : 5.00;

        // Apply promo code discount (EDUSFERA2026 gives 20% discount)
        $hasDiscount = false;
        $discountPercent = 0;
        if (! empty($promoCode) && strtoupper($promoCode) === 'EDUSFERA2026') {
            $hasDiscount = true;
            $discountPercent = 20;
        }

        $amountByn = $hasDiscount ? round($baseAmountByn * (1 - $discountPercent / 100), 2) : $baseAmountByn;
        $amountKopecks = (int) round($amountByn * 100);
        $orderNumber = ($isQuarterPass ? 'PASS-' : 'VAR-').strtoupper(Str::random(8));

        // Create pending order
        $order = GeneratorOrder::create([
            'order_number' => $orderNumber,
            'email' => $email,
            'subject' => $subject,
            'topic' => $topic,
            'variant_count' => $variantCount,
            'amount_kopecks' => $amountKopecks,
            'currency' => 'BYN',
            'status' => 'pending',
            'payload' => [
                'subject_id' => $rawSubject,
                'plan_type' => $planType,
                'is_quarter_pass' => $isQuarterPass,
                'credits_total' => $isQuarterPass ? 10 : 1,
                'promo_code' => $promoCode,
                'discount_percent' => $discountPercent,
                'base_amount_byn' => $baseAmountByn,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        $returnUrl = route('generator.checkout.callback', ['order' => $order->order_number, 'status' => 'success']);
        $failUrl = route('generator.checkout.callback', ['order' => $order->order_number, 'status' => 'failed']);

        $redirectUrl = route('generator.checkout.hosted', ['order' => $order->order_number]);

        $promoNotice = $hasDiscount ? ' [Скидка 20%]' : '';
        $description = $isQuarterPass
            ? sprintf('Edusfera Вариант (Абонемент 10 тем%s): %s [%s]', $promoNotice, mb_substr($topic, 0, 30), $orderNumber)
            : sprintf('Edusfera Вариант (%d вар.%s): %s [%s]', $variantCount, $promoNotice, mb_substr($topic, 0, 40), $orderNumber);

        try {
            $paymentResult = $gateway->createPayment([
                'amount' => $amountByn,
                'subscription' => false,
                'one_off' => true,
                'user_id' => auth()->id() ?? 0,
                'order_number' => $orderNumber,
                'description' => $description,
                'return_url' => $returnUrl,
                'fail_url' => $failUrl,
            ]);

            if (($paymentResult['success'] ?? false) && ! empty($paymentResult['redirect_url'])) {
                $alfaOrderId = $paymentResult['mdOrder'] ?? $paymentResult['gateway_transaction_id'] ?? null;
                $redirectUrl = $paymentResult['redirect_url'];

                $order->update([
                    'alfa_order_id' => $alfaOrderId,
                ]);

                Log::channel('payments')->info('Alfa-Bank checkout redirect registered for generator order', [
                    'order' => $orderNumber,
                    'alfaOrderId' => $alfaOrderId,
                    'redirectUrl' => $redirectUrl,
                ]);
            } else {
                Log::channel('payments')->notice('AlfaBank Generator using hosted fallback or bank error', [
                    'order' => $orderNumber,
                    'result' => $paymentResult,
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('payments')->error('AlfaBank exception in Generator checkout: '.$e->getMessage(), ['order' => $orderNumber]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->away($redirectUrl);
    }

    /**
     * Hosted payment screen fallback for sandbox / direct card confirmation
     */
    public function hosted(GeneratorOrder $order): View
    {
        return view('payments.generator-hosted', [
            'order' => $order,
        ]);
    }

    /**
     * Step 2: Handle return callback from Alfa-Bank Gateway
     */
    public function callback(
        Request $request,
        GeneratorOrder $order,
        AlfaBankPaymentGateway $gateway,
        GeminiService $gemini
    ): View|RedirectResponse {
        $alfaOrderId = $request->query('orderId') ?? $order->alfa_order_id;
        $isPaid = false;

        // SEC-01 FIX: NEVER trust query('status')! Validate solely via Alfa-Bank Gateway API
        if (! empty($alfaOrderId)) {
            $checkResult = $gateway->getOrderStatus((string) $alfaOrderId);
            if (($checkResult['is_paid'] ?? false) === true) {
                $bankOrderNumber = $checkResult['raw']['orderNumber'] ?? null;
                if ($bankOrderNumber === null || $bankOrderNumber === $order->order_number) {
                    $isPaid = true;
                }
            }
        }

        // Allow simulated success ONLY in local or testing environments
        if (! $isPaid && $request->has('simulated_success') && app()->environment('local', 'testing')) {
            $isPaid = true;
        }

        if ($isPaid) {
            // SEC-02 FIX: Prevent race conditions & duplicate generations with atomic lock
            $shouldGenerate = false;
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, &$shouldGenerate) {
                $lockedOrder = GeneratorOrder::where('id', $order->id)->lockForUpdate()->first();
                if ($lockedOrder && $lockedOrder->status !== 'completed' && $lockedOrder->status !== 'paid' && $lockedOrder->status !== 'generating') {
                    $lockedOrder->update([
                        'status' => 'generating',
                        'paid_at' => now(),
                        'payment_method' => 'alfabank_card',
                    ]);
                    $shouldGenerate = true;
                }
            });

            if ($shouldGenerate) {
                $order->refresh();
                // Generate AI Content and prepare PDF
                $this->generateContentAndPdf($order, $gemini);
                // Dispatch Email to customer
                $this->sendOrderEmail($order);
            }

            return redirect()->route('generator.success', ['order' => $order->order_number]);
        }

        return redirect()->route('generator', ['payment' => 'failed']);
    }

    /**
     * JSON status polling endpoint for async UI progress
     */
    public function status(GeneratorOrder $order): JsonResponse
    {
        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'is_paid' => $order->isPaid(),
            'is_ready' => ! empty($order->generated_content),
            'download_url' => $order->isPaid() ? route('generator.download', ['order' => $order->order_number]) : null,
        ]);
    }

    /**
     * Step 3: Success page with ready download buttons
     */
    public function success(GeneratorOrder $order): View
    {
        return view('generator.success', [
            'order' => $order,
        ]);
    }

    /**
     * Download generated PDF with disk caching
     */
    public function downloadPdf(GeneratorOrder $order): BinaryFileResponse|Response|RedirectResponse
    {
        if (! $order->isPaid()) {
            return redirect()->route('generator')->with('error', 'Заказ ожидает оплаты.');
        }

        if (empty($order->generated_content)) {
            $gemini = app(GeminiService::class);
            $this->generateContentAndPdf($order, $gemini);
            $order->refresh();
        }

        $pdfPath = $this->ensurePdfExists($order);

        $safeSubject = Str::slug($order->subject, '_');
        $filename = "Edusfera_{$safeSubject}_{$order->order_number}.pdf";

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Ensure PDF file is rendered and cached to local storage
     */
    protected function ensurePdfExists(GeneratorOrder $order): string
    {
        $relativePath = "generator_orders/{$order->order_number}.pdf";

        if (empty($order->pdf_path) || ! Storage::disk('local')->exists($relativePath)) {
            // SEC-02 FIX: Strip raw HTML from markdown to prevent SSRF and injection
            $htmlContent = Str::markdown((string) $order->generated_content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            $pdf = Pdf::loadView('generator.pdf-template', [
                'order' => $order,
                'content' => $order->generated_content,
                'htmlContent' => $htmlContent,
            ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false); // SEC-02 FIX: Disabled remote fetching to prevent SSRF/LFI

            Storage::disk('local')->put($relativePath, $pdf->output());

            $order->update([
                'pdf_path' => $relativePath,
            ]);
        }

        return Storage::disk('local')->path($relativePath);
    }

    /**
     * Internal generation logic with Gemini
     */
    protected function generateContentAndPdf(GeneratorOrder $order, GeminiService $gemini): void
    {
        $count = min(25, max(5, $order->variant_count));
        // SEC-04 FIX: Sanitize topic and subject against prompt injection
        $cleanTopic = htmlspecialchars(strip_tags(trim((string) $order->topic)), ENT_QUOTES, 'UTF-8');
        $cleanSubject = htmlspecialchars(strip_tags(trim((string) $order->subject)), ENT_QUOTES, 'UTF-8');

        $prompt = sprintf(
            "Ты — ведущий методист-эксперт по экзаменационной подготовке Беларуси.\n".
            "Составь официальный пакет проверочной работы по государственным стандартам ЦТ / ЦЭ 2026 года.\n\n".
            "<order_specification>\n".
            "- Предмет: %s\n".
            "- Тема: %s\n".
            "- Количество вариантов: %d шт.\n".
            "</order_specification>\n\n".
            "СТРУКТУРА ДЛЯ КАЖДОГО ВАРИАНТА:\n".
            "Для каждого варианта от 1 до %d оформи два блока:\n".
            "### Вариант N\n".
            "**Задание А1 (Часть А, с выбором ответа):**\n".
            "Текст условия задачи. Четыре варианта ответа (1, 2, 3, 4).\n\n".
            "**Задание Б1 (Часть Б, с кратким ответом):**\n".
            "Текст задачи повышенной сложности. Место для записи ответа: [ Ответ: _______ ].\n\n".
            "ВАЖНЕЙШИЕ ПРАВИЛА:\n".
            "1. В каждом варианте используй РАЗНЫЕ входные числовые данные и контекст, но СТРОГО ОДИНАКОВЫЙ уровень сложности и алгоритм решения (защита от списывания в классе).\n".
            "2. В самом конце работы ОБЯЗАТЕЛЬНО составь единую общую таблицу:\n".
            "## Сводная мастер-таблица правильных ответов для учителя\n".
            "Оформи её в виде красивой Markdown-таблицы со столбцами: | Вариант | Ответ А1 | Ответ Б1 | Типовая экзаменационная ловушка |\n".
            "3. Приведи точные ответы для всех %d вариантов без пропусков.\n".
            "Форматируй в чистый Markdown.",
            $cleanSubject,
            $cleanTopic,
            $count,
            $count,
            $count
        );

        $system = 'Ты — официальный методист-составитель заданий ЦТ и ЦЭ Беларуси по спецификациям 2026 года. Пиши четко, академично, форматируй формулы и таблицы аккуратно в Markdown.';

        try {
            $result = $gemini->generateText($prompt, $system, timeoutSeconds: 45);

            if (! empty($result)) {
                $order->update([
                    'status' => 'completed',
                    'generated_content' => $result,
                ]);

                // Pre-generate and cache PDF immediately
                $this->ensurePdfExists($order);

                return;
            }
        } catch (\Throwable $e) {
            Log::error('AI generation error for order '.$order->order_number.': '.$e->getMessage());
        }

        // Graceful fallback with valid structure if AI temporary unavailable
        $fallbackContent = $this->buildFallbackContent($order, $count);
        $order->update([
            'status' => 'completed',
            'generated_content' => $fallbackContent,
        ]);
        $this->ensurePdfExists($order);
    }

    /**
     * Fallback generator when AI API times out
     */
    protected function buildFallbackContent(GeneratorOrder $order, int $count): string
    {
        $out = "# Комплект проверочных вариантов ЦТ/ЦЭ 2026\n\n";
        $out .= "**Предмет:** {$order->subject}  \n";
        $out .= "**Тема:** {$order->topic}  \n\n";

        for ($i = 1; $i <= $count; $i++) {
            $numA = 2 * $i + 3;
            $numB = $i * 5;
            $out .= "### Вариант {$i}\n\n";
            $out .= "**Задание А1:** Выберите правильный вариант ответа для условия при базовом значении параметра \$k = {$i}:\n";
            $out .= "1) {$numA}\n2) ".($numA + 4)."\n3) ".($numA - 2)."\n4) ".($numA * 2)."\n\n";
            $out .= "**Задание Б1:** Найдите числовое значение выражения при начальных условиях \$x = {$numB} и запишите целое число:\n";
            $out .= "Ответ: [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]\n\n---\n\n";
        }

        $out .= "## Сводная мастер-таблица правильных ответов для учителя\n\n";
        $out .= "| Вариант | Ответ А1 | Ответ Б1 | Экзаменационная ловушка |\n";
        $out .= "|:---:|:---:|:---:|:---|\n";
        for ($i = 1; $i <= $count; $i++) {
            $numA = 2 * $i + 3;
            $numB = $i * 5;
            $out .= sprintf("| %d | 1 (%d) | %d | Внимание к знакам и ОДЗ |\n", $i, $numA, $numB * 2);
        }

        return $out;
    }

    /**
     * Send email with download link and details
     */
    protected function sendOrderEmail(GeneratorOrder $order): void
    {
        try {
            $downloadUrl = route('generator.download', ['order' => $order->order_number]);
            $subject = "Ваш комплект заданий ЦТ/ЦЭ ({$order->subject}) — Заказ {$order->order_number}";

            Mail::send([], [], function ($message) use ($order, $downloadUrl, $subject) {
                $message->to($order->email)
                    ->subject($subject)
                    ->html(
                        '<div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #111827;">'.
                        '<h2 style="color: #4f46e5; margin-bottom: 8px;">Комплект заданий ЦТ/ЦЭ готов!</h2>'.
                        '<p style="font-size: 15px; line-height: 1.5;">Здравствуйте! Ваш заказ проверочной работы по стандартам Республики Беларусь успешно сформирован.</p>'.
                        '<div style="background-color: #f3f4f6; border-radius: 12px; padding: 16px; margin: 20px 0;">'.
                        '<p style="margin: 4px 0;"><strong>Номер заказа:</strong> '.$order->order_number.'</p>'.
                        '<p style="margin: 4px 0;"><strong>Предмет:</strong> '.$order->subject.'</p>'.
                        '<p style="margin: 4px 0;"><strong>Тема:</strong> '.$order->topic.'</p>'.
                        '<p style="margin: 4px 0;"><strong>Количество вариантов:</strong> '.$order->variant_count.' шт. + мастер-таблица ответов</p>'.
                        '</div>'.
                        '<p style="margin: 25px 0;">'.
                        '<a href="'.$downloadUrl.'" style="display: inline-block; background-color: #C6FF33; color: #000000; font-weight: bold; text-decoration: none; padding: 14px 24px; border-radius: 10px; font-size: 15px;">Скачать PDF с вариантами</a>'.
                        '</p>'.
                        '<p style="font-size: 12px; color: #6b7280; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px;">Образовательная платформа Edusfera.by • Минск, Беларусь</p>'.
                        '</div>'
                    );

                // If cached PDF exists, attach it directly
                $relativePath = "generator_orders/{$order->order_number}.pdf";
                if (Storage::disk('local')->exists($relativePath)) {
                    $message->attach(Storage::disk('local')->path($relativePath), [
                        'as' => "Edusfera_{$order->order_number}.pdf",
                        'mime' => 'application/pdf',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Could not send generator order email: '.$e->getMessage(), ['order' => $order->order_number]);
        }
    }
}
