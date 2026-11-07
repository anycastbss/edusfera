<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GeneratorOrder;
use App\Services\Ai\GeminiService;
use App\Services\Payment\AlfaBankPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    public function test_generator_landing_page_renders_successfully(): void
    {
        $response = $this->get(route('generator'));

        $response->assertStatus(200);
        $response->assertSee('generator-app-root');
    }

    public function test_checkout_initiation_validates_required_fields(): void
    {
        $response = $this->postJson(route('generator.checkout.initiate'), [
            'email' => 'not-an-email',
            'subject' => '',
            'topic' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'subject', 'topic']);
    }

    public function test_checkout_initiation_creates_order_and_normalizes_subject(): void
    {
        $gatewayMock = Mockery::mock(AlfaBankPaymentGateway::class);
        $gatewayMock->shouldReceive('createPayment')
            ->once()
            ->andReturn([
                'success' => true,
                'redirect_url' => 'https://payment.alfabank.by/test-redirect',
                'mdOrder' => 'alfa-order-12345',
            ]);
        $this->app->instance(AlfaBankPaymentGateway::class, $gatewayMock);

        $response = $this->postJson(route('generator.checkout.initiate'), [
            'email' => 'tutor@school.by',
            'subject' => 'math',
            'topic' => 'Иррациональные уравнения',
            'count' => 25,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect_url' => 'https://payment.alfabank.by/test-redirect',
        ]);

        $this->assertDatabaseHas('generator_orders', [
            'email' => 'tutor@school.by',
            'subject' => 'Математика',
            'topic' => 'Иррациональные уравнения',
            'variant_count' => 25,
            'amount_kopecks' => 500,
            'status' => 'pending',
            'alfa_order_id' => 'alfa-order-12345',
        ]);
    }

    public function test_checkout_initiation_with_quarter_pass_charges_19_byn(): void
    {
        $gatewayMock = Mockery::mock(AlfaBankPaymentGateway::class);
        $gatewayMock->shouldReceive('createPayment')
            ->once()
            ->with(Mockery::on(function ($args) {
                return (float) $args['amount'] === 19.00;
            }))
            ->andReturn([
                'success' => true,
                'redirect_url' => 'https://payment.alfabank.by/test-redirect',
                'mdOrder' => 'alfa-order-pass',
            ]);
        $this->app->instance(AlfaBankPaymentGateway::class, $gatewayMock);

        $response = $this->postJson(route('generator.checkout.initiate'), [
            'email' => 'tutor@school.by',
            'subject' => 'physics',
            'topic' => 'Законы постоянного тока',
            'count' => 15,
            'plan_type' => 'quarter_pass',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('generator_orders', [
            'email' => 'tutor@school.by',
            'amount_kopecks' => 1900,
            'status' => 'pending',
        ]);
    }

    public function test_hosted_view_loads_for_order(): void
    {
        $order = GeneratorOrder::create([
            'order_number' => 'GEN-TEST1234',
            'email' => 'teacher@school.by',
            'subject' => 'Физика',
            'topic' => 'Законы Ньютона',
            'variant_count' => 15,
            'amount_kopecks' => 500,
            'currency' => 'BYN',
            'status' => 'pending',
        ]);

        $response = $this->get(route('generator.checkout.hosted', ['order' => $order->order_number]));

        $response->assertStatus(200);
        $response->assertSee('GEN-TEST1234');
        $response->assertSee('Физика');
        $response->assertDontSee('{/*');
    }

    public function test_callback_marks_order_as_paid_and_generates_materials(): void
    {
        $geminiMock = Mockery::mock(GeminiService::class);
        $geminiMock->shouldReceive('generateText')
            ->once()
            ->andReturn("### Вариант 1\n**Задание А1:** Пример\n## Сводная мастер-таблица\n| Вар | А1 | Б1 |\n| 1 | 2 | 4 |");
        $this->app->instance(GeminiService::class, $geminiMock);

        $gatewayMock = Mockery::mock(AlfaBankPaymentGateway::class);
        $gatewayMock->shouldReceive('getOrderStatus')
            ->once()
            ->with('ALFA-ORDER-999')
            ->andReturn([
                'is_paid' => true,
                'raw' => [
                    'orderStatus' => 2,
                    'orderNumber' => 'GEN-CALLBACK',
                ],
            ]);
        $this->app->instance(AlfaBankPaymentGateway::class, $gatewayMock);

        $order = GeneratorOrder::create([
            'order_number' => 'GEN-CALLBACK',
            'email' => 'teacher@school.by',
            'subject' => 'Математика',
            'topic' => 'Тригонометрия',
            'variant_count' => 10,
            'amount_kopecks' => 500,
            'currency' => 'BYN',
            'status' => 'pending',
            'alfa_order_id' => 'ALFA-ORDER-999',
        ]);

        $response = $this->get(route('generator.checkout.callback', [
            'order' => $order->order_number,
            'orderId' => 'ALFA-ORDER-999',
        ]));

        $response->assertRedirect(route('generator.success', ['order' => $order->order_number]));

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertEquals('completed', $order->status);
        $this->assertNotEmpty($order->generated_content);
    }

    public function test_callback_rejects_unverified_status_success_spoofing(): void
    {
        $order = GeneratorOrder::create([
            'order_number' => 'GEN-SPOOF',
            'email' => 'user@school.by',
            'subject' => 'Математика',
            'topic' => 'Тест без оплаты',
            'variant_count' => 10,
            'amount_kopecks' => 500,
            'currency' => 'BYN',
            'status' => 'pending',
        ]);

        $response = $this->get(route('generator.checkout.callback', [
            'order' => $order->order_number,
            'status' => 'success', // Spoofed query parameter without gateway validation
        ]));

        $response->assertRedirect(route('generator', ['payment' => 'failed']));

        $order->refresh();
        $this->assertFalse($order->isPaid());
    }

    public function test_download_pdf_rejects_unpaid_order(): void
    {
        $order = GeneratorOrder::create([
            'order_number' => 'GEN-UNPAID',
            'email' => 'teacher@school.by',
            'subject' => 'Химия',
            'topic' => 'Электролиз',
            'variant_count' => 15,
            'amount_kopecks' => 500,
            'currency' => 'BYN',
            'status' => 'pending',
        ]);

        $response = $this->get(route('generator.download', ['order' => $order->order_number]));

        $response->assertRedirect(route('generator'));
    }

    public function test_download_pdf_serves_valid_pdf_for_paid_order(): void
    {
        $order = GeneratorOrder::create([
            'order_number' => 'GEN-PAID123',
            'email' => 'teacher@school.by',
            'subject' => 'Математика',
            'topic' => 'Стереометрия',
            'variant_count' => 15,
            'amount_kopecks' => 500,
            'currency' => 'BYN',
            'status' => 'completed',
            'generated_content' => "### Вариант 1\nТест\n## Таблица ответов\n| 1 | 2 |",
        ]);

        $response = $this->get(route('generator.download', ['order' => $order->order_number]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_simulated_success_rejected_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.alfabank.sandbox' => false]);
        config(['payments.alfabank.test_mode' => false]);

        $order = GeneratorOrder::create([
            'order_number' => 'GEN-PROD-HACK',
            'email' => 'hacker@example.com',
            'subject' => 'Математика',
            'topic' => 'Тест',
            'variant_count' => 15,
            'amount_kopecks' => 500,
            'currency' => 'BYN',
            'status' => 'pending',
        ]);

        $response = $this->get(route('generator.checkout.callback', [
            'order' => $order->order_number,
            'simulated_success' => 1,
        ]));

        $response->assertRedirect(route('generator', ['payment' => 'failed']));

        $order->refresh();
        $this->assertEquals('pending', $order->status);
        $this->assertFalse($order->isPaid());
    }
}
