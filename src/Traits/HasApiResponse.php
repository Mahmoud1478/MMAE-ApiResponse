<?php

namespace MMAE\ApiResponse\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use MMAE\ApiResponse\Configurations\Response;
use MMAE\ApiResponse\Contracts\ExceptionContract;
use MMAE\ApiResponse\Exceptions\GeneralErrorException;
use MMAE\ApiResponse\Exceptions\ModelNotFoundException;
use MMAE\ApiResponse\Exceptions\RouteNotFoundException;
use MMAE\ApiResponse\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

trait HasApiResponse
{
    /**
     * Builds the standard JSON envelope response: {success, data, errors, message, token}.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>|JsonResource|LengthAwarePaginator<array-key, mixed>|Model  $data
     * @param  array<array-key, mixed>|Collection<array-key, mixed>  $errors
     */
    final protected function makeResponse(
        bool $success,
        array|Collection|JsonResource|LengthAwarePaginator|Model $data = [],
        string $message = '',
        array|Collection $errors = [],
        string $token = '',
        int $statusCode = SymfonyResponse::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'errors' => (object) $errors,
            'message' => $message,
            'token' => $token,
        ], $statusCode);
    }

    /**
     * Dumps the response envelope and halts execution. JSON-expecting clients get a
     * pretty-printed JSON dump instead of the HTML VarDumper output so the payload
     * stays parseable/readable for API consumers. Pass $token to include it in the
     * dumped envelope; omit it to leave the token out.
     *
     * @param  array<array-key, mixed>  $args
     */
    private function dumpAndDie(array $args, string $token = ''): never
    {
        $response = $this->makeResponse(
            success: false,
            data: $args,
            message: 'Debugging',
            errors: [],
            token: $token,
            statusCode: SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
        );
        $response->setEncodingOptions(JSON_PRETTY_PRINT)->send();
        exit;
    }

    /**
     * Builds a successful envelope response carrying data.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>|JsonResource|LengthAwarePaginator<array-key, mixed>|Model  $data
     */
    protected function successResponse(array|Collection|JsonResource|LengthAwarePaginator|Model $data, int $statusCode = SymfonyResponse::HTTP_OK): JsonResponse
    {
        return $this->makeResponse(true, $data, statusCode: $statusCode);
    }

    /**
     * Builds a successful envelope response carrying data plus an auth token.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>|JsonResource|LengthAwarePaginator<array-key, mixed>  $data
     */
    protected function successResponseWithToken(
        array|Collection|JsonResource|LengthAwarePaginator $data,
        string $token,
        string $message = '',
        int $statusCode = SymfonyResponse::HTTP_OK,
    ): JsonResponse {
        return $this->makeResponse(true, $data, message: $message, token: $token, statusCode: $statusCode);
    }

    /**
     * Builds a successful "user registered" envelope response carrying data plus an auth
     * token. Defaults to 201 Created.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>|JsonResource|LengthAwarePaginator<array-key, mixed>  $data
     */
    protected function registeredSuccessfullyResponse(
        array|Collection|JsonResource|LengthAwarePaginator $data,
        string $token,
        string $message = '',
        int $statusCode = SymfonyResponse::HTTP_CREATED,
    ): JsonResponse {
        return $this->makeResponse(true, $data, message: $message, token: $token, statusCode: $statusCode);
    }

    /**
     * Builds a successful envelope response carrying only a message (no data).
     */
    protected function successMessageResponse(string $message, int $statusCode = SymfonyResponse::HTTP_OK): JsonResponse
    {
        return $this->makeResponse(true, message: $message, statusCode: $statusCode);
    }

    /**
     * Builds a successful "resource created" envelope response. Defaults to 201 Created.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>|JsonResource|LengthAwarePaginator<array-key, mixed>|Model  $data
     */
    protected function createdResponse(
        array|Collection|JsonResource|LengthAwarePaginator|Model $data,
        string $message = '',
        int $statusCode = SymfonyResponse::HTTP_CREATED,
    ): JsonResponse {
        return $this->makeResponse(true, $data, message: $message, statusCode: $statusCode);
    }

    /**
     * Builds a successful "resource updated" envelope response. Defaults to 200 OK.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>|JsonResource|LengthAwarePaginator<array-key, mixed>|Model  $data
     */
    protected function updatedResponse(
        array|Collection|JsonResource|LengthAwarePaginator|Model $data,
        string $message = '',
        int $statusCode = SymfonyResponse::HTTP_OK,
    ): JsonResponse {
        return $this->makeResponse(true, $data, message: $message, statusCode: $statusCode);
    }

    /**
     * Builds a successful "resource deleted" envelope response (no data). Defaults to 200 OK
     * rather than 204 No Content so the envelope body (success/message) still reaches the client.
     */
    protected function deletedResponse(string $message = '', int $statusCode = SymfonyResponse::HTTP_OK): JsonResponse
    {
        return $this->makeResponse(true, message: $message, statusCode: $statusCode);
    }

    /**
     * Builds a failed envelope response carrying only a message (no errors).
     * Defaults to Response::$FAILED_STATUS when no status code is given.
     */
    protected function failedMessageResponse(string $message, ?int $statusCode = null): JsonResponse
    {
        return $this->makeResponse(false, message: $message, statusCode: $statusCode ?? Response::$FAILED_STATUS);
    }

    /**
     * Builds a failed envelope response carrying a message and structured errors.
     * Defaults to Response::$FAILED_STATUS when no status code is given.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>  $errors
     */
    protected function failedResponse(
        array|Collection $errors,
        string $message,
        ?int $statusCode = null,
    ): JsonResponse {
        return $this->makeResponse(false, message: $message, errors: $errors, statusCode: $statusCode ?? Response::$FAILED_STATUS);
    }

    /**
     * Renders any Throwable into the standard envelope response. Exceptions implementing
     * ExceptionContract render themselves; anything else gets wrapped in a
     * GeneralErrorException so the original exception is preserved as the "previous".
     * Only the unhandled fallback case is reported to the app's exception handler —
     * the other branches are expected/known conditions, not bugs worth logging.
     * $request defaults to the current request when omitted.
     */
    protected function exceptionResponse(Throwable $exception, ?Request $request = null): JsonResponse
    {
        $request ??= request();

        if ($exception instanceof ExceptionContract) {
            return $exception->render($request);
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->modelNotFoundExceptionResponse($request, previous: $exception);
        }

        if ($exception instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException) {
            return $this->routeNotFoundExceptionResponse($request, previous: $exception);
        }

        report($exception);

        return $this->generalErrorExceptionResponse($request, previous: $exception);
    }

    /**
     * Builds and renders a GeneralErrorException response.
     */
    protected function generalErrorExceptionResponse(
        Request $request,
        ?string $message = null,
        int $statusCode = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
        ?Throwable $previous = null,
    ): JsonResponse {
        return (new GeneralErrorException($message, $statusCode, $previous))->render($request);
    }

    /**
     * Builds and renders a ModelNotFoundException response.
     */
    protected function modelNotFoundExceptionResponse(
        Request $request,
        ?string $message = null,
        int $statusCode = SymfonyResponse::HTTP_NOT_FOUND,
        ?Throwable $previous = null,
    ): JsonResponse {
        return (new ModelNotFoundException($message, $statusCode, $previous))->render($request);
    }

    /**
     * Builds and renders a RouteNotFoundException response.
     */
    protected function routeNotFoundExceptionResponse(
        Request $request,
        ?string $message = null,
        int $statusCode = SymfonyResponse::HTTP_NOT_FOUND,
        ?Throwable $previous = null,
    ): JsonResponse {
        return (new RouteNotFoundException($message, $statusCode, $previous))->render($request);
    }

    /**
     * Builds and renders a ValidationException response.
     *
     * @param  array<array-key, mixed>|Collection<array-key, mixed>  $errors
     */
    protected function validationExceptionResponse(
        Request $request,
        array|Collection $errors,
        ?string $message = null,
        int $statusCode = SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
        ?Throwable $previous = null,
    ): JsonResponse {
        return (new ValidationException($errors, $message, $statusCode, $previous))->render($request);
    }

    /**
     * Appends a "debug" block (exception class, file, line, trace) to an already-built
     * envelope response. Only ever called when config('app.debug') is true. Frame args
     * are stripped since they may contain sensitive values (passwords, tokens, etc.).
     */
    protected function withDebugTrace(JsonResponse $response, Throwable $source): JsonResponse
    {
        $data = $response->getData(true);
        $data = is_array($data) ? $data : [];
        $data['errors'] = (object) ($data['errors'] ?? []);

        return $response->setData([
            ...$data,
            'debug' => [
                'exception' => $source::class,
                'file' => $source->getFile(),
                'line' => $source->getLine(),
                'trace' => collect($source->getTrace())
                    ->map(fn (array $frame): array => Arr::except($frame, ['args']))
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
