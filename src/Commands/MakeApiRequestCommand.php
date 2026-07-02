<?php

declare(strict_types=1);

namespace MMAE\ApiResponse\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeApiRequestCommand extends GeneratorCommand
{
    protected $signature = 'make:request-api {name}';

    protected $description = 'Make a Api request';

    protected $type = 'request';

    protected function getStub(): string
    {
        return __DIR__.'/../stubs/request.stub';
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Requests\Api';
    }
}
