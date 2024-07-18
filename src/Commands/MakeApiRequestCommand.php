<?php

namespace MMAE\ApiResponse\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:request-api', description: 'Make a Api request')]

class MakeApiRequestCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:request-api {name}';
    protected $type = 'request';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a Api request';


    protected function getStub(): string
    {
        return __DIR__ . '/../stubs/request.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Requests\Api';
    }
}
