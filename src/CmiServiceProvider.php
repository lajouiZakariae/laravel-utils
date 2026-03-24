<?php

namespace LaravelCmi;

use Illuminate\Support\ServiceProvider;
use LaravelCmi\Console\Commands\CmiInstallCommand;

class CmiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CmiInstallCommand::class,
            ]);
        }
    }
}
