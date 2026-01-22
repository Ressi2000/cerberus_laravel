<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogLogin;
use App\Listeners\LogLogout;

class EventServiceProvider extends ServiceProvider
{

    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        \Illuminate\Auth\Events\Login::class => [
            \App\Listeners\LogLogin::class,
        ],

        \Illuminate\Auth\Events\Logout::class => [
            \App\Listeners\LogLogout::class,
        ],
    ];


    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
