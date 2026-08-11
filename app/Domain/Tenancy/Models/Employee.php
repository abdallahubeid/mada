<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Models\User;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * HR employee profile (personal + employment data; payroll fields deferred).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property int|null $department_id
 * @property int|null $manager_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $national_id
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $avatar_path
 * @property string|null $cv_path
 * @property string $job_title
 * @property Carbon $joining_date
 * @property EmployeeStatus $status
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'department_id',
        'manager_id',
        'first_name',
        'last_name',
        'national_id',
        'phone',
        'address',
        'avatar_path',
        'cv_path',
        'job_title',
        'joining_date',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'status' => EmployeeStatus::class,
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim($this->first_name.' '.$this->last_name),
        );
    }

    public function avatarUrl(): string
    {
        if (filled($this->avatar_path)) {
            return Storage::disk('custom')->url($this->avatar_path);
        }

        $initial = htmlspecialchars(
            mb_strtoupper(mb_substr(trim($this->first_name), 0, 1) ?: '?'),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img">
  <rect width="64" height="64" rx="32" fill="#10b981"/>
  <text x="32" y="38" text-anchor="middle" font-family="system-ui,sans-serif" font-size="28" font-weight="700" fill="#022c22">{$initial}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function cvUrl(): ?string
    {
        return filled($this->cv_path)
            ? Storage::disk('custom')->url($this->cv_path)
            : null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /**
     * @return HasMany<EmployeeContract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * @return HasMany<EmployeeEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(EmployeeEvaluation::class);
    }

    /**
     * Tasks assigned to this employee by their manager.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'employee_id');
    }

    /**
     * Tasks this employee has assigned to their direct reports.
     *
     * @return HasMany<Task, $this>
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'manager_id');
    }
}
