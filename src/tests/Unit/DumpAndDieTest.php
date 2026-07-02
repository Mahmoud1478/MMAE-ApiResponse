<?php

use Symfony\Component\Process\Process;

test('dumpAndDie sends pretty-printed json and halts execution', function () {
    $process = new Process([PHP_BINARY, __DIR__.'/../Fixtures/dump-and-die-script.php']);
    $process->run();

    expect($process->isSuccessful())->toBeTrue();

    $output = $process->getOutput();

    expect($output)->toContain("\n    \"success\": false");

    $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)->toBe([
        'success' => false,
        'data' => ['foo' => 'bar'],
        'errors' => [],
        'message' => 'Debugging',
        'token' => 'test-token',
    ]);
});
