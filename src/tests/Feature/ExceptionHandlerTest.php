<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MMAE\ApiResponse\Configurations\Response;

test('validation exception renders api response', function () {
    $response = $this->getJson('/throws-validation-exception');

    $response->assertStatus(Response::$VALIDATION_FAILED_STATUS);
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('errors.name.0', 'The name field is required.');
});

test('model not found exception renders api response', function () {
    config(['app.debug' => false]);

    $response = $this->getJson('/throws-model-not-found-exception');

    $response->assertNotFound();
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'Resource not found');
    $response->assertJsonMissingPath('debug');
});

test('route not found exception renders api response', function () {
    config(['app.debug' => false]);

    $response = $this->getJson('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'Route not found');
    $response->assertJsonMissingPath('debug');
});

test('general error exception hides message and trace when debug is off', function () {
    config(['app.debug' => false]);

    $response = $this->getJson('/throws-general-error-exception');

    $response->assertServerError();
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'Something went wrong');
    $response->assertJsonMissingPath('debug');
    $response->assertDontSee('boom');
});

test('general error exception shows raw message and trace when debug is on', function () {
    config(['app.debug' => true]);

    $response = $this->getJson('/throws-general-error-exception');

    $response->assertServerError();
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'boom');
    $response->assertJsonPath('debug.exception', Exception::class);
    $response->assertJsonStructure(['debug' => ['exception', 'file', 'line', 'trace']]);
});

test('model not found exception shows raw message and trace when debug is on', function () {
    config(['app.debug' => true]);

    $response = $this->getJson('/throws-model-not-found-exception');

    $response->assertNotFound();
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'No query results for model [DummyModel] 1');
    $response->assertJsonPath('debug.exception', ModelNotFoundException::class);
    $response->assertJsonStructure(['debug' => ['exception', 'file', 'line', 'trace']]);
});

test('validation exception shows raw message and trace when debug is on', function () {
    config(['app.debug' => true]);

    $response = $this->getJson('/throws-validation-exception');

    $response->assertStatus(Response::$VALIDATION_FAILED_STATUS);
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('errors.name.0', 'The name field is required.');
    $response->assertJsonStructure(['debug' => ['exception', 'file', 'line', 'trace']]);
});

test('debug trace frames never leak call arguments', function () {
    config(['app.debug' => true]);

    $response = $this->getJson('/throws-general-error-exception');

    $trace = $response->json('debug.trace');

    expect($trace)->toBeArray()->not->toBeEmpty();

    foreach ($trace as $frame) {
        expect($frame)->not->toHaveKey('args');
    }
});

test('non-json requests are not handled by the api response handler', function () {
    $response = $this->get('/throws-model-not-found-exception');

    $response->assertNotFound();
    $response->assertDontSee('"success"', false);
});

test('exception messages are translated per locale', function () {
    config(['app.debug' => false]);
    app()->setLocale('ar');

    $response = $this->getJson('/throws-model-not-found-exception');

    $response->assertJsonPath('message', trans('apiresponse::messages.resource_not_found', locale: 'ar'));
});

test('validation failed message can still be overridden', function () {
    Response::$VALIDATION_FAILED_MESSAGE = 'Custom validation message';

    // /throws-validation-exception uses $request->validate() directly, whose exception
    // already carries Laravel's own message — it never reaches the override fallback.
    // /request-response goes through ApiRequest, which passes no message and lets the
    // fallback chain (explicit message -> Response override -> translation) apply.
    $response = $this->getJson('/request-response');

    $response->assertJsonPath('message', 'Custom validation message');

    Response::$VALIDATION_FAILED_MESSAGE = null;
});
