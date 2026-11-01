<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Subscription\Models\Subscription;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionProfilePublicWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Subscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Оплатите тариф Частная практика, чтобы ваша анкета не стала публичной')
            ->body('Через 2 дня срок действия вашего закрытого профиля истекает. Без продления анкета станет видимой в общем каталоге.')
            ->icon('heroicon-o-lock-closed')
            ->iconColor('warning')
            ->getDatabaseMessage() + [
                'subscription_id' => $this->subscription->id,
                'url' => '/admin/tutor-subscription-page',
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Оплатите тариф Частная практика, чтобы ваша анкета не стала публичной')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Через 2 дня завершается оплаченный период закрытого профиля на платформе Edusfera.')
            ->line('Чтобы ваша анкета преподавателя не появилась в общем каталоге и поиске, оплатите тариф «Частная практика» (40 BYN) или «Премиум» (60 BYN).')
            ->action('Продлить закрытый профиль', url('/admin/tutor-subscription-page'));
    }
}
