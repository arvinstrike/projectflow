<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        $query = $organization->projects()->accessibleBy($user);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        if (in_array($sortBy, ['name', 'status', 'priority', 'deadline', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $projects = $query->with(['owner', 'users', 'milestones', 'tasks'])
            ->paginate(12)
            ->appends($request->query());

        // Get filter options
        $statusOptions = [
            'planning' => 'Planning',
            'active' => 'Active',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $priorityOptions = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];

        return view('projects.index', compact(
            'projects',
            'statusOptions',
            'priorityOptions',
            'organization'
        ));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        // Check if organization can add more projects
        if (!$organization->canAddProjects()) {
            return redirect()->route('projects.index')
                ->with('error', 'You have reached the maximum number of projects for your plan.');
        }

        Gate::authorize('create', [Project::class, $organization]);

        return view('projects.create', compact('organization'));
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        Gate::authorize('create', [Project::class, $organization]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'priority' => 'required|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'deadline' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project = $organization->projects()->create([
            ...$validated,
            'owner_id' => Auth::id(),
            'color' => $validated['color'] ?? '#3b82f6',
        ]);

        // Add creator as project manager
        $project->addTeamMember(Auth::user(), 'manager');

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load([
            'owner',
            'users',
            'milestones.tasks',
            'tasks' => function ($query) {
                $query->whereNull('parent_id')->with('subtasks');
            }
        ]);

        // Get project statistics
        $stats = $project->getStats();

        // Get recent activity (placeholder)
        $recentActivity = collect();

        // Get team members with their roles
        $teamMembers = $project->users()
            ->withPivot(['role', 'joined_at'])
            ->get();

        return view('projects.show', compact(
            'project',
            'stats',
            'recentActivity',
            'teamMembers'
        ));
    }

    /**
     * Show the form for editing the project.
     */
    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'deadline' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully.',
                'project' => $project->fresh(),
            ]);
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        $projectName = $project->name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project '{$projectName}' deleted successfully.");
    }

    /**
     * Show project team management.
     */
    public function team(Project $project)
    {
        Gate::authorize('viewTeam', $project);

        $project->load(['users', 'organization.users']);

        $teamMembers = $project->users()
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->get();

        // Get organization members who are not in this project
        $availableUsers = $project->organization->activeUsers()
            ->whereNotIn('users.id', $teamMembers->pluck('id'))
            ->get();

        return view('projects.team', compact('project', 'teamMembers', 'availableUsers'));
    }

    /**
     * Add team member to project.
     */
    public function addTeamMember(Request $request, Project $project)
    {
        Gate::authorize('manageTeam', $project);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,member,viewer',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Check if user is in the organization
        if (!$user->canAccessOrganization($project->organization_id)) {
            return redirect()->back()
                ->with('error', 'User is not a member of this organization.');
        }

        // Check if user is already in the project
        if ($project->users()->where('user_id', $user->id)->exists()) {
            return redirect()->back()
                ->with('error', 'User is already a member of this project.');
        }

        $project->addTeamMember($user, $validated['role']);

        return redirect()->back()
            ->with('success', "Added {$user->name} to the project team.");
    }

    /**
     * Update team member role.
     */
    public function updateTeamMember(Request $request, Project $project, User $user)
    {
        Gate::authorize('manageTeam', $project);

        $validated = $request->validate([
            'role' => 'required|in:manager,member,viewer',
        ]);

        $project->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return redirect()->back()
            ->with('success', "Updated {$user->name}'s role successfully.");
    }

    /**
     * Remove team member from project.
     */
    public function removeTeamMember(Project $project, User $user)
    {
        Gate::authorize('manageTeam', $project);

        // Don't allow removing the project owner
        if ($project->owner_id === $user->id) {
            return redirect()->back()
                ->with('error', 'Cannot remove the project owner.');
        }

        $project->removeTeamMember($user);

        return redirect()->back()
            ->with('success', "Removed {$user->name} from the project team.");
    }

    /**
     * Duplicate project.
     */
    public function duplicate(Project $project)
    {
        Gate::authorize('create', [Project::class, $project->organization]);

        $newProject = $project->organization->projects()->create([
            'owner_id' => Auth::id(),
            'name' => $project->name . ' (Copy)',
            'description' => $project->description,
            'color' => $project->color,
            'priority' => $project->priority,
            'budget' => $project->budget,
            'settings' => $project->settings,
        ]);

        // Add creator as project manager
        $newProject->addTeamMember(Auth::user(), 'manager');

        return redirect()->route('projects.show', $newProject)
            ->with('success', 'Project duplicated successfully.');
    }

    /**
     * Archive project.
     */
    public function archive(Project $project)
    {
        Gate::authorize('update', $project);

        $project->update(['status' => 'completed']);

        return redirect()->route('projects.index')
            ->with('success', 'Project archived successfully.');
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
