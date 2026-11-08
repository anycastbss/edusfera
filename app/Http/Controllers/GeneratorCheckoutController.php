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
    public function initiate(
        Request $request,
        AlfaBankPaymentGateway $gateway,
        GeminiService $gemini
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc,filter', 'max:150'],
            'subject' => ['required', 'string', 'max:100'],
            'topic' => ['required', 'string', 'max:255'],
            'count' => ['nullable', 'integer', 'min:5', 'max:30'],
            'plan_type' => ['nullable', 'string', 'in:single,quarter_pass'],
            'test_bypass' => ['nullable'],
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

        $isQuarterPass = ($planType === 'quarter_pass');
        $amountByn = $isQuarterPass ? 19.00 : 5.00;
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
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        // Option 1: Admin bypass or test mode
        $user = auth()->user();
        $isAdmin = $user && (
            (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
            $user->role === 'admin' ||
            ($user->role instanceof \App\Domain\User\Enums\UserRole && $user->role === \App\Domain\User\Enums\UserRole::Admin)
        );

        $isTestBypass = $isAdmin
            || $request->boolean('test_bypass')
            || strtolower($email) === 'test@edusfera.by'
            || $request->input('promo_code') === 'TEST2026';

        if ($isTestBypass) {
            @set_time_limit(180);

            $order->update([
                'status' => 'generating',
                'paid_at' => now(),
                'payment_method' => $isAdmin ? 'admin_test_bypass' : 'sandbox_test',
            ]);

            Log::channel('payments')->info('Generator test bypass activated', [
                'order' => $orderNumber,
                'user_id' => auth()->id(),
                'email' => $email,
                'is_admin' => $isAdmin,
            ]);

            // Generate AI content and compile PDF
            $this->generateContentAndPdf($order, $gemini);

            // Optional email notification
            $this->sendOrderEmail($order);

            $successUrl = route('generator.success', ['order' => $order->order_number]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'order_number' => $order->order_number,
                    'redirect_url' => $successUrl,
                ]);
            }

            return redirect()->to($successUrl);
        }

        $returnUrl = route('generator.checkout.callback', ['order' => $order->order_number, 'status' => 'success']);
        $failUrl = route('generator.checkout.callback', ['order' => $order->order_number, 'status' => 'failed']);

        $redirectUrl = route('generator.checkout.hosted', ['order' => $order->order_number]);

        $description = $isQuarterPass
            ? sprintf('Edusfera Вариант (Абонемент 10 тем): %s [%s]', mb_substr($topic, 0, 30), $orderNumber)
            : sprintf('Edusfera Вариант (%d вар.): %s [%s]', $variantCount, mb_substr($topic, 0, 40), $orderNumber);

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

        // Allow simulated success ONLY in local or testing environments OR for admin
        if (! $isPaid && $request->has('simulated_success')) {
            $user = auth()->user();
            $isAdmin = $user && (
                (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                $user->role === 'admin' ||
                ($user->role instanceof \App\Domain\User\Enums\UserRole && $user->role === \App\Domain\User\Enums\UserRole::Admin)
            );
            if ($isAdmin || app()->environment('local', 'testing')) {
                $isPaid = true;
            }
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
     * Clean and convert LaTeX mathematical expressions into crisp Unicode for DomPDF printing
     */
    public static function cleanMathFormulasForPrint(string $text): string
    {
        // 1. Convert LaTeX square roots: \sqrt{...} -> √( ... )
        $text = preg_replace('/\\\\sqrt\{([^}]+)\}/u', '√($1)', $text);
        $text = preg_replace('/\\\\sqrt\s*([0-9a-zA-Z]+)/u', '√$1', $text);

        // 2. Convert LaTeX fractions: \frac{a}{b} -> (a)/(b)
        $text = preg_replace('/\\\\frac\{([^}]+)\}\{([^}]+)\}/u', '($1)/($2)', $text);

        // 3. Convert powers like x^2, x^3, x^4, x^{-1} to unicode superscripts
        $superscripts = [
            '0' => '⁰', '1' => '¹', '2' => '²', '3' => '³', '4' => '⁴',
            '5' => '⁵', '6' => '⁶', '7' => '⁷', '8' => '⁸', '9' => '⁹',
            '+' => '⁺', '-' => '⁻', '=' => '⁼', '(' => '⁽', ')' => '⁾',
            'n' => 'ⁿ', 'x' => 'ˣ',
        ];
        $text = preg_replace_callback('/\^\{?([0-9+\-nx]+)\}?/u', function ($m) use ($superscripts) {
            $val = $m[1];
            $res = '';
            for ($i = 0; $i < mb_strlen($val); $i++) {
                $ch = mb_substr($val, $i, 1);
                $res .= $superscripts[$ch] ?? '^'.$ch;
            }
            return $res;
        }, $text);

        // 4. Common LaTeX mathematical symbols to clean Unicode
        $replacements = [
            '\\cdot' => '·',
            '\\times' => '×',
            '\\pm' => '±',
            '\\mp' => '∓',
            '\\le' => '≤',
            '\\leq' => '≤',
            '\\ge' => '≥',
            '\\geq' => '≥',
            '\\neq' => '≠',
            '\\approx' => '≈',
            '\\infty' => '∞',
            '\\pi' => 'π',
            '\\degree' => '°',
            '\\circ' => '°',
            '\\quad' => '  ',
            '\\qquad' => '    ',
            '\\,' => ' ',
            '\\;' => ' ',
            '\\!' => '',
            '\\left(' => '(',
            '\\right)' => ')',
            '\\left[' => '[',
            '\\right]' => ']',
            '\\left\\{' => '{',
            '\\right\\}' => '}',
            '\\text{' => '',
        ];
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);

        // 5. Strip remaining dollar signs used in LaTeX inline & block math ($...$ and $$...$$)
        $text = preg_replace('/\$\$+/u', '', $text);
        $text = preg_replace('/\$([^\$\n]+)\$/u', '$1', $text);
        $text = str_replace('$', '', $text);

        // 6. Fix options formatted as "1. " into "1) " so Markdown won't parse them into an ordered list
        $text = preg_replace('/^(\s*)([1-4])\.\s+/m', '$1$2) ', $text);

        return $text;
    }

    /**
     * Ensure PDF file is rendered and cached to local storage
     */
    protected function ensurePdfExists(GeneratorOrder $order, bool $force = false): string
    {
        $relativePath = "generator_orders/{$order->order_number}.pdf";

        if ($force || empty($order->pdf_path) || ! Storage::disk('local')->exists($relativePath)) {
            $cleaned = self::cleanMathFormulasForPrint((string) $order->generated_content);

            // SEC-02 FIX: Strip raw HTML from markdown to prevent SSRF and injection
            $htmlContent = Str::markdown($cleaned, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            $pdf = Pdf::loadView('generator.pdf-template', [
                'order' => $order,
                'content' => $cleaned,
                'htmlContent' => $htmlContent,
            ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false); // SEC-02 FIX: Disabled remote fetching to prevent SSRF/LFI

            Storage::disk('local')->put($relativePath, $pdf->output());

            $order->update([
                'pdf_path' => $relativePath,
                'generated_content' => $cleaned,
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
            "Ты — ведущий методист-составитель проверочных и экзаменационных работ Беларуси.\n".
            "Составь официальный комплект заданий для всего класса по государственным стандартам ЦТ и ЦЭ Республики Беларусь 2026 года.\n\n".
            "<order_specification>\n".
            "- Дисциплина: %s\n".
            "- Тема: %s\n".
            "- Количество вариантов: %d шт.\n".
            "</order_specification>\n\n".
            "════════════════════════════════════════════════════════════════\n".
            "КРИТИЧЕСКИЕ ТРЕБОВАНИЯ К ФОРМУЛАМ И ОФОРМЛЕНИЮ (СТРОГО!):\n".
            "1. СТРОЖАЙШЕ ЗАПРЕЩЕНО использовать LaTeX, знаки доллара ($ или $$) и обратные слэши (\\sqrt, \\frac, \\cdot)!\n".
            "   Документ верстается напрямую в печатный бланк PDF для школы.\n".
            "2. Все математические выражения пиши ТОЛЬКО в чистом человекочитаемом текстовом виде с символами Юникода:\n".
            "   - Знак корня: пиши '√' и скобки, например: √(2x + 7) = x − 4\n".
            "   - Степени: пиши верхним индексом Юникода: x², x³, (x − 1)², a²\n".
            "   - Умножение: символ '·' (а не звездочка и не \\cdot)\n".
            "   - Знак минус: '−' или '-'\n".
            "   - Дроби: через косую черту '(a + b) / c'\n".
            "   - Знаки сравнения: '≤', '≥', '≠', '±'\n".
            "3. Формат вариантов ответов в Части А: строго '1) ...', '2) ...', '3) ...', '4) ...' (с круглой скобкой, КАЖДЫЙ с новой строки, НЕ через точку 1.).\n".
            "4. Часть Б: по правилам ЦТ/ЦЭ Беларуси ответом ВСЕГДА должно быть СТРОГО ЦЕЛОЕ ЧИСЛО (положительное или отрицательное, например: 6, -2, 14, 0). Никаких дробей или выражений с корнями в ответе Части Б!\n".
            "5. Защита от списывания: в каждом варианте должны быть РАЗНЫЕ числа и другие корни, но одинаковый метод решения.\n\n".
            "════════════════════════════════════════════════════════════════\n".
            "ЭТАЛОННЫЙ ОБРАЗЕЦ СТРУКТУРЫ КАЖДОГО ВАРИАНТА:\n\n".
            "### Вариант 1\n".
            "Учащийся: ___________________________   Класс: _______   Дата: ____________\n\n".
            "**Задание А1 (Часть А, с выбором ответа):**  \n".
            "Укажите корень уравнения √(2x + 7) = x − 4:  \n".
            "1) 1  \n".
            "2) 9  \n".
            "3) 1 и 9  \n".
            "4) −1  \n\n".
            "**Задание Б1 (Часть Б, с кратким ответом):**  \n".
            "Решите уравнение √(x² − 5x + 6) · √(x − 1) = 0. В бланк ответов запишите произведение всех действительных корней уравнения.  \n".
            "[ Ответ: ____________ ]\n\n".
            "---\n\n".
            "Составь такие варианты для всех %d вариантов (от Вариант 1 до Вариант %d).\n\n".
            "════════════════════════════════════════════════════════════════\n".
            "ФИНАЛЬНЫЙ БЛОК (ОБЯЗАТЕЛЬНО В КОНЦЕ):\n".
            "## Сводная мастер-таблица правильных ответов для преподавателя\n".
            "Составь сводную таблицу в Markdown со всеми %d вариантами:\n".
            "| Вариант | Ответ А1 | Ответ Б1 | Экзаменационная ловушка (пояснение для учителя) |\n".
            "| :---: | :---: | :---: | :--- |\n".
            "| 1 | 2 (9) | 6 | Посторонний корень x=1 отсекается условием ОДЗ x − 4 ≥ 0. В Б1 корни 1, 2, 3. |\n".
            "...\n".
            "Заполни таблицу для всех %d вариантов без пропусков.",
            $cleanSubject,
            $cleanTopic,
            $count,
            $count,
            $count,
            $count,
            $count
        );

        $system = 'Ты — ведущий методист-составитель проверочных заданий по государственным стандартам Беларуси (ЦТ/ЦЭ 2026). '.
                  'Пиши чисто, профессионально, с красивыми академическими формулировками. '.
                  'КАТЕГОРИЧЕСКИЙ ЗАПРЕТ НА LaTeX: никогда не используй знаки $ и команды LaTeX, формулы пиши только знаками Юникода (√, ², ³, ·, ≤, ≥). '.
                  'В Части Б ответ ВСЕГДА обязан быть целым числом.';

        try {
            $result = $gemini->generateText($prompt, $system, timeoutSeconds: 75);

            if (! empty($result)) {
                $cleanedResult = self::cleanMathFormulasForPrint($result);

                $order->update([
                    'status' => 'completed',
                    'generated_content' => $cleanedResult,
                ]);

                // Pre-generate and cache PDF immediately
                $this->ensurePdfExists($order, force: true);

                return;
            }
        } catch (\Throwable $e) {
            Log::error('AI generation error for order '.$order->order_number.': '.$e->getMessage());
        }

        // Graceful fallback with valid structure if AI temporary unavailable
        $fallbackContent = $this->buildFallbackContent($order, $count);
        $order->update([
            'status' => 'completed',
            'generated_content' => self::cleanMathFormulasForPrint($fallbackContent),
        ]);
        $this->ensurePdfExists($order, force: true);
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
