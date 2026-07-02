<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Exceptions\GeneralErrorException;
use MMAE\ApiResponse\Exceptions\ModelNotFoundException;
use MMAE\ApiResponse\Exceptions\RouteNotFoundException;
use MMAE\ApiResponse\Exceptions\ValidationException;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

beforeEach(function () {
    $this->subject = new class
    {
        use HasApiResponse;

        public function expose(Throwable $exception, Request $request): JsonResponse
        {
            return $this->exceptionResponse($exception, $request);
        }

        public function exposeWithoutRequest(Throwable $exception): JsonResponse
        {
            return $this->exceptionResponse($exception);
        }

        public function generalError(
            Request $request,
            ?string $message = null,
            int $statusCode = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
            ?Throwable $previous = null,
        ): JsonResponse {
            return $this->generalErrorExceptionResponse($request, $message, $statusCode, $previous);
        }

        public function modelNotFound(Request $request, ?string $message = null, int $statusCode = SymfonyResponse::HTTP_NOT_FOUND): JsonResponse
        {
            return $this->modelNotFoundExceptionResponse($request, $message, $statusCode);
        }

        public function routeNotFound(Request $request, ?string $message = null, int $statusCode = SymfonyResponse::HTTP_NOT_FOUND): JsonResponse
        {
            return $this->routeNotFoundExceptionResponse($request, $message, $statusCode);
        }

        public function validation(Request $request, array $errors, ?string $message = null, int $statusCode = SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
        {
            return $this->validationExceptionResponse($request, $errors, $message, $statusCode);
        }
    };

    $this->request = Request::create('/');
});

test('renders general error exception via its own render method', function () {
    $exception = new GeneralErrorException('custom message', SymfonyResponse::HTTP_BAD_GATEWAY);

    $response = $this->subject->expose($exception, $this->request);
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_BAD_GATEWAY)
        ->and($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('custom message');
});

test('renders model not found exception via its own render method', function () {
    $exception = new ModelNotFoundException;

    $response = $this->subject->expose($exception, $this->request);
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_NOT_FOUND)
        ->and($data['message'])->toBe(trans('apiresponse::messages.resource_not_found'));
});

test('renders route not found exception via its own render method', function () {
    $exception = new RouteNotFoundException;

    $response = $this->subject->expose($exception, $this->request);
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_NOT_FOUND)
        ->and($data['message'])->toBe(trans('apiresponse::messages.route_not_found'));
});

test('renders validation exception via its own render method with errors', function () {
    $exception = new ValidationException(['name' => ['The name field is required.']]);

    $response = $this->subject->expose($exception, $this->request);
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors']['name'][0])->toBe('The name field is required.');
});

test('wraps unknown throwable in a general error exception, hiding message when debug is off', function () {
    config(['app.debug' => false]);

    $exception = new RuntimeException('raw boom');

    $response = $this->subject->expose($exception, $this->request);
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR)
        ->and($data['success'])->toBeFalse()
        ->and($data['message'])->toBe(trans('apiresponse::messages.something_went_wrong'))
        ->and($data)->not->toHaveKey('debug');
});

test('wraps unknown throwable in a general error exception, exposing previous message and trace when debug is on', function () {
    config(['app.debug' => true]);

    $exception = new RuntimeException('raw boom');

    $response = $this->subject->expose($exception, $this->request);
    $data = $response->getData(true);

    expect($data['message'])->toBe('raw boom')
        ->and($data['debug']['exception'])->toBe(RuntimeException::class);
});

test('reports unknown throwables to the exception handler', function () {
    $exception = new RuntimeException('raw boom');

    $this->mock(ExceptionHandler::class)
        ->shouldReceive('report')
        ->once()
        ->with($exception);

    $this->subject->expose($exception, $this->request);
});

test('does not report exceptions that render themselves', function () {
    $this->mock(ExceptionHandler::class)->shouldNotReceive('report');

    $this->subject->expose(new GeneralErrorException, $this->request);
    $this->subject->expose(new ModelNotFoundException, $this->request);
    $this->subject->expose(new RouteNotFoundException, $this->request);
    $this->subject->expose(new ValidationException(['name' => ['required']]), $this->request);
});

test('exceptionResponse resolves the current request when none is given', function () {
    $exception = new ModelNotFoundException;

    $response = $this->subject->exposeWithoutRequest($exception);
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_NOT_FOUND)
        ->and($data['message'])->toBe(trans('apiresponse::messages.resource_not_found'));
});

test('generalErrorExceptionResponse builds and renders a general error exception', function () {
    $response = $this->subject->generalError($this->request, 'custom message', SymfonyResponse::HTTP_BAD_GATEWAY);
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_BAD_GATEWAY)
        ->and($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('custom message');
});

test('generalErrorExceptionResponse preserves the previous exception in the debug trace', function () {
    config(['app.debug' => true]);

    $previous = new RuntimeException('raw boom');

    $response = $this->subject->generalError($this->request, previous: $previous);
    $data = $response->getData(true);

    expect($data['message'])->toBe('raw boom')
        ->and($data['debug']['exception'])->toBe(RuntimeException::class);
});

test('modelNotFoundExceptionResponse builds and renders a model not found exception', function () {
    $response = $this->subject->modelNotFound($this->request, 'custom message');
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_NOT_FOUND)
        ->and($data['message'])->toBe('custom message');
});

test('modelNotFoundExceptionResponse defaults to the translated message', function () {
    $response = $this->subject->modelNotFound($this->request);
    $data = $response->getData(true);

    expect($data['message'])->toBe(trans('apiresponse::messages.resource_not_found'));
});

test('routeNotFoundExceptionResponse builds and renders a route not found exception', function () {
    $response = $this->subject->routeNotFound($this->request, 'custom message');
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_NOT_FOUND)
        ->and($data['message'])->toBe('custom message');
});

test('routeNotFoundExceptionResponse defaults to the translated message', function () {
    $response = $this->subject->routeNotFound($this->request);
    $data = $response->getData(true);

    expect($data['message'])->toBe(trans('apiresponse::messages.route_not_found'));
});

test('validationExceptionResponse builds and renders a validation exception with errors', function () {
    $response = $this->subject->validation($this->request, ['name' => ['The name field is required.']], 'custom message');
    $data = $response->getData(true);

    expect($response->status())->toBe(SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['message'])->toBe('custom message')
        ->and($data['errors']['name'][0])->toBe('The name field is required.');
});

test('validationExceptionResponse accepts a custom status code', function () {
    $response = $this->subject->validation($this->request, ['name' => ['required']], statusCode: SymfonyResponse::HTTP_BAD_REQUEST);

    expect($response->status())->toBe(SymfonyResponse::HTTP_BAD_REQUEST);
});
