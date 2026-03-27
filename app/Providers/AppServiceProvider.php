<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\OwnerPhoneRequest;
use App\Models\Report;
use App\Observers\OwnerPhoneRequestObserver;
use App\Observers\ReportObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
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
        // Force HTTPS in production/hosting
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        // Override mail config at runtime
        // QUAN TRỌNG: KHÔNG inject MailSettings vào boot() vì sẽ fail khi chưa migrate
        try {
            $settings = app(\App\Settings\MailSettings::class);
            config([
                'mail.mailers.smtp.host' => $settings->email_host,
                'mail.mailers.smtp.port' => $settings->email_port,
                'mail.mailers.smtp.encryption' => $settings->email_encryption,
                'mail.mailers.smtp.username' => $settings->email_username,
                'mail.mailers.smtp.password' => $settings->email_password,
                'mail.from.address' => $settings->email_from_address,
                'mail.from.name' => $settings->email_from_name,
            ]);
        } catch (\Exception $e) {
            // Fallback to .env if settings table doesn't exist yet (migration running)
            // hoặc khi chưa có data trong settings
        }

        // Register Policies for Spatie Permission Models
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(\Spatie\Permission\Models\Permission::class, \App\Policies\PermissionPolicy::class);

        // Register Observers for notifications
        OwnerPhoneRequest::observe(OwnerPhoneRequestObserver::class);
        Report::observe(ReportObserver::class);

        // Super Admin bypass
        Gate::before(function ($user, $ability) {
            return $user->hasRole('SUPER_ADMIN') ? true : null;
        });

        // Scramble Documentation Gate
        Gate::define('viewApiDocs', function ($user = null) {
            return true;
        });
    }
}
