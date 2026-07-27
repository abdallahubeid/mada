<?php

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Footer / campaign newsletter subscriber.
 *
 * @property int $id
 * @property string $email
 * @property string $status
 * @property Carbon|null $subscribed_at
 * @property Carbon|null $unsubscribed_at
 * @property Carbon|null $deleted_at
 */
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'status',
        'subscribed_at',
        'unsubscribed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBSCRIBED);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNSUBSCRIBED);
    }

    public function isSubscribed(): bool
    {
        return $this->status === self::STATUS_SUBSCRIBED;
    }

    public function markSubscribed(): void
    {
        $this->forceFill([
            'status' => self::STATUS_SUBSCRIBED,
            'subscribed_at' => $this->subscribed_at ?? now(),
            'unsubscribed_at' => null,
        ])->save();
    }

    public function markUnsubscribed(): void
    {
        $this->forceFill([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ])->save();
    }

    public function toggleStatus(): void
    {
        if ($this->isSubscribed()) {
            $this->markUnsubscribed();

            return;
        }

        $this->markSubscribed();
    }

    public function statusLabel(): string
    {
        return $this->isSubscribed() ? 'مشترك' : 'ملغى الاشتراك';
    }
}
