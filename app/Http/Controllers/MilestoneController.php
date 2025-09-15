<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MilestoneController extends Controller
{
    /**
     * Display milestones for a specific project.
     */
    public function index(Project $project)
    {
        Gate::authorize('view', $project);

        $milestones = $project->milestones()
            ->withCount(['tasks', 'completedTasks', 'activeTasks'])
            ->ordered()
            ->get();

        return view('milestones.index', compact('project', 'milestones'));
    }

    /**
     * Show the form for creating a new milestone.
     */
    public function create(Project $project)
    {
        Gate::authorize('createMilestone', $project);

        return view('milestones.create', compact('project'));
    }

    /**
     * Store a newly created milestone.
     */
    public function store(Request $request, Project $project)
    {
        Gate::authorize('createMilestone', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $milestone = $project->milestones()->create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'milestone' => $milestone,
                'message' => 'Milestone created successfully.',
            ]);
        }

        return redirect()->route('milestones.show', [$project, $milestone])
            ->with('success', 'Milestone created successfully.');
    }

    /**
     * Display the specified milestone.
     */
    public function show(Project $project, Milestone $milestone)
    {
        Gate::authorize('view', $project);

        $milestone->load(['tasks.assignee']);
        $stats = $milestone->getStats();

        return view('milestones.show', compact('project', 'milestone', 'stats'));
    }

    /**
     * Show the form for editing the milestone.
     */
    public function edit(Project $project, Milestone $milestone)
    {
        Gate::authorize('updateMilestone', [$project, $milestone]);

        return view('milestones.edit', compact('project', 'milestone'));
    }

    /**
     * Update the specified milestone.
     */
    public function update(Request $request, Project $project, Milestone $milestone)
    {
        Gate::authorize('updateMilestone', [$project, $milestone]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,active,completed,cancelled',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $milestone->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'milestone' => $milestone->fresh(),
                'message' => 'Milestone updated successfully.',
            ]);
        }

        return redirect()->route('milestones.show', [$project, $milestone])
            ->with('success', 'Milestone updated successfully.');
    }

    /**
     * Remove the specified milestone.
     */
    public function destroy(Project $project, Milestone $milestone)
    {
        Gate::authorize('deleteMilestone', [$project, $milestone]);

        // Check if milestone has tasks
        if ($milestone->tasks()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete milestone that has tasks. Please move or delete the tasks first.');
        }

        $milestoneName = $milestone->name;
        $milestone->delete();

        return redirect()->route('milestones.index', $project)
            ->with('success', "Milestone '{$milestoneName}' deleted successfully.");
    }

    /**
     * Mark milestone as completed.
     */
    public function complete(Project $project, Milestone $milestone)
    {
        Gate::authorize('updateMilestone', [$project, $milestone]);

        $milestone->markAsCompleted();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone completed successfully.',
                'milestone' => $milestone->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Milestone completed successfully.');
    }

    /**
     * Reopen milestone.
     */
    public function reopen(Project $project, Milestone $milestone)
    {
        Gate::authorize('updateMilestone', [$project, $milestone]);

        $milestone->reopen();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone reopened successfully.',
                'milestone' => $milestone->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Milestone reopened successfully.');
    }

    /**
     * Reorder milestones.
     */
    public function reorder(Request $request, Project $project)
    {
        Gate::authorize('updateMilestone', [$project, new Milestone()]);

        $validated = $request->validate([
            'milestone_ids' => 'required|array',
            'milestone_ids.*' => 'exists:milestones,id',
        ]);

        foreach ($validated['milestone_ids'] as $index => $milestoneId) {
            Milestone::where('id', $milestoneId)
                ->where('project_id', $project->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Milestones reordered successfully.',
        ]);
    }
}
