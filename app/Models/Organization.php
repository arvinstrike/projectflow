<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'settings',
        'plan',
        'max_users',
        'max_projects',
        'status',
        'trial_ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            if (empty($organization->slug)) {
                $organization->slug = Str::slug($organization->name);

                // Ensure slug is unique
                $originalSlug = $organization->slug;
                $count = 1;

                while (static::where('slug', $organization->slug)->exists()) {
                    $organization->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Get the organization's users.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'permissions', 'status', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the organization's active users.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', 'active');
    }

    /**
     * Get the organization's projects.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the organization's active projects.
     */
    public function activeProjects(): HasMany
    {
        return $this->projects()->whereIn('status', ['planning', 'active']);
    }

    /**
     * Get the organization owner.
     */
    public function getOwner()
    {
        return $this->users()->wherePivot('role', 'owner')->first();
    }

    /**
     * Get organization admins.
     */
    public function getAdmins()
    {
        return $this->users()->whereIn('organization_users.role', ['owner', 'admin']);
    }

    /**
     * Check if organization can add more users.
     */
    public function canAddUsers(): bool
    {
        return $this->activeUsers()->count() < $this->max_users;
    }

    /**
     * Check if organization can add more projects.
     */
    public function canAddProjects(): bool
    {
        return $this->activeProjects()->count() < $this->max_projects;
    }

    /**
     * Get remaining user slots.
     */
    public function getRemainingUserSlots(): int
    {
        return max(0, $this->max_users - $this->activeUsers()->count());
    }

    /**
     * Get remaining project slots.
     */
    public function getRemainingProjectSlots(): int
    {
        return max(0, $this->max_projects - $this->activeProjects()->count());
    }

    /**
     * Check if organization is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if trial has expired.
     */
    public function hasTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Get days remaining in trial.
     */
    public function getTrialDaysRemaining(): int
    {
        if (!$this->isOnTrial()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Invite user to organization.
     */
    public function inviteUser(string $email, string $role = 'member'): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create placeholder user or send invitation email
            // Implementation depends on your invitation system
            return;
        }

        $this->users()->attach($user->id, [
            'role' => $role,
            'status' => 'pending',
            'invited_at' => now(),
        ]);

        // Send invitation notification
        // $user->notify(new OrganizationInvitation($this));
    }

    /**
     * Add user to organization.
     */
    public function addUser(User $user, string $role = 'member'): void
    {
        $this->users()->attach($user->id, [
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove user from organization.
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Get organization statistics.
     */
    public function getStats(): array
    {
        return [
            'users_count' => $this->activeUsers()->count(),
            'projects_count' => $this->projects()->count(),
            'active_projects_count' => $this->activeProjects()->count(),
            'completed_projects_count' => $this->projects()->where('status', 'completed')->count(),
            'total_tasks_count' => Task::whereHas('project', function ($query) {
                $query->where('organization_id', $this->id);
            })->count(),
            'completed_tasks_count' => Task::whereHas('project', function ($query) {
                $query->where('organization_id', $this->id);
            })->where('status', 'completed')->count(),
        ];
    }

    /**
     * Get logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }

        return null;
    }

    /**
     * Scope to filter active organizations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get route key name for model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
