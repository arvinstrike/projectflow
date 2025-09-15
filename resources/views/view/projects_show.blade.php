@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="space-y-6">
    <!-- Project Header -->
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="sm:flex sm:items-start sm:justify-between">
                <div class="sm:flex sm:items-start space-x-4">
                    <div class="w-3 h-16 rounded-full flex-shrink-0" style="background-color: {{ $project->color }}"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center space-x-3">
                            <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium status-{{ $project->status }}">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium priority-{{ $project->priority }}">
                                {{ ucfirst($project->priority) }}
                            </span>
                        </div>
                        <div class="mt-1 flex items-center text-sm text-gray-500 space-x-4">
                            <span>{{ $project->code }}</span>
                            <span>•</span>
                            <span>Owner: {{ $project->owner->name }}</span>
                            <span>•</span>
                            <span>Created {{ $project->created_at->format('M j, Y') }}</span>
                        </div>
                        @if($project->description)
                        <p class="mt-3 text-gray-600">{{ $project->description }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:flex-shrink-0 sm:items-center space-x-3">
                    <a href="{{ route('projects.edit', $project) }}"
                       class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="m2.695 14.763-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.886L17.5 5.501a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z" />
                        </svg>
                        Edit
                    </a>
                    <a href="{{ route('tasks.create', $project) }}"
                       class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                        </svg>
                        New Task
                    </a>
                </div>
            </div>

            <!-- Project Meta Info -->
            <div class="mt-6 grid grid-cols-2 gap-6 sm:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Progress</dt>
                    <dd class="mt-1">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                <div class="h-2 rounded-full" style="width: {{ $stats['progress'] }}%; background-color: {{ $project->color }}"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $stats['progress'] }}%</span>
                        </div>
                    </dd>
                </div>

                @if($project->deadline)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Deadline</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $project->deadline->format('M j, Y') }}
                        @if($project->isOverdue())
                        <span class="text-red-600 font-medium">(Overdue)</span>
                        @elseif($project->deadline->diffInDays() <= 7)
                        <span class="text-yellow-600 font-medium">(Due soon)</span>
                        @endif
                    </dd>
                </div>
                @endif

                @if($project->budget)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Budget</dt>
                    <dd class="mt-1 text-sm text-gray-900">${{ number_format($project->budget, 2) }}</dd>
                </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500">Team Size</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $stats['team_members'] }} members</dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Tasks</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['total_tasks'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['completed_tasks'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['in_progress_tasks'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['overdue_tasks'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Tasks & Milestones -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Milestones -->
            @if($project->milestones->count() > 0)
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Milestones</h3>
                        <a href="{{ route('milestones.create', $project) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Add milestone
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($project->milestones->take(5) as $milestone)
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center min-w-0 flex-1">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <a href="{{ route('milestones.show', [$project, $milestone]) }}" class="hover:text-indigo-600">
                                            {{ $milestone->name }}
                                        </a>
                                    </p>
                                    @if($milestone->due_date)
                                    <p class="text-sm text-gray-500">
                                        Due {{ $milestone->due_date->format('M j, Y') }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                            <div class="ml-4 flex-shrink-0 flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-{{ $milestone->status }}">
                                    {{ ucfirst($milestone->status) }}
                                </span>
                                <div class="text-sm text-gray-500">
                                    {{ $milestone->formatted_progress }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $milestone->progress }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($project->milestones->count() > 5)
                <div class="bg-gray-50 px-4 py-3">
                    <div class="text-sm">
                        <a href="{{ route('milestones.index', $project) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                            View all {{ $project->milestones->count() }} milestones
                        </a>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Recent Tasks -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Tasks</h3>
                        <a href="{{ route('tasks.index', $project) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            View all tasks
                        </a>
                    </div>
                </div>
                <div class="flow-root">
                    @if($project->tasks->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($project->tasks->take(8) as $task)
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center min-w-0 flex-1">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <a href="{{ route('tasks.show', [$project, $task]) }}" class="hover:text-indigo-600">
                                                {{ $task->title }}
                                            </a>
                                        </p>
                                        <div class="flex items-center mt-1 text-sm text-gray-500 space-x-2">
                                            @if($task->assignee)
                                            <span>{{ $task->assignee->name }}</span>
                                            <span>•</span>
                                            @endif
                                            @if($task->due_date)
                                            <span>Due {{ $task->due_date->format('M j') }}</span>
                                            <span>•</span>
                                            @endif
                                            <span class="priority-{{ $task->priority }} px-2 py-1 rounded-full text-xs">
                                                {{ ucfirst($task->priority) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-{{ $task->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">No tasks yet</p>
                        <div class="mt-4">
                            <a href="{{ route('tasks.create', $project) }}"
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Create first task
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Team Members -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Team</h3>
                        <a href="{{ route('projects.team', $project) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Manage
                        </a>
                    </div>
                </div>
                <div class="flow-root">
                    <ul class="divide-y divide-gray-200">
                        @foreach($teamMembers->take(6) as $member)
                        <li class="px-4 py-3 sm:px-6">
                            <div class="flex items-center">
                                <img class="h-8 w-8 rounded-full" src="{{ $member->avatar_url }}" alt="{{ $member->name }}">
                                <div class="ml-3 min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $member->name }}</p>
                                    <p class="text-sm text-gray-500 truncate">{{ ucfirst(str_replace('_', ' ', $member->pivot->role)) }}</p>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @if($teamMembers->count() > 6)
                    <div class="bg-gray-50 px-4 py-3">
                        <div class="text-sm">
                            <a href="{{ route('projects.team', $project) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                View all {{ $teamMembers->count() }} members
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
                </div>
                <div class="flow-root">
                    @if($recentActivity->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($recentActivity as $activity)
                        <li class="px-4 py-3 sm:px-6">
                            <!-- Activity items would go here -->
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">No recent activity</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
