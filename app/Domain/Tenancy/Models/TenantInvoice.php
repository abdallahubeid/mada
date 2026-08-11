<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\TenantInvoiceStatus;
use Database\Factories\TenantInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * SaaS billing invoice issued to a tenant (platform → customer).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $number
 * @property string $amount
 * @property string $currency
 * @property TenantInvoiceStatus $status
 * @property Carbon $issued_at
 * @property string|null $pdf_path
 */
class TenantInvoice extends Model
{
    /** @use HasFactory<TenantInvoiceFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): TenantInvoiceFactory
    {
        return TenantInvoiceFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'number',
        'amount',
        'currency',
        'status',
        'issued_at',
        'pdf_path',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
        'status' => 'paid',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => TenantInvoiceStatus::class,
            'issued_at' => 'datetime',
        ];
    }

    public function pdfUrl(): ?string
    {
        if (! filled($this->pdf_path)) {
            return null;
        }

        return Storage::disk('custom')->url($this->pdf_path);
    }
}
