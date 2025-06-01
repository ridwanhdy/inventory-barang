<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Policies\FilamentResourcePolicy;

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

        // Register Filament Resource Policy
        Gate::define('viewAny', [FilamentResourcePolicy::class, 'viewAny']);
        Gate::define('view', [FilamentResourcePolicy::class, 'view']);
        Gate::define('create', [FilamentResourcePolicy::class, 'create']);
        Gate::define('update', [FilamentResourcePolicy::class, 'update']);
        Gate::define('delete', [FilamentResourcePolicy::class, 'delete']);
    }
} 