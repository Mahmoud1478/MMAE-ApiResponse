<?php

declare(strict_types=1);

namespace MMAE\ApiResponse;

use Illuminate\Support\ServiceProvider;
use MMAE\ApiResponse\Commands\MakeApiRequestCommand;

class MMAEApiResponseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'apiresponse');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeApiRequestCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/apiresponse'),
            ], 'apiresponse-lang');
        }

    }
}
