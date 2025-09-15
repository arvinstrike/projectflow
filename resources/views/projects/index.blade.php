@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Projects
            </h1>
            <p class="mt-2 text-sm text-gray-700">
                Manage and track all your organization's projects
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('projects.create') }}"
               class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Project
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Search projects..."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                        name="status"
                        id="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        @foreach($statusOptions as $value => $label)
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
                        @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('priority') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700">Sort By</label>
                    <select
                        name="sort"
                        id="sort"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="created_at" {{ request('sort', 'created_at') == 'created_at' ? 'selected' : '' }}>
                            Created Date
                        </option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>
                            Name
                        </option>
                        <option value="deadline" {{ request('sort') == 'deadline' ? 'selected' : '' }}>
                            Deadline
                        </option>
                        <option value="priority" {{ request('sort') == 'priority' ? 'selected' : '' }}>
                            Priority
                        </option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="sm:col-span-2 lg:col-span-4 flex items-end space-x-3">
                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Apply Filters
                    </button>
                    <a
                        href="{{ route('projects.index') }}"
                        class="inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Projects Grid -->
    @if($projects->count() > 0)
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($projects as $project)
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow duration-200">
            <!-- Project Header -->
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $project->color }}"></div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-medium text-gray-900 truncate">
                                <a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-600">
                                    {{ $project->name }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500">{{ $project->code }}</p>
                        </div>
                    </div>

                    <!-- Project Actions -->
                    <div class="flex-shrink-0" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center p-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>

                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('projects.show', $project) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                View Details
                            </a>
                            <a href="{{ route('projects.edit', $project) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Edit Project
                            </a>
                            <a href="{{ route('projects.team', $project) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Manage Team
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Project Description -->
                @if($project->description)
                <p class="mt-3 text-sm text-gray-600 line-clamp-2">
                    {{ $project->description }}
                </p>
                @endif

                <!-- Project Stats -->
                <div class="mt-4 flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center text-gray-500">
                            <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            {{ $project->tasks()->count() }} tasks
                        </div>
                        <div class="flex items-center text-gray-500">
                            <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            {{ $project->users()->count() }} members
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-700">Progress</span>
                        <span class="text-gray-900 font-medium">{{ $project->progress }}%</span>
                    </div>
                    <div class="mt-2 bg-gray-200 rounded-full h-2">
                        <div
                            class="h-2 rounded-full transition-all duration-300"
                            style="width: {{ $project->progress }}%; background-color: {{ $project->color }}">
                        </div>
                    </div>
                </div>

                <!-- Project Meta -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium status-{{ $project->status }}">
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium priority-{{ $project->priority }}">
                            {{ ucfirst($project->priority) }}
                        </span>
                    </div>
                    @if($project->deadline)
                    <div class="text-xs text-gray-500">
                        Due {{ $project->deadline->format('M j') }}
                        @if($project->isOverdue())
                        <span class="text-red-600 font-medium">(Overdue)</span>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Team Avatars -->
                @if($project->users()->count() > 0)
                <div class="mt-4">
                    <div class="flex -space-x-2 overflow-hidden">
                        @foreach($project->users()->limit(4)->get() as $user)
                        <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white"
                             src="{{ $user->avatar_url }}"
                             alt="{{ $user->name }}"
                             title="{{ $user->name }}">
                        @endforeach
                        @if($project->users()->count() > 4)
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-500 ring-2 ring-white text-xs font-medium text-white">
                            +{{ $project->users()->count() - 4 }}
                        </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $projects->links() }}
    </div>

    @else
    <!-- Empty State -->
    <div class="bg-white shadow rounded-lg">
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No projects found</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if(request()->hasAny(['search', 'status', 'priority']))
                Try adjusting your search criteria or clear the filters.
                @else
                Get started by creating your first project.
                @endif
            </p>
            <div class="mt-6">
                @if(request()->hasAny(['search', 'status', 'priority']))
                <a href="{{ route('projects.index') }}"
                   class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Clear Filters
                </a>
                @else
                <a href="{{ route('projects.create') }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Project
                </a>
                @endif
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
