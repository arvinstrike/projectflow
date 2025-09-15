@extends('layouts.auth')

@section('title', 'Register')
@section('heading', 'Create your account')
@section('subheading')
    Or
    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
        sign in to your existing account
    </a>
@endsection

@section('content')
<div class="mt-6">
    <form class="space-y-6" action="{{ route('register') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
                    Full Name
                </label>
                <div class="mt-2">
                    <input
                        id="name"
                        name="name"
                        type="text"
                        autocomplete="name"
                        required
                        value="{{ old('name') }}"
                        class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('name') ring-red-500 @enderror"
                        placeholder="John Doe">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="username" class="block text-sm font-medium leading-6 text-gray-900">
                    Username
                </label>
                <div class="mt-2">
                    <input
                        id="username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        required
                        value="{{ old('username') }}"
                        class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('username') ring-red-500 @enderror"
                        placeholder="johndoe">
                    @error('username')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
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
                    value="{{ old('email') }}"
                    class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('email') ring-red-500 @enderror"
                    placeholder="john@example.com">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="organization_name" class="block text-sm font-medium leading-6 text-gray-900">
                Organization Name
            </label>
            <div class="mt-2">
                <input
                    id="organization_name"
                    name="organization_name"
                    type="text"
                    required
                    value="{{ old('organization_name') }}"
                    class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('organization_name') ring-red-500 @enderror"
                    placeholder="Your Company Name">
                @error('organization_name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-gray-500">
                    This will be your organization's workspace where you can invite team members.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                    Password
                </label>
                <div class="mt-2">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('password') ring-red-500 @enderror"
                        placeholder="••••••••">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium leading-6 text-gray-900">
                    Confirm Password
                </label>
                <div class="mt-2">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="••••••••">
                </div>
            </div>
        </div>

        <div class="flex items-center">
            <input
                id="terms"
                name="terms"
                type="checkbox"
                required
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
            <label for="terms" class="ml-3 block text-sm leading-6 text-gray-900">
                I agree to the
                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Terms of Service</a>
                and
                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
            </label>
        </div>

        <div>
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                x-data="{ loading: false }"
                @click="loading = true"
                :disabled="loading">

                <span x-show="!loading">Create account</span>
                <span x-show="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating account...
                </span>
            </button>
        </div>
    </form>
</div>

<!-- Trial information -->
<div class="mt-8 p-4 bg-blue-50 rounded-lg">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">
                Free 14-day trial included
            </h3>
            <div class="mt-2 text-sm text-blue-700">
                <p>Start with our free plan and upgrade when you're ready. No credit card required.</p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Up to 5 team members</li>
                    <li>3 projects included</li>
                    <li>Basic project management features</li>
                    <li>Email support</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
