<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'owner_id',
        'name',
        'code',
        'description',
        'color',
        'status',
        'priority',
        'start_date',
        'end_date',
        'deadline',
        'budget',
        'settings',
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
            'end_date' => 'date',
            'deadline' => 'date',
            'budget' => 'decimal:2',
            'settings' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->code)) {
                $project->code = static::generateProjectCode($project->organization_id);
            }
        });
    }

    /**
     * Generate unique project code.
     */
    public static function generateProjectCode($organizationId): string
    {
        $org = Organization::find($organizationId);
        $prefix = strtoupper(substr($org->name, 0, 3));

        $lastProject = static::where('organization_id', $organizationId)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastProject ? intval(substr($lastProject->code, -3)) + 1 : 1;

        return $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the organization that owns the project.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the project owner.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the project team members.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Get the project milestones.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    /**
     * Get the project tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    /**
     * Get active milestones.
     */
    public function activeMilestones(): HasMany
    {
        return $this->milestones()->whereIn('status', ['planning', 'active']);
    }

    /**
     * Get active tasks.
     */
    public function activeTasks(): HasMany
    {
        return $this->tasks()->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Get overdue tasks.
     */
    public function overdueTasks(): HasMany
    {
        return $this->tasks()
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Add user to project team.
     */
    public function addTeamMember(User $user, string $role = 'member'): void
    {
        $this->users()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove user from project team.
     */
    public function removeTeamMember(User $user): void
    {
        $this->users()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);
    }

    /**
     * Get project progress percentage.
     */
    public function getProgressAttribute(): float
    {
        $totalTasks = $this->tasks()->count();

        if ($totalTasks === 0) {
            return 0.0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();

        return round(($completedTasks / $totalTasks) * 100, 1);
    }

    /**
     * Get project statistics.
     */
    public function getStats(): array
    {
        return [
            'total_tasks' => $this->tasks()->count(),
            'completed_tasks' => $this->tasks()->where('status', 'completed')->count(),
            'in_progress_tasks' => $this->tasks()->where('status', 'in_progress')->count(),
            'overdue_tasks' => $this->overdueTasks()->count(),
            'total_milestones' => $this->milestones()->count(),
            'completed_milestones' => $this->milestones()->where('status', 'completed')->count(),
            'team_members' => $this->users()->count(),
            'progress' => $this->progress,
        ];
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'completed';
    }

    /**
     * Get days until deadline.
     */
    public function getDaysUntilDeadline(): ?int
    {
        if (!$this->deadline) {
            return null;
        }

        return now()->diffInDays($this->deadline, false);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return [
            'planning' => 'gray',
            'active' => 'blue',
            'on_hold' => 'yellow',
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
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active projects.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planning', 'active']);
    }

    /**
     * Scope to filter projects by user access.
     */
    public function scopeAccessibleBy($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('users', function ($subQ) use ($user) {
                  $subQ->where('user_id', $user->id);
              });
        });
    }
}
