<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Update last login timestamp
            Auth::user()->updateLastLogin();

            // Set current organization if user has one
            $this->setCurrentOrganization();

            $redirectUrl = route('dashboard');

            // Handle AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => $redirectUrl
                ]);
            }

            return redirect()->intended($redirectUrl);
        }

        $errorMessage = __('The provided credentials do not match our records.');

        // Handle AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => [
                    'email' => [$errorMessage]
                ]
            ], 422);
        }

        throw ValidationException::withMessages([
            'email' => $errorMessage,
        ]);
    }

    /**
     * Show the registration form.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle a registration request.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'organization_name' => 'required|string|max:255',
            'terms' => 'required|accepted',
        ], [
            'terms.required' => 'You must agree to the terms and conditions.',
            'terms.accepted' => 'You must agree to the terms and conditions.',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'timezone' => 'UTC',
        ]);

        // Create organization - FIXED: Use direct integer to avoid Carbon error
        $organization = Organization::create([
            'name' => $validated['organization_name'],
            'plan' => 'free',
            'max_users' => 5,
            'max_projects' => 3,
            'trial_ends_at' => now()->addDays(14), // Fixed: direct integer
        ]);

        // Add user as owner of the organization
        $organization->addUser($user, 'owner');

        // Auto-login the user
        Auth::login($user);

        // Set current organization
        session(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard')
            ->with('success', 'Account created successfully! Welcome to your 14-day free trial.');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Switch organization.
     */
    public function switchOrganization(Request $request, Organization $organization)
    {
        $user = Auth::user();

        // Check if user has access to this organization
        if (!$user->canAccessOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        // Set current organization in session
        session(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard')
            ->with('success', "Switched to {$organization->name}");
    }

    /**
     * Show user profile.
     */
    public function showProfile()
    {
        $user = Auth::user();
        $organizations = $user->organizations()->withPivot(['role', 'joined_at'])->get();

        return view('auth.profile', compact('user', 'organizations'));
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'timezone' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $user->update($validated);

        return redirect()->route('profile')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The provided password does not match your current password.'),
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile')
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Accept organization invitation.
     */
    public function acceptInvitation(Request $request, $token)
    {
        // This would be implemented with a proper invitation system
        // For now, it's a placeholder

        return redirect()->route('dashboard')
            ->with('success', 'Invitation accepted successfully.');
    }

    /**
     * Show organization selection if user belongs to multiple.
     */
    public function showOrganizationSelect()
    {
        $user = Auth::user();
        $organizations = $user->organizations()
            ->wherePivot('status', 'active')
            ->withPivot(['role'])
            ->get();

        if ($organizations->count() === 1) {
            session(['current_organization_id' => $organizations->first()->id]);
            return redirect()->route('dashboard');
        }

        return view('auth.select-organization', compact('organizations'));
    }

    /**
     * Set current organization for the user.
     */
    protected function setCurrentOrganization()
    {
        $user = Auth::user();

        // If no current organization in session, set the first active one
        if (!session('current_organization_id')) {
            $organization = $user->organizations()
                ->wherePivot('status', 'active')
                ->first();

            if ($organization) {
                session(['current_organization_id' => $organization->id]);
            }
        }
    }

    /**
     * Show forgot password form.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password request.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        // This would send a password reset email
        // For now, it's a placeholder

        return redirect()->route('login')
            ->with('success', 'Password reset instructions sent to your email.');
    }

    /**
     * Show reset password form.
     */
    public function showResetPassword($token)
    {
        return view('auth.reset-password', compact('token'));
    }

    /**
     * Handle password reset.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // This would handle the actual password reset
        // For now, it's a placeholder

        return redirect()->route('login')
            ->with('success', 'Password reset successfully.');
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password does not match your current password.'),
            ]);
        }

        // Check if user is the only owner of any organization
        $ownedOrganizations = $user->organizations()
            ->wherePivot('role', 'owner')
            ->get();

        foreach ($ownedOrganizations as $org) {
            $owners = $org->users()->wherePivot('role', 'owner')->count();
            if ($owners === 1) {
                return redirect()->back()
                    ->withErrors(['password' => "You cannot delete your account while being the only owner of '{$org->name}'. Please transfer ownership first."]);
            }
        }

        // Logout and delete account
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Your account has been deleted successfully.');
    }
}
