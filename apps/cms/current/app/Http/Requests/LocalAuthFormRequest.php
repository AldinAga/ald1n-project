<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\AppUrlOrigin;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * B883: a failed (or neutral) POST of an unauthenticated auth form goes to
 * that form's own GET route on the APP_URL origin. Laravel's default for
 * failed validation and for back() is the Referer header, which an attacker
 * controls; the controllers therefore redirect through localRedirect().
 *
 * JSON requests keep the standard 422 response.
 *
 * @author Ald1n <https://ald1n.com>
 */
abstract class LocalAuthFormRequest extends FormRequest
{
    /** @return array{0:string,1:array<string,string>} route name and parameters of the form's GET page */
    abstract protected function formRoute(): array;

    public function localRedirect(): RedirectResponse
    {
        [$route, $parameters] = $this->formRoute();

        return AppUrlOrigin::redirect($this, $route, $parameters);
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new ValidationException($validator, $this->localRedirect()
            ->withErrors($validator, $this->errorBag)
            // Only what the form echoes back; never a password or token.
            ->withInput(array_filter($this->only(['login', 'email']), 'is_string')));
    }

    /**
     * The token page when the posted token has the shape of a real one, else
     * the fallback page. Only [A-Za-z0-9]{80} ever reaches a URL, so the
     * Location cannot carry attacker-controlled path or query text.
     *
     * @return array{0:string,1:array<string,string>}
     */
    protected function tokenFormRoute(string $tokenRoute, string $fallbackRoute): array
    {
        $token = $this->input('token');

        return is_string($token) && preg_match('/\A[A-Za-z0-9]{80}\z/D', $token) === 1
            ? [$tokenRoute, ['token' => $token]]
            : [$fallbackRoute, []];
    }
}
