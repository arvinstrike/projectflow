<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL compatibility
        Schema::defaultStringLength(191);

        // Use Bootstrap pagination views
        Paginator::useBootstrap();

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Custom view composers
        $this->registerViewComposers();

        // Custom macros
        $this->registerMacros();
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        // Global view composer for current organization
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $organizationId = session('current_organization_id');
                $currentOrganization = null;

                if ($organizationId) {
                    $currentOrganization = auth()->user()->organizations()
                        ->where('organization_id', $organizationId)
                        ->first();
                }

                $view->with('currentOrganization', $currentOrganization);
            }
        });

        // Sidebar navigation composer
        view()->composer('layouts.partials.sidebar', function ($view) {
            if (auth()->check() && session('current_organization_id')) {
                $user = auth()->user();
                $organizationId = session('current_organization_id');

                // Get user's recent projects
                $recentProjects = \App\Models\Project::whereHas('organization', function ($query) use ($organizationId) {
                    $query->where('id', $organizationId);
                })
                ->accessibleBy($user)
                ->latest()
                ->limit(5)
                ->get();

                $view->with('recentProjects', $recentProjects);
            }
        });
    }

    /**
     * Register custom macros.
     */
    protected function registerMacros(): void
    {
        // Collection macro to group by date
        \Illuminate\Support\Collection::macro('groupByDate', function ($dateField = 'created_at', $format = 'Y-m-d') {
            return $this->groupBy(function ($item) use ($dateField, $format) {
                return $item->{$dateField}->format($format);
            });
        });

        // Request macro to check if current route is in array
        \Illuminate\Http\Request::macro('routeIs', function (...$patterns) {
            return request()->routeIs(...$patterns);
        });

        // String macro for generating initials
        \Illuminate\Support\Str::macro('initials', function ($name, $length = 2) {
            $words = explode(' ', trim($name));
            $initials = '';

            foreach ($words as $word) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= $length) {
                    break;
                }
            }

            return substr($initials, 0, $length);
        });

        // Carbon macro for business days
        \Carbon\Carbon::macro('addBusinessDays', function ($days) {
            $date = $this->copy();

            while ($days > 0) {
                $date->addDay();
                if ($date->isWeekday()) {
                    $days--;
                }
            }

            return $date;
        });

        // Carbon macro for human readable diff with context
        \Carbon\Carbon::macro('humanDiffWithContext', function ($other = null) {
            $other = $other ?: now();
            $diff = $this->diffForHumans($other);

            if ($this->isPast()) {
                return $diff . ' (overdue)';
            } elseif ($this->diffInDays($other) <= 1) {
                return $diff . ' (urgent)';
            } elseif ($this->diffInDays($other) <= 7) {
                return $diff . ' (due soon)';
            }

            return $diff;
        });
    }
}
