<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\VoterResolver;
use App\Services\SessionVoterResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    
    public function register(): void
    {
        $this->app->bind(VoterResolver::class, SessionVoterResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
