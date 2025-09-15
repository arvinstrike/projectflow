<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Get current organization from session
        $currentOrganizationId = session('current_organization_id');

        // If no current organization, redirect to selection
        if (!$currentOrganizationId) {
            // Check if user has any organizations
            $userOrganizations = $user->organizations()
                ->wherePivot('status', 'active')
                ->get();

            if ($userOrganizations->isEmpty()) {
                // User has no organizations, this shouldn't happen in normal flow
                // but handle gracefully
                abort(403, 'You are not a member of any organization.');
            }

            if ($userOrganizations->count() === 1) {
                // User only has one organization, set it automatically
                session(['current_organization_id' => $userOrganizations->first()->id]);
                return $next($request);
            }

            // User has multiple organizations, redirect to selection
            return redirect()->route('organization.select');
        }

        // Verify user has access to current organization
        $hasAccess = $user->organizations()
            ->where('organization_id', $currentOrganizationId)
            ->wherePivot('status', 'active')
            ->exists();

        if (!$hasAccess) {
            // Clear invalid organization from session
            session()->forget('current_organization_id');

            return redirect()->route('organization.select')
                ->with('error', 'You do not have access to the selected organization.');
        }

        return $next($request);
    }
}
