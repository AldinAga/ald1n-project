<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApiErrorResponse
{
    /** @param array<string,list<string>> $errors */
    public static function make(
        Request $request,
        string $message,
        string $code,
        int $status,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'message' => $message,
            'code' => $code,
            'request_id' => self::requestId($request),
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, $headers);
    }

    private static function requestId(Request $request): ?string
    {
        $requestId = trim((string) $request->attributes->get('request_id'));

        return $requestId !== '' ? $requestId : null;
    }
}
