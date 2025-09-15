<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\OrganizationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard if authenticated, otherwise to login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('reset-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Auth user routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Organization selection (for users with multiple organizations)
    Route::get('/select-organization', [AuthController::class, 'showOrganizationSelect'])->name('organization.select');
    Route::post('/switch-organization/{organization}', [AuthController::class, 'switchOrganization'])->name('auth.switch-organization');

    // Profile management
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [AuthController::class, 'deleteAccount'])->name('profile.delete');

    // Accept invitations
    Route::get('/invitation/{token}', [AuthController::class, 'acceptInvitation'])->name('invitation.accept');
});

/*
|--------------------------------------------------------------------------
| Protected Application Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'organization.access'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
    Route::get('/dashboard/calendar', [DashboardController::class, 'calendar'])->name('dashboard.calendar');
    Route::get('/dashboard/day/{date}', [DashboardController::class, 'showDay'])->name('dashboard.day');

    /*
    |--------------------------------------------------------------------------
    | Project Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

        // Project team management
        Route::get('/{project}/team', [ProjectController::class, 'team'])->name('projects.team');
        Route::post('/{project}/team', [ProjectController::class, 'addTeamMember'])->name('projects.team.add');
        Route::put('/{project}/team/{user}', [ProjectController::class, 'updateTeamMember'])->name('projects.team.update');
        Route::delete('/{project}/team/{user}', [ProjectController::class, 'removeTeamMember'])->name('projects.team.remove');

        // Project actions
        Route::post('/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');
        Route::post('/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    });

    /*
    |--------------------------------------------------------------------------
    | Milestone Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('projects/{project}/milestones')->group(function () {
        Route::get('/', [MilestoneController::class, 'index'])->name('milestones.index');
        Route::get('/create', [MilestoneController::class, 'create'])->name('milestones.create');
        Route::post('/', [MilestoneController::class, 'store'])->name('milestones.store');
        Route::get('/{milestone}', [MilestoneController::class, 'show'])->name('milestones.show');
        Route::get('/{milestone}/edit', [MilestoneController::class, 'edit'])->name('milestones.edit');
        Route::put('/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
        Route::delete('/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');

        // Milestone actions
        Route::post('/{milestone}/complete', [MilestoneController::class, 'complete'])->name('milestones.complete');
        Route::post('/{milestone}/reopen', [MilestoneController::class, 'reopen'])->name('milestones.reopen');
        Route::post('/reorder', [MilestoneController::class, 'reorder'])->name('milestones.reorder');
    });

    /*
    |--------------------------------------------------------------------------
    | Task Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('tasks')->group(function () {
        Route::get('/my', [TaskController::class, 'myTasks'])->name('tasks.my');
        Route::get('/assigned', [TaskController::class, 'assigned'])->name('tasks.assigned');
        Route::get('/created', [TaskController::class, 'created'])->name('tasks.created');
        Route::get('/search', [TaskController::class, 'search'])->name('tasks.search');
    });

    Route::prefix('projects/{project}/tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        // Task actions
        Route::post('/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/{task}/reopen', [TaskController::class, 'reopen'])->name('tasks.reopen');
        Route::post('/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
        Route::post('/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');

        // Time tracking
        Route::post('/{task}/time/start', [TaskController::class, 'startTimer'])->name('tasks.time.start');
        Route::post('/{task}/time/stop', [TaskController::class, 'stopTimer'])->name('tasks.time.stop');
        Route::post('/{task}/time/log', [TaskController::class, 'logTime'])->name('tasks.time.log');
    });

    /*
    |--------------------------------------------------------------------------
    | Organization Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('organization')->group(function () {
        Route::get('/settings', [OrganizationController::class, 'settings'])->name('organization.settings');
        Route::put('/settings', [OrganizationController::class, 'updateSettings'])->name('organization.settings.update');

        // Team management
        Route::get('/team', [OrganizationController::class, 'team'])->name('organization.team');
        Route::post('/team/invite', [OrganizationController::class, 'inviteUser'])->name('organization.team.invite');
        Route::put('/team/{user}', [OrganizationController::class, 'updateUserRole'])->name('organization.team.update');
        Route::delete('/team/{user}', [OrganizationController::class, 'removeUser'])->name('organization.team.remove');

        // Billing & subscription (placeholder)
        Route::get('/billing', [OrganizationController::class, 'billing'])->name('organization.billing');
        Route::post('/billing/upgrade', [OrganizationController::class, 'upgrade'])->name('organization.billing.upgrade');
        Route::post('/billing/cancel', [OrganizationController::class, 'cancel'])->name('organization.billing.cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | API Routes for AJAX calls
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->group(function () {
        // Quick task updates
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('api.tasks.status');
        Route::patch('/tasks/{task}/priority', [TaskController::class, 'updatePriority'])->name('api.tasks.priority');

        // Search endpoints
        Route::get('/search/projects', [ProjectController::class, 'searchProjects'])->name('api.search.projects');
        Route::get('/search/tasks', [TaskController::class, 'searchTasks'])->name('api.search.tasks');
        Route::get('/search/users', [OrganizationController::class, 'searchUsers'])->name('api.search.users');

        // Dashboard data
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('api.dashboard.stats');
        Route::get('/dashboard/activities', [DashboardController::class, 'getActivities'])->name('api.dashboard.activities');

        // Project data
        Route::get('/projects/{project}/stats', [ProjectController::class, 'getStats'])->name('api.projects.stats');
        Route::get('/projects/{project}/timeline', [ProjectController::class, 'getTimeline'])->name('api.projects.timeline');

        // Analytics data
        Route::get('/analytics/task-completion', [DashboardController::class, 'getTaskCompletionData'])->name('api.analytics.task-completion');
        Route::get('/analytics/project-progress', [DashboardController::class, 'getProjectProgressData'])->name('api.analytics.project-progress');
        Route::get('/analytics/team-productivity', [DashboardController::class, 'getTeamProductivityData'])->name('api.analytics.team-productivity');
    });
});

/*
|--------------------------------------------------------------------------
| Public/Guest Routes
|--------------------------------------------------------------------------
*/
// Landing page (if you have one)
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

// Help & Documentation
Route::get('/help', function () {
    return view('help.index');
})->name('help');

// Terms & Privacy
Route::get('/terms', function () {
    return view('legal.terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('legal.privacy');
})->name('privacy');

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

/*
|--------------------------------------------------------------------------
| Development Routes (only in local environment)
|--------------------------------------------------------------------------
*/
if (app()->environment('local')) {
    Route::get('/dev/seed-demo', function () {
        // Seed demo data for development
        Artisan::call('db:seed', ['--class' => 'DemoSeeder']);
        return redirect()->route('dashboard')->with('success', 'Demo data seeded successfully!');
    })->name('dev.seed');

    Route::get('/dev/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        return redirect()->back()->with('success', 'Cache cleared successfully!');
    })->name('dev.clear-cache');
}
