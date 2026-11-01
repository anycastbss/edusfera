<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Pages;

use App\Services\PlatformConfigService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class PlatformSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.site-admin.pages.platform-settings-page';

    protected static ?string $navigationLabel = 'Управление платформой (Без кода)';

    protected static ?string $title = 'Конфигурация параметров платформы';

    protected static ?string $navigationGroup = 'Управление платформой';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(PlatformConfigService $configService): void
    {
        $settings = $configService->getAll();

        $this->form->fill([
            'maintenance_enabled' => (bool) ($settings['maintenance']['enabled'] ?? false),
            'maintenance_message' => (string) ($settings['maintenance']['message'] ?? ''),
            'maintenance_allowed_ips' => (string) ($settings['maintenance']['allowed_ips'] ?? '127.0.0.1'),

            'livekit_host' => (string) ($settings['webrtc']['livekit_host'] ?? ''),
            'livekit_api_key' => (string) ($settings['webrtc']['livekit_api_key'] ?? ''),
            'livekit_api_secret' => (string) ($settings['webrtc']['livekit_api_secret'] ?? ''),
            'turn_server_url' => (string) ($settings['webrtc']['turn_server_url'] ?? ''),
            'turn_api_key' => (string) ($settings['webrtc']['turn_api_key'] ?? ''),
            'max_lesson_duration' => (int) ($settings['webrtc']['max_lesson_duration'] ?? 60),

            'gemini_api_key' => (string) ($settings['ai']['gemini_api_key'] ?? ''),
            'gemini_model' => (string) ($settings['ai']['model'] ?? 'gemini-2.5-flash'),
            'ai_temperature' => (float) ($settings['ai']['temperature'] ?? 0.7),
            'ai_system_prompt' => (string) ($settings['ai']['system_prompt'] ?? ''),

            'pro_monthly_price' => (float) ($settings['billing']['pro_monthly_price'] ?? 40.0),
            'grace_period_days' => (int) ($settings['billing']['grace_period_days'] ?? 5),
            'trial_days' => (int) ($settings['billing']['trial_days'] ?? 14),
            'platform_commission_pct' => (float) ($settings['billing']['platform_commission_pct'] ?? 10.0),
            'free_ai_generations' => (int) ($settings['billing']['free_ai_generations_monthly'] ?? 15),
            'pro_ai_generations' => (int) ($settings['billing']['pro_ai_generations_monthly'] ?? 200),

            'support_phone' => (string) ($settings['integrations']['support_phone'] ?? ''),
            'support_telegram' => (string) ($settings['integrations']['support_telegram'] ?? ''),
            'telegram_bot_token' => (string) ($settings['integrations']['telegram_bot_token'] ?? ''),
            'telegram_alert_chat_id' => (string) ($settings['integrations']['telegram_alert_chat_id'] ?? ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('🛑 Режим техобслуживания и доступность')
                    ->description('Управление доступностью сайта для обычных пользователей во время технических работ')
                    ->schema([
                        Toggle::make('maintenance_enabled')
                            ->label('Включить режим техобслуживания (Maintenance Mode)')
                            ->helperText('При включении обычные посетители увидят страницу технических работ. Администраторы продолжат иметь доступ.')
                            ->columnSpanFull(),
                        TextInput::make('maintenance_message')
                            ->label('Сообщение для пользователей')
                            ->placeholder('На платформе проводятся плановые технические работы...')
                            ->columnSpan(2),
                        TextInput::make('maintenance_allowed_ips')
                            ->label('Белый список IP-адресов (через запятую)')
                            ->placeholder('127.0.0.1, 192.168.1.1')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Section::make('🎥 Параметры WebRTC & Виртуального класса')
                    ->description('Серверные адреса и авторизационные ключи для комнат LiveKit SFU и TURN-реле')
                    ->schema([
                        TextInput::make('livekit_host')
                            ->label('LiveKit WebSocket URL')
                            ->placeholder('wss://edusfera-iae194s8.livekit.cloud')
                            ->required(),
                        TextInput::make('livekit_api_key')
                            ->label('LiveKit API Key')
                            ->password()
                            ->revealable(),
                        TextInput::make('livekit_api_secret')
                            ->label('LiveKit API Secret')
                            ->password()
                            ->revealable(),
                        TextInput::make('turn_server_url')
                            ->label('Metered TURN URL')
                            ->placeholder('turn:turn.metered.ca:80'),
                        TextInput::make('turn_api_key')
                            ->label('Metered TURN API Key')
                            ->password()
                            ->revealable(),
                        TextInput::make('max_lesson_duration')
                            ->label('Макс. длительность урока (минут)')
                            ->numeric()
                            ->default(60),
                    ])
                    ->columns(3),

                Section::make('🤖 Интеллектуальный шлюз (Google Gemini API)')
                    ->description('Настройка нейросетевых моделей для ЦТ/ЦЭ авто-тестов и ИИ-методиста Майи')
                    ->schema([
                        TextInput::make('gemini_api_key')
                            ->label('Google Gemini API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Ключ из Google AI Studio (aistudio.google.com)')
                            ->columnSpan(2),
                        Select::make('gemini_model')
                            ->label('Активная нейросетевая модель')
                            ->options([
                                'gemini-2.5-flash' => 'Gemini 2.5 Flash (Сверхбыстрая, Рекомендуется)',
                                'gemini-1.5-pro' => 'Gemini 1.5 Pro (Глубокий анализ сложных задач)',
                                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Стабильная база)',
                            ])
                            ->default('gemini-2.5-flash')
                            ->columnSpan(1),
                        TextInput::make('ai_temperature')
                            ->label('Температура (Креативность 0.0 - 1.0)')
                            ->numeric()
                            ->step(0.1)
                            ->default(0.7)
                            ->columnSpan(1),
                        Textarea::make('ai_system_prompt')
                            ->label('Системный промпт методиста Майи')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('💰 Тарифы, квоты и комиссии платформы')
                    ->description('Параметры монетизации и правила подписки для преподавателей')
                    ->schema([
                        TextInput::make('pro_monthly_price')
                            ->label('Стоимость тарифа Pro (BYN / мес)')
                            ->numeric()
                            ->prefix('BYN'),
                        TextInput::make('grace_period_days')
                            ->label('Льготный период при сбое оплаты (дней)')
                            ->numeric()
                            ->default(5),
                        TextInput::make('trial_days')
                            ->label('Бесплатный пробный период (дней)')
                            ->numeric()
                            ->default(14),
                        TextInput::make('platform_commission_pct')
                            ->label('Комиссия платформы (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(10),
                        TextInput::make('free_ai_generations')
                            ->label('Бесплатных ИИ-генераций в мес (Базовый)')
                            ->numeric()
                            ->default(15),
                        TextInput::make('pro_ai_generations')
                            ->label('ИИ-генераций в мес (Тариф Pro)')
                            ->numeric()
                            ->default(200),
                    ])
                    ->columns(3),

                Section::make('🔔 Контакты и Telegram-алерты')
                    ->description('Интеграции для экстренных уведомлений технической команды')
                    ->schema([
                        TextInput::make('telegram_bot_token')
                            ->label('Telegram Bot Token для алертов')
                            ->password()
                            ->revealable(),
                        TextInput::make('telegram_alert_chat_id')
                            ->label('ID чата / группы для алертов')
                            ->placeholder('-1001234567890'),
                        TextInput::make('support_phone')
                            ->label('Телефон техподдержки'),
                        TextInput::make('support_telegram')
                            ->label('Telegram-канал поддержки'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(PlatformConfigService $configService): void
    {
        $state = $this->form->getState();

        $settings = [
            'maintenance' => [
                'enabled' => (bool) ($state['maintenance_enabled'] ?? false),
                'message' => (string) ($state['maintenance_message'] ?? ''),
                'allowed_ips' => (string) ($state['maintenance_allowed_ips'] ?? '127.0.0.1'),
            ],
            'webrtc' => [
                'livekit_host' => (string) ($state['livekit_host'] ?? ''),
                'livekit_api_key' => (string) ($state['livekit_api_key'] ?? ''),
                'livekit_api_secret' => (string) ($state['livekit_api_secret'] ?? ''),
                'turn_server_url' => (string) ($state['turn_server_url'] ?? ''),
                'turn_api_key' => (string) ($state['turn_api_key'] ?? ''),
                'max_lesson_duration' => (int) ($state['max_lesson_duration'] ?? 60),
                'auto_recording' => false,
            ],
            'ai' => [
                'provider' => 'google',
                'gemini_api_key' => (string) ($state['gemini_api_key'] ?? ''),
                'model' => (string) ($state['gemini_model'] ?? 'gemini-2.5-flash'),
                'temperature' => (float) ($state['ai_temperature'] ?? 0.7),
                'system_prompt' => (string) ($state['ai_system_prompt'] ?? ''),
            ],
            'billing' => [
                'pro_monthly_price' => (float) ($state['pro_monthly_price'] ?? 40.0),
                'grace_period_days' => (int) ($state['grace_period_days'] ?? 5),
                'trial_days' => (int) ($state['trial_days'] ?? 14),
                'platform_commission_pct' => (float) ($state['platform_commission_pct'] ?? 10.0),
                'free_ai_generations_monthly' => (int) ($state['free_ai_generations'] ?? 15),
                'pro_ai_generations_monthly' => (int) ($state['pro_ai_generations'] ?? 200),
            ],
            'integrations' => [
                'telegram_bot_token' => (string) ($state['telegram_bot_token'] ?? ''),
                'telegram_alert_chat_id' => (string) ($state['telegram_alert_chat_id'] ?? ''),
                'support_phone' => (string) ($state['support_phone'] ?? ''),
                'support_telegram' => (string) ($state['support_telegram'] ?? ''),
            ],
        ];

        $configService->saveAll($settings);

        Notification::make()
            ->title('Настройки сохранены')
            ->body('Все изменения конфигурации платформы успешно применены без перезапуска кода.')
            ->success()
            ->send();
    }

    public function testGeminiConnection(): void
    {
        $state = $this->form->getState();
        $apiKey = $state['gemini_api_key'] ?? '';
        $model = $state['gemini_model'] ?? 'gemini-2.5-flash';

        if (empty($apiKey)) {
            Notification::make()->title('Ошибка')->body('Сначала введите Gemini API Key')->danger()->send();
            return;
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $response = Http::timeout(8)->post($url, [
                'contents' => [
                    ['parts' => [['text' => 'Привет! Ответь одним словом "OK".']]]
                ]
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->title('Gemini API активен')
                    ->body("Связь с моделью {$model} установлена успешно (200 OK).")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Ошибка связи с Gemini')
                    ->body('Статус: '.$response->status().' - '.$response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Сбой запроса')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
