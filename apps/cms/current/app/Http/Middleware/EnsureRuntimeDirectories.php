<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnsureRuntimeDirectories
{
    public function handle(Request $request, Closure $next): Response
    {
        $unavailable = [];

        foreach ([
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/cache/data' => storage_path('framework/cache/data'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ] as $relative => $directory) {
            try {
                if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
                    $unavailable[] = $relative;
                    continue;
                }

                @chmod($directory, 0775);
                if (!is_writable($directory)) {
                    $unavailable[] = $relative;
                }
            } catch (Throwable) {
                $unavailable[] = $relative;
            }
        }

        if ($unavailable !== []) {
            $message = "Ald1n CMS runtime direktorijumi nisu upisivi: ".implode(', ', array_unique($unavailable)).".\n"
                ."Administrator treba da pokrene: php artisan app:deployment-check --repair\n";

            return new Response($message, Response::HTTP_SERVICE_UNAVAILABLE, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store, private',
                'Retry-After' => '60',
                'X-Ald1n-Runtime-Status' => 'storage-unavailable',
            ]);
        }

        return $next($request);
    }
}
