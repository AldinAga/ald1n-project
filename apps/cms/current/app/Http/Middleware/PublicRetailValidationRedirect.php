<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registration and checkout reject user-controlled Referer as an error
 * destination. Only installed on the two explicit public HTML POST routes.
 * JSON keeps Laravel's native 422; storefront OFF/auth errors are untouched.
 */
final class PublicRetailValidationRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $exception) {
            if ($request->expectsJson() || !$request->isMethod('POST')) {
                throw $exception;
            }

            $route=$request->route()?->getName();
            $target=match ($route) {
                'public-shop.register.submit' => '/registracija',
                'public-shop.checkout.submit' => '/porucivanje',
                default => null,
            };
            if ($target===null) {
                throw $exception;
            }

            // Never flash CSRF/checkout nonce, policy digest, Turnstile response,
            // password or private receipt capability into redirect/session input.
            $allowed=$route==='public-shop.register.submit'
                ? ['first_name','last_name','email','phone']
                : ['shipping_full_name','email','shipping_phone','shipping_address',
                   'shipping_city','shipping_postal_code','payment_method',
                   'delivery_method','customer_note'];

            return (new RedirectResponse($target))
                ->setSession($request->session())
                ->setRequest($request)
                ->withErrors($exception->errors())
                ->withInput($request->only($allowed));
        }
    }
}
