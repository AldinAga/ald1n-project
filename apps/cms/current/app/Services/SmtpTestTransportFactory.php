<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Builds the one-off transport for the SuperAdmin test e-mail. A separate
 * MailManager is used so the shared application mailers are not touched.
 * Feature tests replace this binding with a fake transport.
 *
 * @author Ald1n <https://ald1n.com>
 */
class SmtpTestTransportFactory
{
    /** @param array<string,mixed> $config */
    public function create(array $config): TransportInterface
    {
        return (new MailManager(app()))->createSymfonyTransport($config);
    }
}
