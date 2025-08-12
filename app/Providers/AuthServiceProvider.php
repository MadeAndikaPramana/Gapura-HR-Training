<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define Gates for GAPURA Training System

        // Admin Gates
        Gate::define('admin-access', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('super-admin-access', function ($user) {
            return $user->role === 'super_admin';
        });

        // Training Management Gates
        Gate::define('manage-training-records', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('view-training-records', function ($user) {
            return in_array($user->role, ['staff', 'admin', 'super_admin']);
        });

        Gate::define('create-training-records', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('edit-training-records', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('delete-training-records', function ($user) {
            return $user->role === 'super_admin';
        });

        // Employee Management Gates
        Gate::define('manage-employees', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('view-employees', function ($user) {
            return in_array($user->role, ['staff', 'admin', 'super_admin']);
        });

        // Training Types Management Gates
        Gate::define('manage-training-types', function ($user) {
            return $user->role === 'super_admin';
        });

        // Reports and Analytics Gates
        Gate::define('view-reports', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('export-data', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('import-data', function ($user) {
            return $user->role === 'super_admin';
        });

        // System Settings Gates
        Gate::define('manage-system-settings', function ($user) {
            return $user->role === 'super_admin';
        });

        Gate::define('manage-users', function ($user) {
            return $user->role === 'super_admin';
        });

        // Notification Gates
        Gate::define('send-notifications', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        // Certificate Gates
        Gate::define('generate-certificates', function ($user) {
            return in_array($user->role, ['admin', 'super_admin']);
        });

        Gate::define('verify-certificates', function ($user) {
            return in_array($user->role, ['staff', 'admin', 'super_admin']);
        });
    }
}
