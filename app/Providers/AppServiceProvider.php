<?php

namespace App\Providers;

use App\Models\Invitation;
use App\Models\Organization;
use App\Policies\InvitationPolicy;
use App\Policies\OrganizationPolicy;
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
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
    }
}
