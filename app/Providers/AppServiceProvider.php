<?php

namespace App\Providers;

use App\Models\Employee;
use App\Observers\EmployeeObserver;
use App\Policies\EmployeePolicy;
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
        Employee::observe(EmployeeObserver::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
    }
}
