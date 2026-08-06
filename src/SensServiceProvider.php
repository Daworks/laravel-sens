<?php

namespace Daworks\Sens;

use Daworks\Sens\AlimTalk\AlimTalk;
use Daworks\Sens\Sms\Sms;
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
        $this->app->singleton(Sms::class, function ($app) {
            return new Sms($app['config']->get('laravel-sens'));
        });

        // Register SENS AlimTalk service.
        $this->app->singleton(AlimTalk::class, function ($app) {
            return new AlimTalk($app['config']->get('laravel-sens'));
        });

        // 발송 결과 조회를 위한 진입점. (Sens 파사드)
        $this->app->singleton('sens', function ($app) {
            return new SensManager($app);
        });

        $this->app->alias('sens', SensManager::class);
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
        return [
            'sens',
            SensManager::class,
            Sms::class,
            AlimTalk::class,
        ];
    }
}
