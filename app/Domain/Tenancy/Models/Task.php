<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\TaskPriority;
use App\Domain\Tenancy\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A work item a line manager assigns to one of their direct reports,
 * tracked through a 4-stage Scrum-style status.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $manager_id
 * @property int $employee_id
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $due_date
 * @property TaskPriority $priority
 * @property TaskStatus $status
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'manager_id',
        'employee_id',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'priority' => 'medium',
        'status' => 'todo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
