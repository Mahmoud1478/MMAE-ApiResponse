<?php

namespace MMAE\ApiResponse\Configurations;

use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use MMAE\ApiResponse\Exceptions\GeneralErrorException;
use MMAE\ApiResponse\Exceptions\ModelNotFoundException;
use MMAE\ApiResponse\Exceptions\RouteNotFoundException;
use MMAE\ApiResponse\Exceptions\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Response
{
    public static int $VALIDATION_FAILED_STATUS = 422;

    public static int $FAILED_STATUS = 400;

    /** Null uses the translated `apiresponse::messages.validation_failed` line; set to override. */
    public static ?string $VALIDATION_FAILED_MESSAGE = null;

    public static function handleExceptions(Exceptions $exceptions): void
    {
        $exceptions->render(function (LaravelValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return (new ValidationException($e->errors(), statusCode: self::$VALIDATION_FAILED_STATUS, previous: $e))->render($request);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return;
            }

            $source = $e->getPrevious() ?? $e;

            if ($source instanceof EloquentModelNotFoundException) {
                return (new ModelNotFoundException(previous: $source))->render($request);
            }

            return (new RouteNotFoundException(previous: $source))->render($request);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return (new GeneralErrorException(previous: $e))->render($request);
            }
        });
    }
}
