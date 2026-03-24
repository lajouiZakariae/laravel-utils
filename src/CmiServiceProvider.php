<?php

namespace LaravelUtils;

use Illuminate\Support\ServiceProvider;
use LaravelUtils\Console\Commands\CmiInstallCommand;

class CmiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishesMigrations([
            __DIR__.'/../stubs/database/migrations' => database_path('migrations'),
        ], 'cmi-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CmiInstallCommand::class,
            ]);
        }
    }
}
