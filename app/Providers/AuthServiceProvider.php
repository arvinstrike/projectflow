<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\Milestone;
use App\Policies\OrganizationPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Project::class => ProjectPolicy::class,
        // Task::class => TaskPolicy::class, // Can be added later if needed
        // Milestone::class => MilestonePolicy::class, // Can be added later if needed
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates if needed
        Gate::define('access-organization', function ($user, $organizationId) {
            return $user->canAccessOrganization($organizationId);
        });

        Gate::define('manage-organization-users', function ($user, $organization) {
            $userRole = $user->getRoleInOrganization($organization->id);
            return in_array($userRole, ['owner', 'admin']);
        });

        Gate::define('create-projects', function ($user, $organization) {
            $userRole = $user->getRoleInOrganization($organization->id);
            return in_array($userRole, ['owner', 'admin', 'project_manager']);
        });

        Gate::define('view-analytics', function ($user, $organization) {
            $userRole = $user->getRoleInOrganization($organization->id);
            return in_array($userRole, ['owner', 'admin', 'project_manager']);
        });

        // Super admin gate (for future use)
        Gate::define('super-admin', function ($user) {
            return $user->email === 'admin@projectflow.com'; // Change as needed
        });

        // Role-based gates
        Gate::define('organization-owner', function ($user, $organizationId) {
            return $user->getRoleInOrganization($organizationId) === 'owner';
        });

        Gate::define('organization-admin', function ($user, $organizationId) {
            $role = $user->getRoleInOrganization($organizationId);
            return in_array($role, ['owner', 'admin']);
        });

        Gate::define('project-manager', function ($user, $organizationId) {
            $role = $user->getRoleInOrganization($organizationId);
            return in_array($role, ['owner', 'admin', 'project_manager']);
        });

        // Feature-specific gates based on organization plan
        Gate::define('advanced-analytics', function ($user, $organization) {
            return in_array($organization->plan, ['premium', 'enterprise']);
        });

        Gate::define('api-access', function ($user, $organization) {
            return in_array($organization->plan, ['premium', 'enterprise']);
        });

        Gate::define('gantt-charts', function ($user, $organization) {
            return in_array($organization->plan, ['premium', 'enterprise']);
        });

        Gate::define('time-tracking', function ($user, $organization) {
            return in_array($organization->plan, ['premium', 'enterprise']);
        });

        Gate::define('custom-fields', function ($user, $organization) {
            return in_array($organization->plan, ['basic', 'premium', 'enterprise']);
        });

        Gate::define('white-label', function ($user, $organization) {
            return $organization->plan === 'enterprise';
        });

        Gate::define('sso', function ($user, $organization) {
            return $organization->plan === 'enterprise';
        });

        // Plan limits gates
        Gate::define('can-add-users', function ($user, $organization) {
            return $organization->canAddUsers();
        });

        Gate::define('can-add-projects', function ($user, $organization) {
            return $organization->canAddProjects();
        });

        // Task-specific gates
        Gate::define('assign-tasks', function ($user, $project) {
            // Project team members can assign tasks
            if ($project->users()->where('user_id', $user->id)->exists()) {
                return true;
            }

            // Organization admins can assign tasks
            $userRole = $user->getRoleInOrganization($project->organization_id);
            return in_array($userRole, ['owner', 'admin', 'project_manager']);
        });

        Gate::define('view-all-tasks', function ($user, $organization) {
            $userRole = $user->getRoleInOrganization($organization->id);
            return in_array($userRole, ['owner', 'admin', 'project_manager']);
        });

        // Time tracking gates
        Gate::define('track-time', function ($user, $task) {
            // Task assignee can track time
            if ($task->assignee_id === $user->id) {
                return true;
            }

            // Project team members can track time
            if ($task->project->users()->where('user_id', $user->id)->exists()) {
                return true;
            }

            return false;
        });

        Gate::define('view-time-reports', function ($user, $project) {
            // Project managers and above can view time reports
            if ($project->users()->where('user_id', $user->id)->wherePivot('role', 'manager')->exists()) {
                return true;
            }

            $userRole = $user->getRoleInOrganization($project->organization_id);
            return in_array($userRole, ['owner', 'admin', 'project_manager']);
        });

        // Billing gates
        Gate::define('view-billing', function ($user, $organization) {
            $userRole = $user->getRoleInOrganization($organization->id);
            return in_array($userRole, ['owner', 'admin']);
        });

        Gate::define('manage-billing', function ($user, $organization) {
            return $user->getRoleInOrganization($organization->id) === 'owner';
        });

        // Import/Export gates
        Gate::define('export-data', function ($user, $organization) {
            $userRole = $user->getRoleInOrganization($organization->id);
            return in_array($userRole, ['owner', 'admin']);
        });

        Gate::define('import-data', function ($user, $organization) {
            $userRole = $user->getRoleInOrganization($organization->id);
            return in_array($userRole, ['owner', 'admin', 'project_manager']);
        });
    }
}
