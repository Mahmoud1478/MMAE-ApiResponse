<?php

namespace MMAE\ApiResponse;
use Illuminate\Support\ServiceProvider;
use MMAE\ApiResponse\Commands\MakeApiRequestCommand;

class MMAEApiResponseServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeApiRequestCommand::class,
            ]);
        }

    }
}
