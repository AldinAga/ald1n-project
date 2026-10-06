<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Passkeys\Passkey;

final class RestoreCredential extends Passkey
{
    protected $table = 'restore_credentials';
}
