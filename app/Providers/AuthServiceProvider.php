<?php

namespace App\Providers;

use App\Models\User;
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

        // =======================================================================
        // SIMPLIFIED PERMISSION SYSTEM - ADMIN & SUPER ADMIN ONLY
        // =======================================================================

        // Base Admin Access (admin + super_admin)
        Gate::define('admin-access', function (User $user) {
            return $user->isAdmin();
        });

        // Super Admin Only Access
        Gate::define('super-admin-access', function (User $user) {
            return $user->isSuperAdmin();
        });

        // =======================================================================
        // OPERATIONAL DATA MANAGEMENT (admin + super_admin)
        // =======================================================================

        // Employee Management
        Gate::define('manage-employees', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        Gate::define('view-employees', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        // Training Records Management
        Gate::define('manage-training-records', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        Gate::define('view-training-records', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        // Reports and Analytics
        Gate::define('view-reports', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        Gate::define('export-data', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        Gate::define('import-data', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        // =======================================================================
        // MASTER DATA MANAGEMENT (super_admin only)
        // =======================================================================

        // Training Types Management (create new training categories)
        Gate::define('manage-training-types', function (User $user) {
            return $user->isSuperAdmin(); // super_admin only
        });

        // Department Management (create new departments)
        Gate::define('manage-departments', function (User $user) {
            return $user->isSuperAdmin(); // super_admin only
        });

        // Certificate Templates Management (create new certificate types)
        Gate::define('manage-certificate-templates', function (User $user) {
            return $user->isSuperAdmin(); // super_admin only
        });

        // System Settings Management
        Gate::define('manage-system-settings', function (User $user) {
            return $user->isSuperAdmin(); // super_admin only
        });

        // User Management (create/edit users)
        Gate::define('manage-users', function (User $user) {
            return $user->isSuperAdmin(); // super_admin only
        });

        // Master Data Fields (add new fields to forms)
        Gate::define('manage-master-fields', function (User $user) {
            return $user->isSuperAdmin(); // super_admin only
        });

        // =======================================================================
        // SHARED PERMISSIONS (admin + super_admin)
        // =======================================================================

        // Certificate Generation & Verification
        Gate::define('generate-certificates', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        Gate::define('verify-certificates', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        // Background Checks Management
        Gate::define('manage-background-checks', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });

        // Notifications
        Gate::define('send-notifications', function (User $user) {
            return $user->isAdmin(); // admin + super_admin
        });
    }
}
