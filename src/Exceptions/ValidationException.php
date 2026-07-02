<?php

namespace MMAE\ApiResponse\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use MMAE\ApiResponse\Configurations\Response;
use MMAE\ApiResponse\Contracts\ExceptionContract;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ValidationException extends Exception implements ExceptionContract
{
    use HasApiResponse;

    /**
     * @param  array<array-key, mixed>|Collection<array-key, mixed>  $errors
     */
    public function __construct(
        private readonly array|Collection $errors,
        private readonly ?string $customMessage = null,
        private readonly int $statusCode = SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
        ?Throwable $previous = null,
    ) {
        parent::__construct($this->resolveMessage(), previous: $previous);
    }

    public function render(Request $request): JsonResponse
    {
        if (config('app.debug') && $this->getPrevious()) {
            return $this->withDebugTrace(
                $this->failedResponse($this->errors, $this->getPrevious()->getMessage(), $this->statusCode),
                $this->getPrevious(),
            );
        }

        return $this->failedResponse($this->errors, $this->resolveMessage(), $this->statusCode);
    }

    private function resolveMessage(): string
    {
        return $this->customMessage ?: (Response::$VALIDATION_FAILED_MESSAGE ?? trans('apiresponse::messages.validation_failed'));
    }
}
