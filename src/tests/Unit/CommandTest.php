<?php

namespace MMAE\ApiResponse\tests\Unit;

use MMAE\ApiResponse\tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CommandTest extends TestCase
{
    #[Test]
    function test_make_request_command()
    {
        $name = 'TestRequest';
        $path = app_path("Http/Requests/Api/{$name}.php");
        if (file_exists($path)) unlink($path);
        $this->artisan('make:request-api', ['name' => 'TestRequest'])
            ->assertSuccessful();
        $this->assertFileExists($path);
        unlink($path);
    }

}
