<?php

namespace LaravelUtils;

use Illuminate\Support\ServiceProvider;
use LaravelUtils\Console\Commands\CmiInstallCommand;

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
