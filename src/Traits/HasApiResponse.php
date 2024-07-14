<?php

namespace MMAE\ApiResponse\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use \Illuminate\Pagination\LengthAwarePaginator;
use MMAE\ApiResponse\Configurations\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;


trait HasApiResponse
{

    private function makeResponse(
        bool                                                     $success,
        array|Collection|JsonResource|LengthAwarePaginator|Model $data = [],
        string                                                   $message = '',
        array|Collection                                         $errors = [],
        string                                                   $token = '',
        int                                                      $statusCode = SymfonyResponse::HTTP_OK,
    ): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'errors' => (object) $errors,
            'message' => $message,
            'token' => $token,
        ], $statusCode);
    }

    private function successResponse(array|Collection|JsonResource|LengthAwarePaginator|Model $data, int $statusCode = SymfonyResponse::HTTP_OK): JsonResponse
    {
        return $this->makeResponse(true, $data, statusCode: $statusCode);
    }

    private function successResponseWithToken(
        array|Collection|JsonResource|LengthAwarePaginator $data,
        string                                             $token,
        int                                                $statusCode = SymfonyResponse::HTTP_OK,
    ): JsonResponse
    {
        return $this->makeResponse(true, $data, token: $token, statusCode: $statusCode);
    }

    private function successMessageResponse(string $message, int $statusCode = SymfonyResponse::HTTP_OK,): JsonResponse
    {
        return $this->makeResponse(true, message: $message, statusCode: $statusCode);
    }

    private function failedMessageResponse(string $message, ?int $statusCode = null ): JsonResponse
    {
        return $this->makeResponse(false, message: $message, statusCode: $statusCode ?? Response::$FAILED_STATUS);
    }

    private function failedResponse(
        array|Collection|JsonResource|LengthAwarePaginator|Model $errors,
        string                                                   $message,
        ?int                                                   $statusCode = null,
    ): JsonResponse
    {
        return $this->makeResponse(false, message: $message, errors: $errors, statusCode: $statusCode?? Response::$FAILED_STATUS);
    }
}
