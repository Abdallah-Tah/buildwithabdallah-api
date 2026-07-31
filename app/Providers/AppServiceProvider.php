<?php

namespace App\Providers;

use App\Messaging\LaravelMetaWhatsAppClient;
use App\Messaging\MetaWhatsAppClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MetaWhatsAppClient::class, LaravelMetaWhatsAppClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
