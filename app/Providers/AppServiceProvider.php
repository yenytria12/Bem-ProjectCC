<?php

namespace App\Providers;

use App\Models\Ministry;
use App\Models\Proposal;
use App\Models\ProgramKerja;
use App\Models\User;
use App\Observers\MinistryObserver;
use App\Observers\ProposalObserver;
use App\Observers\ProgramKerjaObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

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
        // Register Observers
        Proposal::observe(ProposalObserver::class);
        ProgramKerja::observe(ProgramKerjaObserver::class);
        User::observe(UserObserver::class);
        Ministry::observe(MinistryObserver::class);
        
        // Register Event Listeners
        Event::listen(Login::class, \App\Listeners\LogUserLogin::class);
        Event::listen(Logout::class, \App\Listeners\LogUserLogout::class);
    }
}
