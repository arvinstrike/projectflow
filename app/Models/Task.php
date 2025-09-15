<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'milestone_id',
        'assignee_id',
        'created_by',
        'parent_id',
        'title',
        'description',
        'status',
        'priority',
        'type',
        'estimated_hours',
        'actual_hours',
        'time_slot',
        'start_date',
        'due_date',
        'completed_at',
        'tags',
        'notes',
        'sort_order',
        'progress',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'tags' => 'array',
            'progress' => 'decimal:2',
            'time_slot' => 'datetime:H:i:s',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if ($task->sort_order === null) {
                $lastOrder = static::where('project_id', $task->project_id)
                    ->max('sort_order');
                $task->sort_order = ($lastOrder ?? 0) + 1;
            }
        });

        static::updated(function ($task) {
            if ($task->status === 'completed' && !$task->completed_at) {
                $task->update(['completed_at' => now(), 'progress' => 100]);
            }

            // Update milestone progress
            if ($task->milestone) {
                $task->milestone->updateProgress();
            }
        });
    }

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the milestone that owns the task.
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent task.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Get the subtasks.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all available status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'in_review' => 'In Review',
            'blocked' => 'Blocked',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Get all available priority options.
     */
    public static function getPriorityOptions(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }

    /**
     * Get all available type options.
     */
    public static function getTypeOptions(): array
    {
        return [
            'task' => 'Task',
            'bug' => 'Bug',
            'feature' => 'Feature',
            'epic' => 'Epic',
        ];
    }

    /**
     * Get formatted time slot.
     */
    public function getFormattedTimeSlotAttribute(): ?string
    {
        if (!$this->time_slot) {
            return null;
        }

        return Carbon::parse($this->time_slot)->format('g:i A');
    }

    /**
     * Get formatted estimated time.
     */
    public function getFormattedEstimatedTimeAttribute(): ?string
    {
        if (!$this->estimated_hours) {
            return null;
        }

        if ($this->estimated_hours < 1) {
            return round($this->estimated_hours * 60) . 'm';
        }

        $hours = floor($this->estimated_hours);
        $minutes = ($this->estimated_hours - $hours) * 60;

        if ($minutes > 0) {
            return $hours . 'h ' . round($minutes) . 'm';
        }

        return $hours . 'h';
    }

    /**
     * Get formatted actual time.
     */
    public function getFormattedActualTimeAttribute(): ?string
    {
        if (!$this->actual_hours) {
            return null;
        }

        if ($this->actual_hours < 1) {
            return round($this->actual_hours * 60) . 'm';
        }

        $hours = floor($this->actual_hours);
        $minutes = ($this->actual_hours - $hours) * 60;

        if ($minutes > 0) {
            return $hours . 'h ' . round($minutes) . 'm';
        }

        return $hours . 'h';
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return [
            'todo' => 'gray',
            'in_progress' => 'blue',
            'in_review' => 'purple',
            'blocked' => 'red',
            'completed' => 'green',
            'cancelled' => 'red',
        ][$this->status] ?? 'gray';
    }

    /**
     * Get priority badge color.
     */
    public function getPriorityColorAttribute(): string
    {
        return [
            'low' => 'green',
            'medium' => 'blue',
            'high' => 'yellow',
            'critical' => 'red',
        ][$this->priority] ?? 'gray';
    }

    /**
     * Get type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return [
            'task' => 'blue',
            'bug' => 'red',
            'feature' => 'green',
            'epic' => 'purple',
        ][$this->type] ?? 'gray';
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Mark task as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress' => 100,
        ]);
    }

    /**
     * Reopen task.
     */
    public function reopen(): void
    {
        $this->update([
            'status' => 'todo',
            'completed_at' => null,
        ]);
    }

    /**
     * Get task hierarchy level.
     */
    public function getHierarchyLevel(): int
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    /**
     * Check if task can be moved to status.
     */
    public function canMoveToStatus(string $status): bool
    {
        $validTransitions = [
            'todo' => ['in_progress', 'cancelled'],
            'in_progress' => ['in_review', 'blocked', 'completed', 'todo'],
            'in_review' => ['completed', 'in_progress', 'todo'],
            'blocked' => ['todo', 'in_progress'],
            'completed' => ['in_review', 'todo'],
            'cancelled' => ['todo'],
        ];

        return in_array($status, $validTransitions[$this->status] ?? []);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority.
     */
    public function scopeWithPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter by assignee.
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    /**
     * Scope to filter overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope to filter active tasks.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope to filter parent tasks only.
     */
    public function scopeParentOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to search tasks.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')");
    }
}
