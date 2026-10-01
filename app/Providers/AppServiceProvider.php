<?php

namespace App\Providers;

use App\Models\Lead;
use App\Notifications\Channels\ExpoPushChannel;
use App\Observers\LeadObserver;
use App\Services\AI\AIProviderInterface;
use App\Services\AI\NullAIProvider;
use App\Services\AI\OpenAIProvider;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIProviderInterface::class, function () {
            if (config('services.ai.enabled') && config('services.ai.provider') === 'openai') {
                return new OpenAIProvider;
            }

            return new NullAIProvider;
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Lead::observe(LeadObserver::class);
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('expo_push', fn ($app) => $app->make(ExpoPushChannel::class));
        });
    }
}
