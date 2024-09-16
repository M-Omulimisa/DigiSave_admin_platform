<?php

namespace App\Providers;

use App\Models\Meeting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\Sacco; // Import your Sacco model
use App\Models\Transaction;
use App\Models\User;
use App\Observers\AuditObserver; // Import your AuditObserver

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Keep your existing configuration
        Schema::defaultStringLength(191);

        // Register the observer for Sacco model
        Sacco::observe(AuditObserver::class);

        // Register the observer for User model
        User::observe(AuditObserver::class);

        // Register the observer for Transaction model
        Transaction::observe(AuditObserver::class);

        // Register the observer for Meeting model
        Meeting::observe(AuditObserver::class);

        // Register the AuditObserver to track changes on the Sacco model
        Sacco::observe(AuditObserver::class);
    }
}
