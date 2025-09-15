<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'avatar',
        'timezone',
        'preferences',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the user's organizations.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'permissions', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the user's projects.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Get projects owned by the user.
     */
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * Get tasks assigned to the user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    /**
     * Get tasks created by the user.
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Get the user's active organization.
     */
    public function getCurrentOrganization()
    {
        $organizationId = session('current_organization_id');

        if ($organizationId) {
            return $this->organizations()->where('organization_id', $organizationId)->first();
        }

        return $this->organizations()->where('organization_users.status', 'active')->first();
    }

    /**
     * Get the user's role in a specific organization.
     */
    public function getRoleInOrganization($organizationId): ?string
    {
        $membership = $this->organizations()
            ->where('organization_id', $organizationId)
            ->first();

        return $membership?->pivot?->role;
    }

    /**
     * Check if user has role in organization.
     */
    public function hasRoleInOrganization($organizationId, $role): bool
    {
        return $this->getRoleInOrganization($organizationId) === $role;
    }

    /**
     * Check if user can access organization.
     */
    public function canAccessOrganization($organizationId): bool
    {
        return $this->organizations()
            ->where('organization_id', $organizationId)
            ->where('organization_users.status', 'active')
            ->exists();
    }

    /**
     * Get user's tasks for today.
     */
    public function getTodayTasks()
    {
        return $this->assignedTasks()
            ->where('due_date', now()->toDateString())
            ->orWhere('time_slot', '!=', null)
            ->orderBy('time_slot')
            ->get();
    }

    /**
     * Get user's overdue tasks.
     */
    public function getOverdueTasks()
    {
        return $this->assignedTasks()
            ->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get initials for avatar.
     */
    public function getInitialsAttribute(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';

        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return substr($initials, 0, 2);
    }

    /**
     * Get avatar URL or generate one.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Generate avatar using UI Avatars or similar service
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name)
               . '&color=fff&background=3b82f6';
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
