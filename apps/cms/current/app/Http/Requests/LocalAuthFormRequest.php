<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * B887: failed FormRequest validation on sensitive HTML POSTs must not use
 * Redirector::to(), which absolutizes an otherwise-relative target.
 * Preserve standard JSON 422 and existing session error/old-input flashes.
 */
abstract class LocalAuthFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
            return;
        }

        $target=$this->getRedirectUrl();
        if (!str_starts_with($target,'/') || str_starts_with($target,'//')
            || str_contains($target,'\\') || preg_match('/[\x00-\x20\x7f]/',$target)) {
            throw new \LogicException('B887_AUTH_REDIRECT_TARGET_NOT_RELATIVE');
        }

        $response=(new RedirectResponse($target))
            ->setSession($this->session())
            ->setRequest($this)
            ->withErrors($validator,$this->errorBag)
            // No password, password confirmation, Turnstile answer or bearer
            // token is ever copied into the old-input flash session.
            ->withInput($this->only(['login','email','first_name','last_name','phone']));

        throw new ValidationException($validator,$response);
    }
}
