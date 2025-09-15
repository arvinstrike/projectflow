<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    /**
     * Show organization settings.
     */
    public function settings()
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('manageOrganization', $organization);

        return view('organization.settings', compact('organization'));
    }

    /**
     * Update organization settings.
     */
    public function updateSettings(Request $request)
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('manageOrganization', $organization);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'settings.timezone' => 'nullable|string|max:50',
            'settings.date_format' => 'nullable|string|max:20',
            'settings.time_format' => 'nullable|in:12,24',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $organization->update($validated);

        return redirect()->back()->with('success', 'Organization settings updated successfully.');
    }

    /**
     * Show team management.
     */
    public function team()
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('manageTeam', $organization);

        $teamMembers = $organization->users()
            ->withPivot(['role', 'status', 'joined_at'])
            ->orderBy('organization_users.role')
            ->orderBy('name')
            ->get();

        $availableRoles = [
            'owner' => 'Owner',
            'admin' => 'Admin',
            'project_manager' => 'Project Manager',
            'member' => 'Member',
            'viewer' => 'Viewer',
        ];

        return view('organization.team', compact('organization', 'teamMembers', 'availableRoles'));
    }

    /**
     * Invite user to organization.
     */
    public function inviteUser(Request $request)
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('manageTeam', $organization);

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,project_manager,member,viewer',
        ]);

        // Check if organization can add more users
        if (!$organization->canAddUsers()) {
            return redirect()->back()
                ->with('error', 'You have reached the maximum number of users for your plan.');
        }

        // Check if user is already in organization
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && $organization->users()->where('user_id', $existingUser->id)->exists()) {
            return redirect()->back()
                ->with('error', 'User is already a member of this organization.');
        }

        try {
            $organization->inviteUser($validated['email'], $validated['role']);

            return redirect()->back()
                ->with('success', 'Invitation sent successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to send invitation. Please try again.');
        }
    }

    /**
     * Update user role in organization.
     */
    public function updateUserRole(Request $request, User $user)
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('manageTeam', $organization);

        $validated = $request->validate([
            'role' => 'required|in:owner,admin,project_manager,member,viewer',
        ]);

        // Check if user is in organization
        if (!$organization->users()->where('user_id', $user->id)->exists()) {
            return redirect()->back()
                ->with('error', 'User is not a member of this organization.');
        }

        // Prevent removing the last owner
        $currentRole = $organization->users()->where('user_id', $user->id)->first()->pivot->role;
        if ($currentRole === 'owner' && $validated['role'] !== 'owner') {
            $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot change role. Organization must have at least one owner.');
            }
        }

        $organization->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return redirect()->back()
            ->with('success', "Updated {$user->name}'s role successfully.");
    }

    /**
     * Remove user from organization.
     */
    public function removeUser(User $user)
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('manageTeam', $organization);

        // Check if user is in organization
        $membership = $organization->users()->where('user_id', $user->id)->first();
        if (!$membership) {
            return redirect()->back()
                ->with('error', 'User is not a member of this organization.');
        }

        // Prevent removing the last owner
        if ($membership->pivot->role === 'owner') {
            $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot remove the last owner from the organization.');
            }
        }

        // Check if user owns any projects
        $ownedProjects = $organization->projects()->where('owner_id', $user->id)->count();
        if ($ownedProjects > 0) {
            return redirect()->back()
                ->with('error', "Cannot remove user who owns {$ownedProjects} project(s). Please transfer ownership first.");
        }

        $organization->removeUser($user);

        // If user being removed is current user, redirect to org selection
        if ($user->id === Auth::id()) {
            session()->forget('current_organization_id');
            return redirect()->route('organization.select')
                ->with('success', 'You have been removed from the organization.');
        }

        return redirect()->back()
            ->with('success', "Removed {$user->name} from the organization.");
    }

    /**
     * Show billing page.
     */
    public function billing()
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('manageOrganization', $organization);

        // Get billing information (placeholder)
        $billingInfo = [
            'current_plan' => $organization->plan,
            'trial_ends_at' => $organization->trial_ends_at,
            'is_on_trial' => $organization->isOnTrial(),
            'trial_days_remaining' => $organization->getTrialDaysRemaining(),
        ];

        $plans = [
            'free' => [
                'name' => 'Free',
                'price' => 0,
                'max_users' => 5,
                'max_projects' => 3,
                'features' => ['Basic project management', 'Task tracking', 'Email support'],
            ],
            'basic' => [
                'name' => 'Basic',
                'price' => 19,
                'max_users' => 25,
                'max_projects' => 25,
                'features' => ['All Free features', 'Advanced analytics', 'Priority support', 'Custom fields'],
            ],
            'premium' => [
                'name' => 'Premium',
                'price' => 39,
                'max_users' => 100,
                'max_projects' => 100,
                'features' => ['All Basic features', 'Gantt charts', 'Time tracking', 'API access'],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 99,
                'max_users' => 500,
                'max_projects' => 500,
                'features' => ['All Premium features', 'SSO', 'Advanced security', 'White-label'],
            ],
        ];

        return view('organization.billing', compact('organization', 'billingInfo', 'plans'));
    }

    /**
     * Search users in organization.
     */
    public function searchUsers(Request $request)
    {
        $organization = $this->getCurrentOrganization();
        $search = $request->get('q', '');

        $users = $organization->activeUsers()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                ];
            }),
        ]);
    }

    /**
     * Get current organization for the user.
     */
    protected function getCurrentOrganization(): ?Organization
    {
        $organizationId = session('current_organization_id');

        if (!$organizationId) {
            return null;
        }

        $user = Auth::user();
        return $user->organizations()
            ->where('organization_id', $organizationId)
            ->first();
    }
}
