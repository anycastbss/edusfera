<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    use HasFactory;

    protected $connection = 'news';

    protected $table = 'news_articles';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'content',
        'featured_image',
        'video_url',
        'status',
        'published_at',
    ];

    public const CATEGORY_TRAINING = 'platform-training';
    public const CATEGORY_NEWS = 'news';
    public const CATEGORY_EXAM = 'exam-prep';

    public static function categories(): array
    {
        return [
            self::CATEGORY_TRAINING => 'Обучение работе на платформе',
            self::CATEGORY_NEWS => 'Новости и обновления',
            self::CATEGORY_EXAM => 'Подготовка к ЦТ / ЦЭ',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_TRAINING => 'Обучение платформе',
            self::CATEGORY_NEWS => 'Новости',
            self::CATEGORY_EXAM => 'Методика ЦТ/ЦЭ',
            default => 'Инструкция',
        };
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_TRAINING => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::CATEGORY_NEWS => 'bg-violet-50 text-violet-700 border-violet-200',
            self::CATEGORY_EXAM => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (NewsArticle $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug(Str::ascii($article->title));
            }
        });
    }

    /**
     * Scope a query to only include published news articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, ?string $category)
    {
        if (empty($category) || $category === 'all') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Get embedded URL for YouTube or Vimeo video.
     */
    public function getEmbedVideoUrlAttribute(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        // YouTube
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->video_url, $match)) {
            return "https://www.youtube.com/embed/{$match[1]}";
        }

        // Vimeo
        if (preg_match('%vimeo\.com/(?:channels/(?:\w+/)?|groups/([^/]*)/videos/|album/(\d+)/video/|video/|)(\d+)(?:$|/|\?)%i', $this->video_url, $match)) {
            return "https://player.vimeo.com/video/{$match[3]}";
        }

        return null;
    }
}
