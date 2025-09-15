@extends('layouts.auth')

@section('title', 'Login')
@section('heading', 'Sign in to your account')
@section('subheading')
    Or
    <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
        create a new account
    </a>
@endsection

@section('content')
<div class="mt-6" x-data="{
    loading: false,
    formData: {
        email: '{{ old('email') }}',
        password: '',
        remember: false
    },
    errors: {},

    async submitForm() {
        // Prevent double submission
        if (this.loading) return;

        // Basic validation
        this.errors = {};

        if (!this.formData.email.trim()) {
            this.errors.email = 'Email is required';
        }

        if (!this.formData.password) {
            this.errors.password = 'Password is required';
        }

        // If validation errors, don't submit
        if (Object.keys(this.errors).length > 0) {
            return;
        }

        // Set loading state
        this.loading = true;

        try {
            // Create form data
            const form = this.$refs.loginForm;
            const formData = new FormData(form);

            // Submit form
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok) {
                // Success - redirect
                window.location.href = result.redirect || '/dashboard';
            } else {
                // Handle authentication errors
                if (result.errors) {
                    this.errors = result.errors;
                } else {
                    this.errors.email = result.message || 'Invalid email or password.';
                }
            }
        } catch (error) {
            console.error('Login error:', error);
            this.errors.general = 'Network error. Please try again.';
        } finally {
            // Always reset loading state
            this.loading = false;
        }
    }
}">
    <form x-ref="loginForm" @submit.prevent="submitForm()" class="space-y-6" action="{{ route('login') }}" method="POST">
        @csrf

        <!-- General Error -->
        <div x-show="errors.general" class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800" x-text="errors.general"></p>
                </div>
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium leading-6 text-gray-900">
                Email address
            </label>
            <div class="mt-2">
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    x-model="formData.email"
                    value="{{ old('email') }}"
                    class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                    :class="{ 'ring-red-500': errors.email }"
                    placeholder="admin@example.com">
                <p x-show="errors.email" class="mt-2 text-sm text-red-600" x-text="errors.email"></p>
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                Password
            </label>
            <div class="mt-2">
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    x-model="formData.password"
                    class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                    :class="{ 'ring-red-500': errors.password }"
                    placeholder="password">
                <p x-show="errors.password" class="mt-2 text-sm text-red-600" x-text="errors.password"></p>
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    x-model="formData.remember"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                <label for="remember" class="ml-3 block text-sm leading-6 text-gray-900">
                    Remember me
                </label>
            </div>

            <div class="text-sm leading-6">
                <a href="{{ route('forgot-password') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                    Forgot password?
                </a>
            </div>
        </div>

        <div>
            <button
                type="submit"
                :disabled="loading"
                :class="{ 'opacity-50 cursor-not-allowed': loading }"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">

                <span x-show="!loading">Sign in</span>
                <span x-show="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing in...
                </span>
            </button>
        </div>
    </form>
</div>

<!-- Demo Credentials -->
<div class="mt-8 p-4 bg-blue-50 rounded-lg">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">
                Demo Accounts Available
            </h3>
            <div class="mt-2 text-sm text-blue-700">
                <div class="space-y-1">
                    <p><strong>Owner:</strong> admin@example.com | password</p>
                    <p><strong>Manager:</strong> jane@example.com | password</p>
                    <p><strong>Developer:</strong> mike@example.com | password</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Login Buttons for Demo -->
<div class="mt-4 space-y-2">
    <p class="text-sm text-gray-600 text-center">Quick demo login:</p>
    <div class="flex space-x-2">
        <button
            @click="formData.email = 'admin@example.com'; formData.password = 'password'"
            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Admin
        </button>
        <button
            @click="formData.email = 'jane@example.com'; formData.password = 'password'"
            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Manager
        </button>
        <button
            @click="formData.email = 'mike@example.com'; formData.password = 'password'"
            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Developer
        </button>
    </div>
</div>
@endsection
