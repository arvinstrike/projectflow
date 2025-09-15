<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the main dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        // Get dashboard statistics
        $stats = $this->getDashboardStats($user, $organization);

        // Get recent projects
        $recentProjects = $organization->projects()
            ->with(['owner', 'users'])
            ->accessibleBy($user)
            ->latest()
            ->limit(6)
            ->get();

        // Get user's tasks for today
        $todayTasks = $user->getTodayTasks()
            ->with(['project', 'milestone'])
            ->limit(10)
            ->get();

        // Get user's overdue tasks
        $overdueTasks = $user->getOverdueTasks()
            ->with(['project', 'milestone'])
            ->limit(5)
            ->get();

        // Get recent activity (placeholder for now)
        $recentActivity = collect(); // This would contain recent activities

        return view('dashboard.index', compact(
            'stats',
            'recentProjects',
            'todayTasks',
            'overdueTasks',
            'recentActivity',
            'organization'
        ));
    }

    /**
     * Show calendar view.
     */
    public function calendar(Request $request)
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Create a Carbon instance for the selected month
        $date = Carbon::createFromDate($year, $month, 1);

        // Get previous and next month for navigation
        $prevMonth = $date->copy()->subMonth();
        $nextMonth = $date->copy()->addMonth();

        // Get calendar data
        $daysInMonth = $date->daysInMonth;
        $firstDayOfWeek = $date->copy()->startOfMonth()->dayOfWeek;

        // Get tasks for this month
        $tasks = Task::whereHas('project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->assignedTo($user->id)
            ->where(function ($query) use ($date) {
                $query->whereBetween('due_date', [
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth()
                ])
                ->orWhereBetween('start_date', [
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth()
                ]);
            })
            ->with(['project', 'milestone'])
            ->get()
            ->groupBy(function ($task) {
                return $task->due_date ? $task->due_date->format('Y-m-d') :
                       ($task->start_date ? $task->start_date->format('Y-m-d') : null);
            });

        return view('dashboard.calendar', compact(
            'date',
            'prevMonth',
            'nextMonth',
            'daysInMonth',
            'firstDayOfWeek',
            'tasks'
        ));
    }

    /**
     * Show specific day tasks.
     */
    public function showDay($date)
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        $carbonDate = Carbon::parse($date);

        // Get tasks for this day
        $tasks = Task::whereHas('project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->assignedTo($user->id)
            ->where(function ($query) use ($carbonDate) {
                $query->where('due_date', $carbonDate->format('Y-m-d'))
                      ->orWhere('start_date', $carbonDate->format('Y-m-d'))
                      ->orWhereNotNull('time_slot');
            })
            ->with(['project', 'milestone'])
            ->orderBy('time_slot')
            ->orderBy('priority', 'desc')
            ->get();

        // Get completion statistics
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $stats = [
            'total' => $totalTasks,
            'completed' => $completedTasks,
            'percent' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
        ];

        return view('dashboard.day', compact('tasks', 'stats', 'carbonDate'));
    }

    /**
     * Show analytics dashboard.
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return redirect()->route('organization.select');
        }

        $period = $request->input('period', '30'); // days
        $startDate = now()->subDays($period);

        // Get analytics data
        $analytics = [
            'task_completion_trend' => $this->getTaskCompletionTrend($organization, $startDate),
            'project_progress' => $this->getProjectProgress($organization),
            'team_productivity' => $this->getTeamProductivity($organization, $startDate),
            'overdue_analysis' => $this->getOverdueAnalysis($organization),
            'time_tracking' => $this->getTimeTrackingData($organization, $startDate),
        ];

        return view('dashboard.analytics', compact('analytics', 'period'));
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

    /**
     * Get dashboard statistics.
     */
    protected function getDashboardStats(User $user, Organization $organization): array
    {
        // Projects stats
        $totalProjects = $organization->projects()->accessibleBy($user)->count();
        $activeProjects = $organization->projects()
            ->accessibleBy($user)
            ->active()
            ->count();
        $completedProjects = $organization->projects()
            ->accessibleBy($user)
            ->withStatus('completed')
            ->count();

        // Tasks stats
        $totalTasks = Task::whereHas('project', function ($query) use ($organization, $user) {
            $query->where('organization_id', $organization->id)
                  ->accessibleBy($user);
        })->count();

        $myTasks = Task::whereHas('project', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })->assignedTo($user->id)->count();

        $completedTasks = Task::whereHas('project', function ($query) use ($organization, $user) {
            $query->where('organization_id', $organization->id)
                  ->accessibleBy($user);
        })->where('status', 'completed')->count();

        $overdueTasks = Task::whereHas('project', function ($query) use ($organization, $user) {
            $query->where('organization_id', $organization->id)
                  ->accessibleBy($user);
        })->overdue()->count();

        // Team stats
        $teamMembers = $organization->activeUsers()->count();

        // Recent activity count
        $recentActivity = 0; // Placeholder

        return [
            'projects' => [
                'total' => $totalProjects,
                'active' => $activeProjects,
                'completed' => $completedProjects,
            ],
            'tasks' => [
                'total' => $totalTasks,
                'mine' => $myTasks,
                'completed' => $completedTasks,
                'overdue' => $overdueTasks,
            ],
            'team_members' => $teamMembers,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * Get task completion trend data.
     */
    protected function getTaskCompletionTrend(Organization $organization, Carbon $startDate): array
    {
        // This would return data for charting task completion over time
        // Placeholder implementation
        return [];
    }

    /**
     * Get project progress data.
     */
    protected function getProjectProgress(Organization $organization): array
    {
        return $organization->projects()
            ->active()
            ->with(['tasks'])
            ->get()
            ->map(function ($project) {
                return [
                    'name' => $project->name,
                    'progress' => $project->progress,
                    'tasks_completed' => $project->tasks()->where('status', 'completed')->count(),
                    'total_tasks' => $project->tasks()->count(),
                ];
            })
            ->toArray();
    }

    /**
     * Get team productivity data.
     */
    protected function getTeamProductivity(Organization $organization, Carbon $startDate): array
    {
        return $organization->activeUsers()
            ->withCount([
                'assignedTasks as completed_tasks' => function ($query) use ($startDate) {
                    $query->where('status', 'completed')
                          ->where('completed_at', '>=', $startDate);
                }
            ])
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'completed_tasks' => $user->completed_tasks,
                ];
            })
            ->toArray();
    }

    /**
     * Get overdue analysis data.
     */
    protected function getOverdueAnalysis(Organization $organization): array
    {
        $overdueTasks = Task::whereHas('project', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })->overdue()->count();

        $totalActiveTasks = Task::whereHas('project', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })->active()->count();

        return [
            'overdue_count' => $overdueTasks,
            'overdue_percentage' => $totalActiveTasks > 0 ?
                round(($overdueTasks / $totalActiveTasks) * 100, 1) : 0,
        ];
    }

    /**
     * Get time tracking data.
     */
    protected function getTimeTrackingData(Organization $organization, Carbon $startDate): array
    {
        // This would analyze actual vs estimated time
        // Placeholder implementation
        return [
            'total_estimated_hours' => 0,
            'total_actual_hours' => 0,
            'accuracy_percentage' => 0,
        ];
    }
}
