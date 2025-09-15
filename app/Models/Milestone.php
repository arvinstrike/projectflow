<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
        'start_date',
        'due_date',
        'completed_at',
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
            'progress' => 'decimal:2',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($milestone) {
            if ($milestone->sort_order === null) {
                $lastOrder = static::where('project_id', $milestone->project_id)
                    ->max('sort_order');
                $milestone->sort_order = ($lastOrder ?? 0) + 1;
            }
        });

        static::updated(function ($milestone) {
            if ($milestone->status === 'completed' && !$milestone->completed_at) {
                $milestone->update(['completed_at' => now()]);
            }
        });
    }

    /**
     * Get the project that owns the milestone.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the tasks for this milestone.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    /**
     * Get active tasks for this milestone.
     */
    public function activeTasks(): HasMany
    {
        return $this->tasks()->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Get completed tasks for this milestone.
     */
    public function completedTasks(): HasMany
    {
        return $this->tasks()->where('status', 'completed');
    }

    /**
     * Calculate and update progress based on tasks.
     */
    public function updateProgress(): void
    {
        $totalTasks = $this->tasks()->count();

        if ($totalTasks === 0) {
            $this->update(['progress' => 0]);
            return;
        }

        $completedTasks = $this->completedTasks()->count();
        $progress = ($completedTasks / $totalTasks) * 100;

        $this->update(['progress' => round($progress, 2)]);

        // Auto-complete milestone if all tasks are done
        if ($progress === 100.0 && $this->status !== 'completed') {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Get milestone statistics.
     */
    public function getStats(): array
    {
        $totalTasks = $this->tasks()->count();
        $completedTasks = $this->completedTasks()->count();
        $inProgressTasks = $this->tasks()->where('status', 'in_progress')->count();
        $overdueTasks = $this->tasks()
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'overdue_tasks' => $overdueTasks,
            'progress' => $this->progress,
        ];
    }

    /**
     * Check if milestone is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'completed';
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
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return [
            'planning' => 'gray',
            'active' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
        ][$this->status] ?? 'gray';
    }

    /**
     * Get formatted progress with percentage.
     */
    public function getFormattedProgressAttribute(): string
    {
        return number_format($this->progress, 1) . '%';
    }

    /**
     * Mark milestone as completed.
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
     * Reopen milestone.
     */
    public function reopen(): void
    {
        $this->update([
            'status' => 'active',
            'completed_at' => null,
        ]);

        $this->updateProgress();
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active milestones.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planning', 'active']);
    }

    /**
     * Scope to filter overdue milestones.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'completed');
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
