<?php

namespace MMAE\ApiResponse\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Contracts\ExceptionContract;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class RouteNotFoundException extends Exception implements ExceptionContract
{
    use HasApiResponse;

    public function __construct(
        private readonly ?string $customMessage = null,
        private readonly int $statusCode = SymfonyResponse::HTTP_NOT_FOUND,
        ?Throwable $previous = null,
    ) {
        parent::__construct($customMessage ?? trans('apiresponse::messages.route_not_found'), previous: $previous);
    }

    public function render(Request $request): JsonResponse
    {
        $message = $this->customMessage ?? trans('apiresponse::messages.route_not_found');

        if (config('app.debug') && $this->getPrevious()) {
            return $this->withDebugTrace(
                $this->failedMessageResponse($message, $this->statusCode),
                $this->getPrevious(),
            );
        }

        return $this->failedMessageResponse($message, $this->statusCode);
    }
}
