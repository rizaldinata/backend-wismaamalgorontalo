<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;

trait ApiResponse
{
    protected function apiSuccess($data = null, string $message = 'Success', int $code = 200, $meta = null): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    protected function apiError(string $message, int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
