<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum SubscriptionPlan: string
{
    case START = 'start';
    case PRO = 'pro';
    case BASIC = 'basic';
    case PREMIUM = 'premium';

    public function title(): string
    {
        return match ($this) {
            self::START, self::BASIC => 'Стандарт',
            self::PRO => 'Pro',
            self::PREMIUM => 'Премиум',
        };
    }

    public function label(): string
    {
        return $this->title();
    }

    public function getLabel(): string
    {
        return $this->title();
    }

    public function monthlyPriceKopecks(): int
    {
        return match ($this) {
            self::START, self::BASIC => 2000,
            self::PRO => 4000,
            self::PREMIUM => 6000,
        };
    }

    public function monthlyPriceByn(): int
    {
        return (int) ($this->monthlyPriceKopecks() / 100);
    }

    public function yearlyMonthlyEquivalent(): float
    {
        return round($this->yearlyPriceByn() / 12, 2);
    }

    /**
     * 20% discount on yearly subscription (10 months price for 12 months access).
     */
    public function yearlyPriceKopecks(): int
    {
        return match ($this) {
            self::START, self::BASIC => 19200,
            self::PRO => 38400,
            self::PREMIUM => 57600,
        };
    }

    public function yearlyPriceByn(): int
    {
        return (int) ($this->yearlyPriceKopecks() / 100);
    }

    public function trialDays(): int
    {
        return match ($this) {
            self::START, self::BASIC => 5,
            self::PRO => 14,
            self::PREMIUM => 28,
        };
    }

    public function maxResponsesPerMonth(): ?int
    {
        return match ($this) {
            self::START, self::BASIC => 0,
            self::PRO => 10,
            self::PREMIUM => null,
        };
    }

    public function monthlyResponsesLimit(): ?int
    {
        return $this->maxResponsesPerMonth();
    }

    public function aiMonthlyLimit(): ?int
    {
        return match ($this) {
            self::START, self::BASIC => 10,
            self::PRO => 50,
            self::PREMIUM => null,
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::START, self::BASIC => 'Для старта',
            self::PRO => '⭐ Хит продаж',
            self::PREMIUM => '👑 Топ-эксперт',
        };
    }

    public function subtitle(): string
    {
        return match ($this) {
            self::START, self::BASIC => 'Для работы со своими учениками в приватном кабинете (до 5 учеников)',
            self::PRO => 'Для стабильного дохода: до 15 учеников, 50 генераций ИИ и авто-чеки НПД',
            self::PREMIUM => 'Режим «Личная школа» (White-Label), безлимит учеников и ТОП-1 в каталоге',
        };
    }

    public function features(): array
    {
        return match ($this) {
            self::START, self::BASIC => [
                'До 5 активных учеников',
                'Приватный кабинет (анкета и цены скрыты от посторонних)',
                'Виртуальный класс (SFU) и интерактивная доска',
                'Длительность занятия до 60 минут',
                'Расписание, CRM и персональная ссылка на запись',
                'Базовое облачное хранилище (1 ГБ)',
                'Ручной учет налогов (0 откликов на бирже)',
            ],
            self::PRO => [
                'До 15 активных учеников',
                '🔒 Приватный кабинет без публикации в каталоге (или открытая витрина на выбор)',
                '50 генераций ИИ в месяц (Сборник заданий ЦТ/ЦЭ, авто-домашка)',
                'Авто-генерация чеков НПД для МНС РБ в 1 клик',
                'Виртуальный класс без ограничений по времени + 15 ГБ хранилища',
                'Календарь + автонапоминания ученикам (SMS и Telegram)',
                'Отклики на входящие заявки (до 10 в месяц)',
            ],
            self::PREMIUM => [
                'БЕЗЛИМИТНО учеников (неограниченно)',
                '🏛️ Личная онлайн-школа + Настройка по звонку (White-Label: персональный бренд)',
                'БЕЗЛИМИТНЫЙ ИИ-помощник (генерация ДЗ, тестов ЦТ/ЦЭ и планов)',
                'Авто-генерация чеков НПД в 1 клик (полный автопилот)',
                '👑 Золотой бейдж «Топ-эксперт» и ТОП-1 позиция в каталоге',
                'Видео-визитка прямо в поисковой выдаче каталога',
                'БЕЗЛИМИТНЫЕ отклики на заявки и автоподбор учеников',
                'Двусторонняя авто-синхронизация с Google Calendar',
                'Безлимитное облачное хранилище (100 ГБ) и запись уроков в HD',
                'Мгновенный вывод средств (Instant Payout) 0%',
                'Персональный менеджер и приоритетная VIP-поддержка 24/7',
            ],
        };
    }

    public function storageLimitGb(): int
    {
        return match ($this) {
            self::START, self::BASIC => 1,
            self::PRO => 15,
            self::PREMIUM => 100,
        };
    }

    public function maxLessonDurationMinutes(): ?int
    {
        return match ($this) {
            self::START, self::BASIC => 60,
            self::PRO, self::PREMIUM => null,
        };
    }

    public function allowsVideoCalls(): bool
    {
        return true;
    }

    public function allowsCalendarSync(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsBranding(): bool
    {
        return match ($this) {
            self::START, self::BASIC => false,
            self::PRO, self::PREMIUM => true,
        };
    }

    public function allowsVideoIntro(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsInstantPayout(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsAnalytics(): bool
    {
        return match ($this) {
            self::START, self::BASIC => false,
            self::PRO, self::PREMIUM => true,
        };
    }

    public function allowsDeepAnalytics(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function searchRankWeight(): int
    {
        return match ($this) {
            self::START, self::BASIC => 1,
            self::PRO => 5,
            self::PREMIUM => 10,
        };
    }
}
