<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

/**
 * B885: route-preserving local redirect on malformed activation POSTs.
 * Never use an untrusted Referer, Host, forwarded authority or token path.
 */
final class CustomerActivationRequest extends LocalAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:80'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ];
    }

    public function localFormLocation(): string
    {
        $token = (string) $this->input('token', '');
        return preg_match('/\\A[A-Za-z0-9]{80}\\z/D', $token) === 1
            ? route('customer-activation.show', ['token' => $token], false)
            : route('login', [], false);
    }

    protected function getRedirectUrl(): string
    {
        return $this->localFormLocation();
    }
}
