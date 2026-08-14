<?php

namespace App\Providers;

use App\Billing\LaravelStripeClient;
use App\Billing\StripeClient;
use App\Messaging\LaravelMetaWhatsAppClient;
use App\Messaging\MetaWhatsAppClient;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MetaWhatsAppClient::class, LaravelMetaWhatsAppClient::class);
        $this->app->bind(StripeClient::class, LaravelStripeClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', fn (User $user): bool => $user->isOperationsAdmin());
    }
}
