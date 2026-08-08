<?php

declare(strict_types=1);

use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureRuntimeDirectories;
use App\Http\Middleware\EnsureTrackedPortalSession;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\SecurityHeaders;
use App\Support\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(EnsureRuntimeDirectories::class);
        $middleware->prepend(AttachRequestId::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'tracked-session' => EnsureTrackedPortalSession::class,
            'permission' => RequirePermission::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, \Throwable $exception): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(static function (ValidationException $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiErrorResponse::make(
                $request,
                'Podaci nisu ispravni.',
                'validation_failed',
                422,
                $exception->errors(),
            );
        });

        $exceptions->render(static function (AuthenticationException $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiErrorResponse::make($request, 'Prijava je obavezna.', 'unauthenticated', 401);
        });

        $exceptions->render(static function (AuthorizationException $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiErrorResponse::make($request, 'Nemate potrebnu dozvolu.', 'forbidden', 403);
        });

        $exceptions->render(static function (ModelNotFoundException $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiErrorResponse::make($request, 'Traženi resurs nije pronađen.', 'not_found', 404);
        });

        $exceptions->render(static function (ThrottleRequestsException $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiErrorResponse::make(
                $request,
                'Previše zahteva. Pokušajte ponovo kasnije.',
                'rate_limited',
                429,
                headers: $exception->getHeaders(),
            );
        });

        $exceptions->render(static function (HttpExceptionInterface $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $status = $exception->getStatusCode();
            $code = match ($status) {
                400 => 'bad_request',
                401 => 'unauthenticated',
                403 => 'forbidden',
                404 => 'not_found',
                405 => 'method_not_allowed',
                409 => 'conflict',
                419 => 'session_expired',
                422 => 'unprocessable_entity',
                429 => 'rate_limited',
                503 => 'service_unavailable',
                default => 'http_error',
            };
            $message = $status >= 500 ? '' : trim($exception->getMessage());
            if ($message === '') {
                $message = match ($status) {
                    403 => 'Nemate potrebnu dozvolu.',
                    404 => 'Traženi resurs nije pronađen.',
                    405 => 'HTTP metoda nije podržana.',
                    429 => 'Previše zahteva. Pokušajte ponovo kasnije.',
                    500 => 'Došlo je do interne greške.',
                    503 => 'Usluga trenutno nije dostupna.',
                    default => $status >= 500 ? 'Došlo je do interne greške.' : 'Zahtev nije moguće obraditi.',
                };
            }

            return ApiErrorResponse::make($request, $message, $code, $status, headers: $exception->getHeaders());
        });

        $exceptions->render(static function (\Throwable $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiErrorResponse::make(
                $request,
                'Došlo je do interne greške.',
                'server_error',
                500,
            );
        });
    })
    ->create();
