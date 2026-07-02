<?php

/**
 * Standalone entry point invoked in a subprocess by DumpAndDieTest, because
 * HasApiResponse::dumpAndDie() calls exit() and would otherwise kill the
 * PHPUnit/Pest process running it. Boots a real app so the response()
 * helper used by makeResponse() resolves.
 */
require __DIR__.'/../../../vendor/autoload.php';

use MMAE\ApiResponse\Traits\HasApiResponse;
use Orchestra\Testbench\Foundation\Application;

Application::create(basePath: __DIR__.'/../../../workbench');

$subject = new class
{
    use HasApiResponse;
};

$method = new ReflectionMethod($subject, 'dumpAndDie');
$method->setAccessible(true);
$method->invoke($subject, ['foo' => 'bar'], 'test-token');
