@php
    $currentOrganization = auth()->user()->getCurrentOrganization();
@endphp

<div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
            <span class="text-xl font-bold text-white">{{ config('app.name', 'ProjectFlow') }}</span>
        </a>
    </div>

    <!-- Organization Selector -->
    @if($currentOrganization)
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" class="group flex w-full items-center rounded-md bg-gray-700 px-2 py-2 text-left text-sm font-medium text-white hover:bg-gray-600">
            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded bg-gray-500 text-xs font-medium text-white">
                {{ strtoupper(substr($currentOrganization->name, 0, 1)) }}
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="truncate text-sm font-medium text-white">{{ $currentOrganization->name }}</p>
                <p class="truncate text-xs text-gray-300">{{ ucfirst($currentOrganization->plan) }} Plan</p>
            </div>
            <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>

        <div x-show="open"
             x-transition
             @click.away="open = false"
             class="absolute right-0 left-0 z-10 mt-2 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
            @foreach(auth()->user()->organizations as $org)
                @if($org->id !== $currentOrganization->id)
                <a href="{{ route('auth.switch-organization', $org) }}"
                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    {{ $org->name }}
                </a>
                @endif
            @endforeach
            <hr class="my-1">
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                Organization Settings
            </a>
        </div>
    </div>
    @endif

    <!-- Navigation -->
    <nav class="flex flex-1 flex-col">
        <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
                <ul role="list" class="-mx-2 space-y-1">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="{{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2V7zm0 0V5a2 2 0 012-2h6l2 2h6a2 2 0 012 2v2" />
                            </svg>
                            Dashboard
                        </a>
                    </li>

                    <!-- Projects -->
                    <li x-data="{ open: {{ request()->routeIs('projects.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="{{ request()->routeIs('projects.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm leading-6 font-semibold">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Projects
                            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-90': open }" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <ul x-show="open" x-transition class="mt-1 px-2">
                            <li>
                                <a href="{{ route('projects.index') }}"
                                   class="{{ request()->routeIs('projects.index') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex gap-x-3 rounded-md py-2 pl-9 pr-2 text-sm leading-6">
                                    All Projects
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('projects.create') }}"
                                   class="{{ request()->routeIs('projects.create') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex gap-x-3 rounded-md py-2 pl-9 pr-2 text-sm leading-6">
                                    Create Project
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Tasks -->
                    <li x-data="{ open: {{ request()->routeIs('tasks.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="{{ request()->routeIs('tasks.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm leading-6 font-semibold">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Tasks
                            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-90': open }" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <ul x-show="open" x-transition class="mt-1 px-2">
                            <li>
                                <a href="{{ route('tasks.my') }}"
                                   class="{{ request()->routeIs('tasks.my') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex gap-x-3 rounded-md py-2 pl-9 pr-2 text-sm leading-6">
                                    My Tasks
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('dashboard.calendar') }}"
                                   class="{{ request()->routeIs('dashboard.calendar') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex gap-x-3 rounded-md py-2 pl-9 pr-2 text-sm leading-6">
                                    Calendar
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Analytics -->
                    <li>
                        <a href="{{ route('dashboard.analytics') }}"
                           class="{{ request()->routeIs('dashboard.analytics') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Analytics
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Quick Actions -->
            <li class="mt-auto">
                <div class="text-xs font-semibold leading-6 text-gray-400">Quick Actions</div>
                <ul role="list" class="-mx-2 mt-2 space-y-1">
                    <li>
                        <a href="{{ route('projects.create') }}"
                           class="text-gray-400 hover:text-white hover:bg-gray-800 group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            New Project
                        </a>
                    </li>
                    <li>
                        <a href="#"
                           class="text-gray-400 hover:text-white hover:bg-gray-800 group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Invite Team
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- Organization Info -->
    @if($currentOrganization)
    <div class="border-t border-gray-700 pt-4">
        <div class="px-2">
            <div class="text-xs font-semibold leading-6 text-gray-400 mb-2">Organization</div>
            <div class="text-xs text-gray-400">
                <div>{{ $currentOrganization->activeUsers()->count() }}/{{ $currentOrganization->max_users }} users</div>
                <div>{{ $currentOrganization->projects()->count() }}/{{ $currentOrganization->max_projects }} projects</div>
                @if($currentOrganization->isOnTrial())
                <div class="mt-1 text-yellow-400">
                    Trial: {{ $currentOrganization->getTrialDaysRemaining() }} days left
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
