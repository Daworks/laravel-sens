<?php

namespace Daworks\Sens;

use Daworks\Sens\AlimTalk\AlimTalk;
use Daworks\Sens\AlimTalk\AlimTalkChannel;
use Daworks\Sens\Sms\Sms;
use Daworks\Sens\Sms\SmsChannel;
use Illuminate\Support\ServiceProvider;

class SensServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-sens.php',
            'laravel-sens'
        );

        // Register SENS SMS service.
        $this->app->when(SmsChannel::class)
            ->needs(Sms::class)
            ->give(function ($app) {
                return new Sms($app['config']->get('laravel-sens'));
            });

        // Register SENS AlimTalk service.
        $this->app->when(AlimTalkChannel::class)
            ->needs(AlimTalk::class)
            ->give(function ($app) {
                return new AlimTalk($app['config']->get('laravel-sens'));
            });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/laravel-sens.php' => config_path('laravel-sens.php')
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
