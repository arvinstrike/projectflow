<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can always view projects they have access to
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        // Check if user is project owner
        if ($user->id === $project->owner_id) {
            return true;
        }

        // Check if user is project team member
        if ($project->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check organization-level access
        return $this->hasOrganizationAccess($user, $project);
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user, Organization $organization): bool
    {
        // Check if user has access to organization
        if (!$user->canAccessOrganization($organization->id)) {
            return false;
        }

        $userRole = $user->getRoleInOrganization($organization->id);

        // Only owners, admins, and project managers can create projects
        return in_array($userRole, ['owner', 'admin', 'project_manager']);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        // Project owner can always update
        if ($user->id === $project->owner_id) {
            return true;
        }

        // Project managers can update
        if ($project->users()->where('user_id', $user->id)->wherePivot('role', 'manager')->exists()) {
            return true;
        }

        // Organization admins can update
        $userRole = $user->getRoleInOrganization($project->organization_id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        // Only project owner or organization owner/admin can delete
        if ($user->id === $project->owner_id) {
            return true;
        }

        $userRole = $user->getRoleInOrganization($project->organization_id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can restore the project.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the project.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        // Only organization owner can force delete
        $userRole = $user->getRoleInOrganization($project->organization_id);
        return $userRole === 'owner';
    }

    /**
     * Determine whether the user can view the project team.
     */
    public function viewTeam(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can manage the project team.
     */
    public function manageTeam(User $user, Project $project): bool
    {
        // Project owner can manage team
        if ($user->id === $project->owner_id) {
            return true;
        }

        // Project managers can manage team
        if ($project->users()->where('user_id', $user->id)->wherePivot('role', 'manager')->exists()) {
            return true;
        }

        // Organization admins can manage team
        $userRole = $user->getRoleInOrganization($project->organization_id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can create tasks in the project.
     */
    public function createTask(User $user, Project $project): bool
    {
        // Project team members can create tasks
        if ($project->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Organization members can create tasks if they have project access
        return $this->hasOrganizationAccess($user, $project);
    }

    /**
     * Determine whether the user can update tasks in the project.
     */
    public function updateTask(User $user, Project $project, $task = null): bool
    {
        // If specific task is provided, check if user is assignee
        if ($task && $task->assignee_id === $user->id) {
            return true;
        }

        // Project team members can update tasks
        if ($project->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Organization admins can update tasks
        return $this->hasOrganizationAccess($user, $project, ['owner', 'admin', 'project_manager']);
    }

    /**
     * Determine whether the user can delete tasks in the project.
     */
    public function deleteTask(User $user, Project $project, $task = null): bool
    {
        // Task creator can delete their own task
        if ($task && $task->created_by === $user->id) {
            return true;
        }

        // Project managers and above can delete tasks
        if ($project->users()->where('user_id', $user->id)->wherePivot('role', 'manager')->exists()) {
            return true;
        }

        // Organization admins can delete tasks
        return $this->hasOrganizationAccess($user, $project, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can create milestones in the project.
     */
    public function createMilestone(User $user, Project $project): bool
    {
        return $this->updateTask($user, $project);
    }

    /**
     * Determine whether the user can update milestones in the project.
     */
    public function updateMilestone(User $user, Project $project, $milestone = null): bool
    {
        return $this->updateTask($user, $project);
    }

    /**
     * Determine whether the user can delete milestones in the project.
     */
    public function deleteMilestone(User $user, Project $project, $milestone = null): bool
    {
        return $this->deleteTask($user, $project);
    }

    /**
     * Check if user has organization-level access to project.
     */
    protected function hasOrganizationAccess(User $user, Project $project, array $allowedRoles = null): bool
    {
        if (!$user->canAccessOrganization($project->organization_id)) {
            return false;
        }

        if ($allowedRoles) {
            $userRole = $user->getRoleInOrganization($project->organization_id);
            return in_array($userRole, $allowedRoles);
        }

        return true;
    }
}
