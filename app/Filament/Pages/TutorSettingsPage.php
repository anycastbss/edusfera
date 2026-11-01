<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\TutorProfile;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TutorSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.tutor-settings-page';

    protected static ?string $navigationLabel = 'Настройки';

    protected static ?string $title = 'Настройки кабинета';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 4;

    public bool $isLargeFont = false;

    public bool $isPublic = false;

    public string $customMessageTemplate = '';

    public ?int $profileId = null;

    public static function canAccess(): bool
    {
        if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'site-admin') {
            return false;
        }

        $user = Auth::user();

        return (bool) $user?->isTutor();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Профиль и документы';
    }

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user?->tutorProfile;

        if ($profile) {
            $this->profileId = $profile->id;
            $this->isPublic = (bool) $profile->is_public;
            $this->isLargeFont = (bool) $profile->comfort_vision_mode;
        }

        $this->customMessageTemplate = "Здравствуйте! Ссылка на наш онлайн-урок в Edusfera: {link}. Вход в один клик без регистрации.";
    }

    public function toggleLargeFont(bool $enabled): void
    {
        $this->isLargeFont = $enabled;
        $user = Auth::user();
        $profile = $user?->tutorProfile;
        if ($profile) {
            $profile->comfort_vision_mode = $enabled;
            $profile->save();
        }

        $this->dispatch('edusfera:large-font-changed', isLarge: $enabled);

        Notification::make()
            ->title($enabled ? 'Режим крупного шрифта включен' : 'Стандартный шрифт включен')
            ->body($enabled ? 'Текст увеличен, контрастность повышена для комфортного чтения.' : 'Интерфейс переключен в стандартный режим.')
            ->success()
            ->send();
    }

    public function togglePrivacy(bool $makePublic): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if (! $makePublic && ! app(\App\Domain\Subscription\Services\SubscriptionFeatureGate::class)->canHideProfile($user)) {
            Notification::make()
                ->title('Приватный режим требует тариф PRO или Онлайн-школа')
                ->body('Скрытие анкеты из каталога доступно на тарифах от 40 BYN/мес или в течение 14-дневного бесплатного пробного периода.')
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('upgrade')
                        ->label('Выбрать тариф')
                        ->url('/admin/tutor-subscription-page'),
                ])
                ->send();

            return;
        }

        $profile = $user->tutorProfile;

        if ($profile) {
            $profile->is_public = $makePublic;
            $profile->save();
            $this->isPublic = $makePublic;

            Notification::make()
                ->title($makePublic ? 'Анкета открыта для каталога' : 'Включен режим личной школы')
                ->body($makePublic ? 'Ваша анкета видна родителям и ученикам в каталоге.' : 'Ваша анкета и расценки скрыты от каталога и поисковиков.')
                ->success()
                ->send();
        }
    }

    public function saveTemplate(): void
    {
        $user = Auth::user();
        if ($user) {
            $settings = $user->settings ?? [];
            $settings['message_template'] = trim($this->customMessageTemplate);
            $user->settings = $settings;
            $user->save();

            Notification::make()
                ->title('Шаблон сообщения сохранен')
                ->body('Этот текст будет использоваться для отправки ссылок в Viber и Telegram.')
                ->success()
                ->send();
        }
    }
}
