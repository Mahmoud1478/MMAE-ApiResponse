<?php

namespace MMAE\ApiResponse\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Contracts\ExceptionContract;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ModelNotFoundException extends Exception implements ExceptionContract
{
    use HasApiResponse;

    public function __construct(
        private readonly ?string $customMessage = null,
        private readonly int $statusCode = SymfonyResponse::HTTP_NOT_FOUND,
        ?Throwable $previous = null,
    ) {
        parent::__construct($customMessage ?? trans('apiresponse::messages.resource_not_found'), previous: $previous);
    }

    public function render(Request $request): JsonResponse
    {
        if (config('app.debug') && $this->getPrevious()) {
            return $this->withDebugTrace(
                $this->failedMessageResponse($this->debugMessage($this->getPrevious()), $this->statusCode),
                $this->getPrevious(),
            );
        }

        $message = $this->customMessage ?? trans('apiresponse::messages.resource_not_found');

        return $this->failedMessageResponse($message, $this->statusCode);
    }

    /**
     * Builds the debug message for the previous exception, using the model's
     * short class name instead of its fully-qualified name when applicable.
     */
    private function debugMessage(Throwable $previous): string
    {
        if (! $previous instanceof EloquentModelNotFoundException) {
            return $previous->getMessage();
        }

        $message = 'No query results for model ['.class_basename($previous->getModel()).']';

        return $previous->getIds() === []
            ? $message.'.'
            : $message.' '.implode(', ', $previous->getIds());
    }
}
