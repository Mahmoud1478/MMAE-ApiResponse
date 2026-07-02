<?php

use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;

function throwingHelper(string $secret): never
{
    throw new RuntimeException('boom');
}

test('withDebugTrace appends debug block and strips frame args', function () {
    $subject = new class
    {
        use HasApiResponse;

        public function expose(JsonResponse $response, Throwable $source): JsonResponse
        {
            return $this->withDebugTrace($response, $source);
        }
    };

    $response = new JsonResponse([
        'success' => false,
        'data' => [],
        'errors' => (object) [],
        'message' => 'oops',
        'token' => '',
    ]);

    try {
        throwingHelper('super-secret-value');
    } catch (Throwable $source) {
        $result = $subject->expose($response, $source);
    }

    $data = $result->getData(true);

    expect($data)->toHaveKey('debug')
        ->and($data['debug']['exception'])->toBe($source::class)
        ->and($data['debug']['file'])->toBe($source->getFile())
        ->and($data['debug']['line'])->toBe($source->getLine())
        ->and($data['debug']['trace'])->toBeArray()->not->toBeEmpty();

    foreach ($data['debug']['trace'] as $frame) {
        expect($frame)->not->toHaveKey('args');
    }

    expect(json_encode($data['debug']['trace']))->not->toContain('super-secret-value');

    expect($result->getContent())->toContain('"errors":{}');
});
