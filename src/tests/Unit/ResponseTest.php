<?php

use MMAE\ApiResponse\Configurations\Response;

test('structure', function () {
    $response = json_decode($this->successResponse([])->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($response)->toBeArray()
        ->toHaveKeys(['success', 'data', 'errors', 'message', 'token']);
});

test('status config changes failed response status', function () {
    Response::$FAILED_STATUS = 200;

    $response = $this->failedResponse([], '');

    expect($response->status())->toBe(200);
});

test('status param changes success response status', function () {
    $response = $this->successResponse([], 201);

    expect($response->status())->toBe(201);
});

test('status param changes failed response status', function () {
    $response = $this->failedResponse([], '', 500);

    expect($response->status())->toBe(500);
});

test('created response defaults to 201 status', function () {
    $response = $this->createdResponse([]);

    expect($response->status())->toBe(201);
});

test('created response status param overrides default', function () {
    $response = $this->createdResponse([], statusCode: 200);

    expect($response->status())->toBe(200);
});

test('updated response defaults to 200 status', function () {
    $response = $this->updatedResponse([]);

    expect($response->status())->toBe(200);
});

test('updated response status param overrides default', function () {
    $response = $this->updatedResponse([], statusCode: 202);

    expect($response->status())->toBe(202);
});

test('deleted response defaults to 200 status with no data', function () {
    $response = json_decode($this->deletedResponse('Deleted successfully')->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($response['success'])->toBeTrue()
        ->and($response['data'])->toBe([])
        ->and($response['message'])->toBe('Deleted successfully');
});

test('deleted response status param overrides default', function () {
    $response = $this->deletedResponse(statusCode: 204);

    expect($response->status())->toBe(204);
});

test('registered successfully response defaults to 201 status and carries data, message, token', function () {
    $response = json_decode(
        $this->registeredSuccessfullyResponse(['id' => 1], 'the-token', 'Registered successfully')->content(),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($response['data'])->toBe(['id' => 1])
        ->and($response['message'])->toBe('Registered successfully')
        ->and($response['token'])->toBe('the-token');
});

test('registered successfully response status param overrides default', function () {
    $response = $this->registeredSuccessfullyResponse([], 'the-token', statusCode: 200);

    expect($response->status())->toBe(200);
});

test('validation response', function () {
    $response = $this->get('/request-response');

    $response->assertStatus(Response::$VALIDATION_FAILED_STATUS);
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', trans('apiresponse::messages.validation_failed'));
    $response->assertJsonPath('errors.name.0', 'The name field is required.');
});
