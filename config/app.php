<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'ProjectFlow'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => env('DEFAULT_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Custom Application Settings
    |--------------------------------------------------------------------------
    |
    | Here you can define custom settings for your ProjectFlow application.
    | These settings can be accessed throughout your application using the
    | config() helper function.
    |
    */

    'features' => [
        'registration' => env('ENABLE_REGISTRATION', true),
        'email_verification' => env('ENABLE_EMAIL_VERIFICATION', false),
        'team_invitations' => env('ENABLE_TEAM_INVITATIONS', true),
        'file_uploads' => env('ENABLE_FILE_UPLOADS', true),
        'api_access' => env('ENABLE_API_ACCESS', true),
        'time_tracking' => env('ENABLE_TIME_TRACKING', true),
    ],

    'organization' => [
        'default_plan' => env('DEFAULT_ORGANIZATION_PLAN', 'free'),
        'default_max_users' => env('DEFAULT_MAX_USERS', 5),
        'default_max_projects' => env('DEFAULT_MAX_PROJECTS', 3),
        'default_trial_days' => env('DEFAULT_TRIAL_DAYS', 14),
    ],

    'defaults' => [
        'task_status' => env('DEFAULT_TASK_STATUS', 'todo'),
        'task_priority' => env('DEFAULT_TASK_PRIORITY', 'medium'),
        'project_status' => env('DEFAULT_PROJECT_STATUS', 'planning'),
        'project_priority' => env('DEFAULT_PROJECT_PRIORITY', 'medium'),
        'work_hours_per_day' => env('DEFAULT_WORK_HOURS_PER_DAY', 8),
        'work_days_per_week' => env('DEFAULT_WORK_DAYS_PER_WEEK', 5),
    ],

    'uploads' => [
        'max_size' => env('MAX_UPLOAD_SIZE', 10240), // KB
        'allowed_types' => explode(',', env('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip')),
    ],

    'api' => [
        'version' => env('API_VERSION', 'v1'),
        'rate_limit' => env('API_RATE_LIMIT', 60),
        'rate_limit_authenticated' => env('API_RATE_LIMIT_AUTHENTICATED', 120),
        'pagination_limit' => env('API_PAGINATION_LIMIT', 50),
        'max_pagination_limit' => env('API_MAX_PAGINATION_LIMIT', 100),
    ],

    'notifications' => [
        'channels' => explode(',', env('NOTIFICATION_CHANNELS', 'mail,database')),
        'slack_webhook' => env('SLACK_WEBHOOK_URL'),
        'discord_webhook' => env('DISCORD_WEBHOOK_URL'),
    ],

    'localization' => [
        'supported_locales' => explode(',', env('SUPPORTED_LOCALES', 'en,es,fr,de,pt')),
        'default_timezone' => env('DEFAULT_TIMEZONE', 'UTC'),
    ],

    'security' => [
        'bcrypt_rounds' => env('BCRYPT_ROUNDS', 12),
        'cors_allowed_origins' => env('CORS_ALLOWED_ORIGINS', '*'),
        'trusted_proxies' => env('TRUSTED_PROXIES', '*'),
    ],

    'demo_mode' => env('DEMO_MODE', false),

    'analytics' => [
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'mixpanel_token' => env('MIXPANEL_TOKEN'),
    ],

    'development' => [
        'telescope_enabled' => env('TELESCOPE_ENABLED', false),
        'debugbar_enabled' => env('DEBUGBAR_ENABLED', false),
    ],

];
