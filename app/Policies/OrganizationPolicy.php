<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own organizations
    }

    /**
     * Determine whether the user can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->canAccessOrganization($organization->id);
    }

    /**
     * Determine whether the user can create organizations.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create organizations
    }

    /**
     * Determine whether the user can update the organization.
     */
    public function update(User $user, Organization $organization): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        // Only organization owner can delete
        return $user->getRoleInOrganization($organization->id) === 'owner';
    }

    /**
     * Determine whether the user can restore the organization.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return $this->delete($user, $organization);
    }

    /**
     * Determine whether the user can permanently delete the organization.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return $this->delete($user, $organization);
    }

    /**
     * Determine whether the user can manage organization settings.
     */
    public function manageOrganization(User $user, Organization $organization): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can manage the organization team.
     */
    public function manageTeam(User $user, Organization $organization): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can invite users to the organization.
     */
    public function inviteUsers(User $user, Organization $organization): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin', 'project_manager']);
    }

    /**
     * Determine whether the user can remove users from the organization.
     */
    public function removeUsers(User $user, Organization $organization, User $targetUser = null): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);

        // Only owners and admins can remove users
        if (!in_array($userRole, ['owner', 'admin'])) {
            return false;
        }

        // If target user is specified, check additional constraints
        if ($targetUser) {
            $targetRole = $targetUser->getRoleInOrganization($organization->id);

            // Admins cannot remove owners
            if ($userRole === 'admin' && $targetRole === 'owner') {
                return false;
            }

            // Cannot remove self if you're the only owner
            if ($targetUser->id === $user->id && $targetRole === 'owner') {
                $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
                return $ownerCount > 1;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can change user roles in the organization.
     */
    public function changeUserRoles(User $user, Organization $organization, User $targetUser = null): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);

        // Only owners and admins can change roles
        if (!in_array($userRole, ['owner', 'admin'])) {
            return false;
        }

        // If target user is specified, check additional constraints
        if ($targetUser) {
            $targetRole = $targetUser->getRoleInOrganization($organization->id);

            // Admins cannot change owner roles
            if ($userRole === 'admin' && $targetRole === 'owner') {
                return false;
            }

            // Cannot change own role if you're the only owner
            if ($targetUser->id === $user->id && $targetRole === 'owner') {
                $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
                return $ownerCount > 1;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can view organization billing.
     */
    public function viewBilling(User $user, Organization $organization): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can manage organization billing.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        // Only organization owner can manage billing
        return $user->getRoleInOrganization($organization->id) === 'owner';
    }

    /**
     * Determine whether the user can view organization analytics.
     */
    public function viewAnalytics(User $user, Organization $organization): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin', 'project_manager']);
    }

    /**
     * Determine whether the user can export organization data.
     */
    public function exportData(User $user, Organization $organization): bool
    {
        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can access organization API.
     */
    public function accessApi(User $user, Organization $organization): bool
    {
        // Check if user has access to organization
        if (!$user->canAccessOrganization($organization->id)) {
            return false;
        }

        // Check if organization's plan supports API access
        return in_array($organization->plan, ['premium', 'enterprise']);
    }

    /**
     * Determine whether the user can create API tokens for the organization.
     */
    public function createApiTokens(User $user, Organization $organization): bool
    {
        if (!$this->accessApi($user, $organization)) {
            return false;
        }

        $userRole = $user->getRoleInOrganization($organization->id);
        return in_array($userRole, ['owner', 'admin']);
    }
}
