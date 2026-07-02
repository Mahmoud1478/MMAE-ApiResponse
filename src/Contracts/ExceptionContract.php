<?php

declare(strict_types=1);

namespace MMAE\ApiResponse\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface ExceptionContract
{
    public function render(Request $request): JsonResponse;
}
