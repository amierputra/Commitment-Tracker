<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    public function successResponse($data = null, string $message = "", int $statusCode = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge(
                [
                    'timestamp' => now()->toDateTimeString(),
                ],
                $meta
            ),
        ], $statusCode);
    }

    public function errorResponse(string $message = "", int $statusCode = 400, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'meta' => array_merge(
                [
                    'timestamp' => now()->toDateTimeString(),
                ],
                $meta
            ),
        ], $statusCode);
    }
}
