@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                My Tasks
            </h1>
            <p class="mt-2 text-sm text-gray-700">
                Tasks assigned to you across all projects
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                        name="status"
                        id="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        @foreach(\App\Models\Task::getStatusOptions() as $value => $label)
                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority Filter -->
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                    <select
                        name="priority"
                        id="priority"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Priorities</option>
                        @foreach(\App\Models\Task::getPriorityOptions() as $value => $label)
                        <option value="{{ $value }}" {{ request('priority') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Project Filter -->
                <div>
                    <label for="project" class="block text-sm font-medium text-gray-700">Project</label>
                    <select
                        name="project"
                        id="project"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ request('project') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-end space-x-3">
                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Apply Filters
                    </button>
                    <a
                        href="{{ route('tasks.my') }}"
                        class="inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks List -->
    @if($tasks->count() > 0)
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($tasks as $task)
            <li>
                <div class="px-4 py-4 sm:px-6 hover:bg-gray-50" x-data="{
                    status: '{{ $task->status }}',
                    updating: false,
                    updateStatus(newStatus) {
                        if (this.updating) return;
                        this.updating = true;

                        fetch('{{ route('api.tasks.status', $task) }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.status = newStatus;
                                showNotification(data.message, 'success');

                                // Reload page if filtering by status
                                if ('{{ request('status') }}') {
                                    setTimeout(() => window.location.reload(), 1000);
                                }
                            } else {
                                showNotification('Failed to update task status', 'error');
                            }
                        })
                        .catch(() => {
                            showNotification('An error occurred', 'error');
                        })
                        .finally(() => {
                            this.updating = false;
                        });
                    }
                }">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center min-w-0 flex-1">
                            <!-- Status Checkbox -->
                            <div class="flex-shrink-0 mr-4">
                                <input
                                    type="checkbox"
                                    :checked="status === 'completed'"
                                    @change="updateStatus($event.target.checked ? 'completed' : 'todo')"
                                    :disabled="updating"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-medium text-gray-900 truncate" :class="{ 'line-through text-gray-500': status === 'completed' }">
                                        <a href="{{ route('tasks.show', [$task->project, $task]) }}" class="hover:text-indigo-600">
                                            {{ $task->title }}
                                        </a>
                                    </h3>
                                </div>

                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 rounded-full" style="background-color: {{ $task->project->color }}"></div>
                                        <span>{{ $task->project->name }}</span>
                                    </div>

                                    @if($task->milestone)
                                    <span>• {{ $task->milestone->name }}</span>
                                    @endif

                                    @if($task->due_date)
                                    <span class="flex items-center {{ $task->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                                        <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Due {{ $task->due_date->format('M j') }}
                                        @if($task->isOverdue())
                                        ({{ $task->due_date->diffForHumans() }})
                                        @endif
                                    </span>
                                    @endif

                                    @if($task->estimated_hours)
                                    <span class="flex items-center">
                                        <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $task->formatted_estimated_time }}
                                    </span>
                                    @endif
                                </div>

                                @if($task->description)
                                <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $task->description }}</p>
                                @endif

                                @if($task->tags)
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($task->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $tag }}
                                    </span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="ml-4 flex-shrink-0 flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium priority-{{ $task->priority }}">
                                {{ ucfirst($task->priority) }}
                            </span>

                            <span :class="{
                                'bg-gray-100 text-gray-800': status === 'todo',
                                'bg-blue-100 text-blue-800': status === 'in_progress',
                                'bg-purple-100 text-purple-800': status === 'in_review',
                                'bg-red-100 text-red-800': status === 'blocked',
                                'bg-green-100 text-green-800': status === 'completed',
                                'bg-red-100 text-red-800': status === 'cancelled'
                            }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                <span x-show="!updating" x-text="status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                <span x-show="updating" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-1 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Updating...
                                </span>
                            </span>

                            <!-- Quick Actions -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center p-1 text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>

                                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
                                    <a href="{{ route('tasks.show', [$task->project, $task]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        View Details
                                    </a>
                                    <a href="{{ route('tasks.edit', [$task->project, $task]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Edit Task
                                    </a>
                                    <a href="{{ route('projects.show', $task->project) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        View Project
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $tasks->links() }}
    </div>

    @else
    <!-- Empty State -->
    <div class="bg-white shadow rounded-lg">
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No tasks found</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if(request()->hasAny(['status', 'priority', 'project']))
                Try adjusting your filters to see more tasks.
                @else
                You don't have any tasks assigned to you yet.
                @endif
            </p>
            <div class="mt-6">
                @if(request()->hasAny(['status', 'priority', 'project']))
                <a href="{{ route('tasks.my') }}"
                   class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Clear Filters
                </a>
                @else
                <a href="{{ route('projects.index') }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    View Projects
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Stats -->
    @if($tasks->count() > 0)
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
        @php
            $totalTasks = $tasks->total();
            $completedTasks = $tasks->where('status', 'completed')->count();
            $inProgressTasks = $tasks->where('status', 'in_progress')->count();
            $overdueTasks = $tasks->filter(function($task) { return $task->isOverdue(); })->count();
        @endphp

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $totalTasks }}</dd>
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
                            <dd class="text-lg font-medium text-gray-900">{{ $completedTasks }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $inProgressTasks }}</dd>
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
                            <dd class="text-lg font-medium text-gray-900">{{ $overdueTasks }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
@endsection
