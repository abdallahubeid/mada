<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * ATS job application submitted via public careers or internal intake.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $job_posting_id
 * @property string $applicant_name
 * @property string $email
 * @property string $phone
 * @property string $cv_path
 * @property string|null $cover_letter
 * @property ApplicationStatus $status
 * @property int|null $converted_employee_id
 */
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): JobApplicationFactory
    {
        return JobApplicationFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'job_posting_id',
        'applicant_name',
        'email',
        'phone',
        'cv_path',
        'cover_letter',
        'status',
        'converted_employee_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    public function cvUrl(): ?string
    {
        return filled($this->cv_path)
            ? Storage::disk('custom')->url($this->cv_path)
            : null;
    }

    public function isConverted(): bool
    {
        return $this->converted_employee_id !== null;
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function convertedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'converted_employee_id');
    }

    /**
     * Every interview stage booked for this candidate, newest first.
     *
     * @return HasMany<Interview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->latest('scheduled_at');
    }
}
