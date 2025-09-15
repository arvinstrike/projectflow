<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    /**
     * Display tasks for a specific project.
     */
    public function index(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $query = $project->tasks()->with(['assignee', 'milestone', 'creator']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assignee')) {
            $query->where('assignee_id', $request->assignee);
        }

        if ($request->filled('milestone')) {
            $query->where('milestone_id', $request->milestone);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'sort_order');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortBy === 'priority') {
            $query->byPriority();
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $tasks = $query->parentOnly()->paginate(20)->appends($request->query());

        // Get filter options
        $assignees = $project->users;
        $milestones = $project->milestones;

        return view('tasks.index', compact(
            'project',
            'tasks',
            'assignees',
            'milestones'
        ));
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(Request $request, Project $project)
    {
        Gate::authorize('createTask', $project);

        $milestones = $project->milestones()->active()->get();
        $assignees = $project->users;
        $parentTasks = $project->tasks()->parentOnly()->active()->get();

        return view('tasks.create', compact('project', 'milestones', 'assignees', 'parentTasks'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request, Project $project)
    {
        Gate::authorize('createTask', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|exists:milestones,id',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => 'required|in:todo,in_progress,in_review,blocked,completed,cancelled',
            'priority' => 'required|in:low,medium,high,critical',
            'type' => 'required|in:task,bug,feature,epic',
            'estimated_hours' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'time_slot' => 'nullable|date_format:H:i',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Verify milestone belongs to project
        if ($validated['milestone_id']) {
            $milestone = Milestone::find($validated['milestone_id']);
            if ($milestone->project_id !== $project->id) {
                return back()->withErrors(['milestone_id' => 'Invalid milestone selected.']);
            }
        }

        // Verify assignee has access to project
        if ($validated['assignee_id']) {
            $assignee = User::find($validated['assignee_id']);
            if (!$project->users()->where('user_id', $assignee->id)->exists()) {
                return back()->withErrors(['assignee_id' => 'Assignee must be a project team member.']);
            }
        }

        // Verify parent task belongs to project
        if ($validated['parent_id']) {
            $parentTask = Task::find($validated['parent_id']);
            if ($parentTask->project_id !== $project->id) {
                return back()->withErrors(['parent_id' => 'Invalid parent task selected.']);
            }
        }

        $task = $project->tasks()->create([
            ...$validated,
            'created_by' => Auth::id(),
            'tags' => $validated['tags'] ? json_encode($validated['tags']) : null,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'task' => $task->load(['assignee', 'milestone', 'creator']),
                'message' => 'Task created successfully.',
            ]);
        }

        return redirect()->route('tasks.show', [$project, $task])
            ->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified task.
     */
    public function show(Project $project, Task $task)
    {
        Gate::authorize('view', $project);

        $task->load([
            'assignee',
            'creator',
            'milestone',
            'parent',
            'subtasks.assignee',
        ]);

        // Get task activity/comments (placeholder for now)
        $activities = collect();

        return view('tasks.show', compact('project', 'task', 'activities'));
    }

    /**
     * Show the form for editing the task.
     */
    public function edit(Project $project, Task $task)
    {
        Gate::authorize('updateTask', [$project, $task]);

        $milestones = $project->milestones()->active()->get();
        $assignees = $project->users;
        $parentTasks = $project->tasks()
            ->parentOnly()
            ->active()
            ->where('id', '!=', $task->id)
            ->get();

        return view('tasks.edit', compact('project', 'task', 'milestones', 'assignees', 'parentTasks'));
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Project $project, Task $task)
    {
        Gate::authorize('updateTask', [$project, $task]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|exists:milestones,id',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => 'required|in:todo,in_progress,in_review,blocked,completed,cancelled',
            'priority' => 'required|in:low,medium,high,critical',
            'type' => 'required|in:task,bug,feature,epic',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'time_slot' => 'nullable|date_format:H:i',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
            'progress' => 'nullable|numeric|min:0|max:100',
        ]);

        // Validation logic similar to store method
        if ($validated['milestone_id']) {
            $milestone = Milestone::find($validated['milestone_id']);
            if ($milestone->project_id !== $project->id) {
                return back()->withErrors(['milestone_id' => 'Invalid milestone selected.']);
            }
        }

        if ($validated['assignee_id']) {
            $assignee = User::find($validated['assignee_id']);
            if (!$project->users()->where('user_id', $assignee->id)->exists()) {
                return back()->withErrors(['assignee_id' => 'Assignee must be a project team member.']);
            }
        }

        if ($validated['parent_id']) {
            $parentTask = Task::find($validated['parent_id']);
            if ($parentTask->project_id !== $project->id || $parentTask->id === $task->id) {
                return back()->withErrors(['parent_id' => 'Invalid parent task selected.']);
            }
        }

        $task->update([
            ...$validated,
            'tags' => $validated['tags'] ? json_encode($validated['tags']) : null,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'task' => $task->fresh(['assignee', 'milestone', 'creator']),
                'message' => 'Task updated successfully.',
            ]);
        }

        return redirect()->route('tasks.show', [$project, $task])
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Project $project, Task $task)
    {
        Gate::authorize('deleteTask', [$project, $task]);

        $taskTitle = $task->title;
        $task->delete();

        return redirect()->route('tasks.index', $project)
            ->with('success', "Task '{$taskTitle}' deleted successfully.");
    }

    /**
     * Show user's assigned tasks.
     */
    public function myTasks(Request $request)
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        $query = Task::whereHas('project', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->assignedTo($user->id)
            ->with(['project', 'milestone']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
        }

        $tasks = $query->orderBy('due_date', 'asc')
            ->orderBy('priority', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $projects = $organization->projects()->accessibleBy($user)->get();

        return view('tasks.my', compact('tasks', 'projects'));
    }

    /**
     * Complete a task.
     */
    public function complete(Project $project, Task $task)
    {
        Gate::authorize('updateTask', [$project, $task]);

        $task->markAsCompleted();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task completed successfully.',
                'task' => $task->fresh(['assignee', 'milestone']),
            ]);
        }

        return redirect()->back()->with('success', 'Task completed successfully.');
    }

    /**
     * Reopen a task.
     */
    public function reopen(Project $project, Task $task)
    {
        Gate::authorize('updateTask', [$project, $task]);

        $task->reopen();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task reopened successfully.',
                'task' => $task->fresh(['assignee', 'milestone']),
            ]);
        }

        return redirect()->back()->with('success', 'Task reopened successfully.');
    }

    /**
     * Assign task to user.
     */
    public function assign(Request $request, Project $project, Task $task)
    {
        Gate::authorize('updateTask', [$project, $task]);

        $validated = $request->validate([
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        if ($validated['assignee_id']) {
            $assignee = User::find($validated['assignee_id']);
            if (!$project->users()->where('user_id', $assignee->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignee must be a project team member.',
                ], 422);
            }
        }

        $task->update(['assignee_id' => $validated['assignee_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully.',
            'task' => $task->fresh(['assignee', 'milestone']),
        ]);
    }

    /**
     * Update task status via AJAX.
     */
    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status' => 'required|in:todo,in_progress,in_review,blocked,completed,cancelled',
        ]);

        Gate::authorize('updateTask', [$task->project, $task]);

        $task->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully.',
            'task' => $task->fresh(['assignee', 'milestone']),
        ]);
    }

    /**
     * Update task priority via AJAX.
     */
    public function updatePriority(Request $request, Task $task)
    {
        $request->validate([
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        Gate::authorize('updateTask', [$task->project, $task]);

        $task->update(['priority' => $request->priority]);

        return response()->json([
            'success' => true,
            'message' => 'Task priority updated successfully.',
            'task' => $task->fresh(['assignee', 'milestone']),
        ]);
    }

    /**
     * Search tasks via AJAX.
     */
    public function searchTasks(Request $request)
    {
        $organization = $this->getCurrentOrganization();
        $search = $request->get('q', '');

        $tasks = Task::whereHas('project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->search($search)
            ->with(['project', 'assignee'])
            ->limit(10)
            ->get();

        return response()->json([
            'tasks' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project_name' => $task->project->name,
                    'assignee_name' => $task->assignee?->name,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'url' => route('tasks.show', [$task->project, $task]),
                ];
            }),
        ]);
    }

    /**
     * Log time for a task.
     */
    public function logTime(Request $request, Project $project, Task $task)
    {
        Gate::authorize('updateTask', [$project, $task]);

        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1|max:24',
            'description' => 'nullable|string|max:255',
        ]);

        // Add to actual hours
        $task->increment('actual_hours', $validated['hours']);

        // Log time entry (placeholder - would need time_logs table)
        // TimeLog::create([...]);

        return response()->json([
            'success' => true,
            'message' => 'Time logged successfully.',
            'task' => $task->fresh(),
        ]);
    }

    /**
     * Reorder tasks.
     */
    public function reorder(Request $request, Project $project)
    {
        Gate::authorize('updateTask', [$project, new Task()]);

        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        foreach ($validated['task_ids'] as $index => $taskId) {
            Task::where('id', $taskId)
                ->where('project_id', $project->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tasks reordered successfully.',
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
