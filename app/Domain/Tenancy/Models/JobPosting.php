<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\EmploymentType;
use App\Domain\Tenancy\Enums\JobPostingStatus;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Internal job opening for recruitment / public careers portal.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $department_id
 * @property string $title
 * @property string $slug
 * @property EmploymentType $employment_type
 * @property string|null $location
 * @property string|null $salary_range
 * @property string $description
 * @property string|null $requirements
 * @property JobPostingStatus $status
 */
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): JobPostingFactory
    {
        return JobPostingFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'department_id',
        'title',
        'slug',
        'employment_type',
        'location',
        'salary_range',
        'description',
        'requirements',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'status' => JobPostingStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (JobPosting $posting): void {
            if (blank($posting->slug) && filled($posting->title)) {
                $posting->slug = static::uniqueSlugForTitle($posting->title, $posting->tenant_id, $posting->id);
            }
        });
    }

    public static function uniqueSlugForTitle(string $title, ?int $tenantId, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Portal card payload shape used by careers Blade views.
     *
     * @return array<string, mixed>
     */
    public function toPortalArray(): array
    {
        $requirements = filled($this->requirements)
            ? preg_split("/\r\n|\n|\r/", (string) $this->requirements) ?: []
            : [];

        $responsibilities = array_values(array_filter(array_map('trim', $requirements)));

        if ($responsibilities === []) {
            $responsibilities = array_values(array_filter(array_map(
                'trim',
                preg_split("/\r\n|\n|\r/", (string) $this->description) ?: [],
            )));
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'department' => $this->department?->name ?? 'عام',
            'location' => $this->location ?: 'غير محدد',
            'type' => $this->employment_type->label(),
            'salary_range' => $this->salary_range,
            'posted_at' => $this->created_at?->diffForHumans() ?? '',
            'summary' => Str::limit(strip_tags($this->description), 160),
            'description' => $this->description,
            'requirements' => $this->requirements,
            'responsibilities' => array_slice($responsibilities, 0, 8),
        ];
    }
}
