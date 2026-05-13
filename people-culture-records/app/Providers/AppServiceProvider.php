<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\DisciplinaryCase;
use App\Models\StaffPromotion;
use App\Models\StaffRelocation;
use App\Models\User;
use App\Policies\DisciplinaryCasePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\StaffPromotionPolicy;
use App\Policies\StaffRelocationPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Paginator::useBootstrapFive();

        Gate::policy(DisciplinaryCase::class, DisciplinaryCasePolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(StaffPromotion::class, StaffPromotionPolicy::class);
        Gate::policy(StaffRelocation::class, StaffRelocationPolicy::class);

        Gate::define('access-dashboard', fn (User $user) => $user->is_active);

        Gate::define('manage-master-data', function (User $user) {
            return $user->is_active && ($user->isAdmin() || $user->isHrManager());
        });

        Gate::define('manage-users', function (User $user) {
            return $user->is_active && $user->isAdmin();
        });

        Gate::define('view-reports', function (User $user) {
            return $user->is_active && ($user->isAdmin() || $user->isHrManager() || $user->hasRole('HR Officer') || $user->hasRole('Viewer'));
        });

        Gate::define('export-reports', function (User $user) {
            return $user->is_active && ($user->isAdmin() || $user->isHrManager() || $user->hasRole('HR Officer') || $user->hasRole('Viewer'));
        });

        Gate::define('view-imports', function (User $user) {
            return $user->is_active && ($user->isAdmin() || $user->isHrManager() || $user->hasRole('HR Officer'));
        });

        Gate::define('import-employees', function (User $user) {
            return $user->is_active && ($user->isAdmin() || $user->isHrManager() || $user->hasRole('HR Officer'));
        });
    }
}
