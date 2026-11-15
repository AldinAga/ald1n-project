<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

/**
 * B883: replaces the inline validate() of the account activation POST so a
 * failure goes to the activation page instead of the Referer.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class CustomerActivationRequest extends LocalAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** The token page, or the login page when the token is malformed. */
    protected function formRoute(): array
    {
        return $this->tokenFormRoute('customer-activation.show', 'login');
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:80'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ];
    }
}
