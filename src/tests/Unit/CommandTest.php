<?php

test('make request command', function () {
    $name = 'TestRequest';
    $path = app_path("Http/Requests/Api/{$name}.php");
    if (file_exists($path)) {
        unlink($path);
    }

    $this->artisan('make:request-api', ['name' => 'TestRequest'])
        ->assertSuccessful();

    $this->assertFileExists($path);

    unlink($path);
});
