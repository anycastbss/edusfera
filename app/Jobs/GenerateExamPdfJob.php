<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Controllers\GeneratorCheckoutController;
use App\Models\GeneratorOrder;
use App\Services\Ai\GeminiService;
use App\Services\Exam\ExamDocumentParser;
use App\Services\Exam\ExamVerificationEngine;
use App\Services\Exam\NodeExamGeneratorService;
use App\Services\Exam\PedagogicalPromptEngine;
use App\Services\Exam\PythonExamGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateExamPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [3, 10, 25];

    public int $timeout = 120;

    public function __construct(
        public readonly GeneratorOrder $order
    ) {}

    public function handle(GeminiService $gemini): void
    {
        $order = $this->order->fresh();

        if (! $order) {
            return;
        }

        $lock = Cache::lock('generator:generation:' . $order->id, 300);

        if (! $lock->get()) {
            return;
        }

        try {
            if (! empty($order->generated_content) && ! empty($order->pdf_path) && Storage::disk('local')->exists($order->pdf_path)) {
                return;
            }

            $order->update([
                'status' => 'generating',
            ]);

            // 1. Generate text content if missing
            if (empty($order->generated_content)) {
                $promptData = PedagogicalPromptEngine::build($order);
                $prompt = $promptData['prompt'];
                $system = $promptData['system'];

                try {
                    $result = $gemini->generateText($prompt, $system, temperature: 0.2, timeoutSeconds: 28);

                    if (! empty($result)) {
                        $cleanedResult = GeneratorCheckoutController::cleanMathFormulasForPrint($result);
                        $verifierAi = ($order->variant_count <= 3) ? $gemini : null;
                        $verifiedResult = ExamVerificationEngine::verifyAndCorrect($cleanedResult, $order, $verifierAi);

                        $order->update([
                            'generated_content' => $verifiedResult,
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::error('AI generation error in GenerateExamPdfJob for ' . $order->order_number . ': ' . $e->getMessage());
                }

                // If AI failed or timed out, fallback to structured course material
                if (empty($order->fresh()->generated_content)) {
                    $fallbackContent = $this->buildFallbackContent($order, (int) ($order->variant_count ?: 5));
                    $verifiedFallback = ExamVerificationEngine::verifyAndCorrect($fallbackContent, $order, null);
                    $order->update([
                        'generated_content' => GeneratorCheckoutController::cleanMathFormulasForPrint($verifiedFallback),
                    ]);
                }
            }

            // 2. Render PDF
            $relativePath = "generator_orders/{$order->order_number}.pdf";
            $sourceContent = (string) $order->fresh()->generated_content;
            $parsedData = ExamDocumentParser::parse($sourceContent, $order);

            // Priority 1: High-speed Node.js microservice (KaTeX + Chromium)
            $pdfSuccess = false;
            if (NodeExamGeneratorService::isAvailable()) {
                $pdfSuccess = NodeExamGeneratorService::generate($order, $parsedData, $relativePath);
            }

            // Priority 2: Python ReportLab fallback
            if (! $pdfSuccess || ! Storage::disk('local')->exists($relativePath)) {
                $pdfSuccess = PythonExamGeneratorService::generate($order, $parsedData, $relativePath);
            }

            // Priority 3: DomPDF emergency fallback
            if (! $pdfSuccess || ! Storage::disk('local')->exists($relativePath)) {
                $cleaned = GeneratorCheckoutController::cleanMathFormulasForPrint($sourceContent);
                $htmlContent = Str::markdown($cleaned, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]);

                $pdf = Pdf::loadView('generator.pdf-standard-template', array_merge([
                    'order' => $order,
                    'content' => $cleaned,
                    'htmlContent' => $htmlContent,
                ], $parsedData))
                ->setPaper('a4', 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false);

                Storage::disk('local')->put($relativePath, $pdf->output());
                $pdfSuccess = true;
            }

            $order->update([
                'status' => 'completed',
                'pdf_path' => $relativePath,
            ]);

            // 3. Send email with access link
            $this->sendOrderEmail($order);
        } catch (Throwable $e) {
            Log::error('GenerateExamPdfJob critical error for ' . $order->order_number . ': ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->order->update([
            'status' => 'failed',
        ]);
        Log::error('GenerateExamPdfJob failed after all attempts: ' . ($exception?->getMessage() ?? 'Unknown error'));
    }

    protected function buildFallbackContent(GeneratorOrder $order, int $count): string
    {
        $subject = $order->subject ?: 'Математика';
        $topic = $order->topic ?: 'Стандарты ЦТ и ЦЭ 2026';
        $content = "# Проверочная работа: {$subject}\n## Тема: {$topic}\n\n";

        for ($i = 1; $i <= $count; $i++) {
            $content .= "### Вариант {$i}\n\n";
            $content .= "**Часть А**\n\n";
            $content .= "А1. Найдите значение выражения при $x = 2$: $2x + 5$.\n";
            $content .= "1) 7\n2) 9\n3) 11\n4) 4\n\n";
            $content .= "А2. Укажите область определения функции $y = \\frac{1}{x - 3}$.\n";
            $content .= "1) $x \\neq 3$\n2) $x > 3$\n3) $x < 3$\n4) Все действительные числа\n\n";
            $content .= "**Часть Б**\n\n";
            $content .= "Б1. Решите уравнение: $3x - 12 = 0$. Запишите корень уравнения.\n\n";
            $content .= "Б2. Найдите периметр квадрата со стороной $a = 7$.\n\n";
        }

        $content .= "## Ключи и ответы\n\n| Вариант | Задание | Ответ | Обоснование |\n|---|---|---|---|\n";
        for ($i = 1; $i <= $count; $i++) {
            $content .= "| {$i} | А1 | 2 | Подстановка: $2(2) + 5 = 9$. |\n";
            $content .= "| {$i} | А2 | 1 | Знаменатель дроби не равен нулю: $x - 3 \\neq 0 \\implies x \\neq 3$. |\n";
            $content .= "| {$i} | Б1 | 4 | $3x = 12 \\implies x = 4$. |\n";
            $content .= "| {$i} | Б2 | 28 | $P = 4 \\times 7 = 28$. |\n";
        }

        return $content;
    }

    protected function sendOrderEmail(GeneratorOrder $order): void
    {
        if (empty($order->email)) {
            return;
        }

        try {
            $downloadUrl = route('generator.download', [
                'order' => $order->order_number,
                'token' => $order->access_token,
            ]);

            $subject = "Ваш тест готов: {$order->subject} — {$order->topic} (Edusfera)";

            Mail::send([], [], function ($message) use ($order, $downloadUrl, $subject) {
                $message->to($order->email)
                    ->subject($subject)
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #1a1a1a;'>
                            <div style='border-bottom: 2px solid #7D39EB; padding-bottom: 16px; margin-bottom: 24px;'>
                                <h1 style='color: #7D39EB; margin: 0; font-size: 24px;'>EDUSFERA</h1>
                                <p style='color: #666; margin: 4px 0 0 0; font-size: 14px;'>Не зубри. Знай.</p>
                            </div>
                            <h2 style='font-size: 18px; margin-bottom: 16px;'>Ваш комплект проверочных материалов сформирован</h2>
                            <p style='line-height: 1.6; color: #444;'>
                                Предмет: <strong>" . e($order->subject) . "</strong><br>
                                Тема: <strong>" . e($order->topic) . "</strong><br>
                                Количество вариантов: <strong>" . (int) $order->variant_count . "</strong>
                            </p>
                            <div style='margin: 32px 0; text-align: center;'>
                                <a href='{$downloadUrl}' style='background-color: #7D39EB; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                                    Скачать PDF с бланками и ключами
                                </a>
                            </div>
                            <p style='font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 16px;'>
                                Ссылка доступна бессрочно. Вы также можете в любой момент скачать материал на странице заказа: " . e($downloadUrl) . "
                            </p>
                        </div>
                    ");
            });
        } catch (Throwable $e) {
            Log::warning('Could not send generator order email from job: ' . $e->getMessage(), [
                'order' => $order->order_number,
            ]);
        }
    }
}

