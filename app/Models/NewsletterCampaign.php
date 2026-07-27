<?php

namespace App\Models;

use Database\Factories\NewsletterCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A sent newsletter campaign record.
 *
 * @property int $id
 * @property string $subject
 * @property string $content
 * @property int $recipients_count
 * @property Carbon $sent_at
 */
class NewsletterCampaign extends Model
{
    /** @use HasFactory<NewsletterCampaignFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subject',
        'content',
        'recipients_count',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }
}
