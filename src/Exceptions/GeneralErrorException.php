<?php

namespace MMAE\ApiResponse\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Contracts\ExceptionContract;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class GeneralErrorException extends Exception implements ExceptionContract
{
    use HasApiResponse;

    public function __construct(
        private readonly ?string $customMessage = null,
        private readonly int $statusCode = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
        ?Throwable $previous = null,
    ) {
        parent::__construct($customMessage ?? trans('apiresponse::messages.something_went_wrong'), $statusCode, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        if (config('app.debug') && $this->getPrevious()) {
            return $this->withDebugTrace(
                $this->failedMessageResponse($this->getPrevious()->getMessage(), $this->statusCode),
                $this->getPrevious(),
            );
        }

        $message = $this->customMessage ?? trans('apiresponse::messages.something_went_wrong');

        return $this->failedMessageResponse($message, $this->statusCode);
    }
}
