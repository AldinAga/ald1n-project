<?php

declare(strict_types=1);

namespace Tests\Unit;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Tests\TestCase;
use Throwable;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Restore Credentials registration and sign-in through the same laravel/passkeys + webauthn-lib calls
 * RestoreCredentialController makes, with a software ES256 authenticator and "none" attestation.
 * No database: it pins the library contract, the HTTP layer is covered by the controller itself.
 */
final class RestoreCredentialWebAuthnCeremonyTest extends TestCase
{
    private const RP_ID = 'cms.ald1n.com';

    // Synthetic APK signing hash; not a credential.
    private const ORIGIN = 'android:apk-key-hash:synthetic-test-apk-hash';

    // UP | BE | BS: Android restore credentials are created with cloud backup and without user verification.
    private const FLAGS = 0x01 | 0x08 | 0x10;

    private OpenSSLAsymmetricKey $key;

    private string $credentialId;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('passkeys.relying_party_id', self::RP_ID);
        config()->set('passkeys.allowed_origins', [self::ORIGIN]);
        WebAuthn::flush();

        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $key);
        $this->key = $key;
        $this->credentialId = random_bytes(32);
    }

    public function test_registration_and_sign_in_round_trip_through_stored_json(): void
    {
        $record = $this->register();

        // Persisted as JSON by StorePasskey and read back by VerifyPasskey.
        $stored = WebAuthn::fromJson(WebAuthn::toJson($record), CredentialRecord::class);
        self::assertSame($this->credentialId, $stored->publicKeyCredentialId);
        self::assertTrue($stored->backupEligible);

        $updated = $this->signIn($stored, counter: 1);
        self::assertSame(1, $updated->counter);
    }

    public function test_sign_in_rejects_a_tampered_signature(): void
    {
        $record = $this->register();

        $this->assertRejected(fn () => $this->signIn($record, counter: 1, tamperSignature: true), 'Invalid signature');
    }

    public function test_sign_in_rejects_an_assertion_from_another_origin(): void
    {
        $record = $this->register();

        $this->assertRejected(fn () => $this->signIn($record, counter: 1, origin: 'https://evil.example'), 'Invalid origin');
    }

    public function test_sign_in_rejects_a_changed_backup_eligibility_flag(): void
    {
        $record = $this->register();

        $this->assertRejected(fn () => $this->signIn($record, counter: 1, flags: 0x01), 'backup eligibility flag');
    }

    public function test_registration_rejects_standard_base64_in_the_credential(): void
    {
        $payload = $this->registrationPayload($this->registrationOptions());
        // "+" belongs to standard base64, not to base64url; keep the length so only the alphabet is wrong.
        $payload['response']['clientDataJSON'] = '+'.substr($payload['response']['clientDataJSON'], 1);

        $this->assertRejected(fn () => WebAuthn::fromJson(json_encode($payload, JSON_THROW_ON_ERROR), PublicKeyCredential::class), 'base64');
    }

    private function register(): CredentialRecord
    {
        $options = $this->registrationOptions();
        // RestoreCredentialController caches the options as JSON between the two requests.
        $options = WebAuthn::fromJson(WebAuthn::toJson($options), PublicKeyCredentialCreationOptions::class);
        $credential = WebAuthn::fromJson(json_encode($this->registrationPayload($options), JSON_THROW_ON_ERROR), PublicKeyCredential::class);

        return WebAuthn::attestationValidator()->check(
            authenticatorAttestationResponse: $credential->response,
            publicKeyCredentialCreationOptions: $options,
            host: Passkeys::relyingPartyId(),
        );
    }

    private function signIn(
        CredentialRecord $record,
        int $counter,
        bool $tamperSignature = false,
        string $origin = self::ORIGIN,
        int $flags = self::FLAGS,
    ): CredentialRecord {
        // Same options RestoreCredentialController::authenticationOptions() issues.
        $options = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: [],
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            timeout: Passkeys::timeout(),
        );
        $options = WebAuthn::fromJson(WebAuthn::toJson($options), PublicKeyCredentialRequestOptions::class);

        $authData = hash('sha256', self::RP_ID, true).chr($flags).pack('N', $counter);
        $clientData = $this->clientData('webauthn.get', $options->challenge, $origin);
        openssl_sign($authData.hash('sha256', $clientData, true), $signature, $this->key, OPENSSL_ALGO_SHA256);
        if ($tamperSignature) {
            $signature[strlen($signature) - 1] = chr(ord($signature[strlen($signature) - 1]) ^ 0x01);
        }

        $payload = [
            'id' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
                'authenticatorData' => Base64UrlSafe::encodeUnpadded($authData),
                'signature' => Base64UrlSafe::encodeUnpadded($signature),
                'userHandle' => Base64UrlSafe::encodeUnpadded($record->userHandle),
            ],
        ];
        $credential = WebAuthn::fromJson(json_encode($payload, JSON_THROW_ON_ERROR), PublicKeyCredential::class);

        return WebAuthn::assertionValidator()->check(
            credentialRecord: $record,
            authenticatorAssertionResponse: $credential->response,
            publicKeyCredentialRequestOptions: $options,
            host: Passkeys::relyingPartyId(),
            userHandle: $record->userHandle,
        );
    }

    private function registrationOptions(): PublicKeyCredentialCreationOptions
    {
        return PublicKeyCredentialCreationOptions::create(
            rp: PublicKeyCredentialRpEntity::create('Ald1n', self::RP_ID),
            user: PublicKeyCredentialUserEntity::create('restore-user', random_bytes(32), 'Restore User'),
            challenge: random_bytes(32),
            pubKeyCredParams: [PublicKeyCredentialParameters::createPk(-7)],
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            ),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
        );
    }

    /** @return array{id: string, rawId: string, type: string, response: array<string, string>} */
    private function registrationPayload(PublicKeyCredentialCreationOptions $options): array
    {
        $ec = openssl_pkey_get_details($this->key)['ec'];
        $coseKey = MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))   // kty: EC2
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))  // alg: ES256
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))  // crv: P-256
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create(str_pad($ec['x'], 32, "\0", STR_PAD_LEFT)))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create(str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)));

        $authData = hash('sha256', self::RP_ID, true)
            .chr(self::FLAGS | 0x40)                 // AT: attested credential data included
            .pack('N', 0)
            .str_repeat("\0", 16)                    // AAGUID
            .pack('n', strlen($this->credentialId))
            .$this->credentialId
            .(string) $coseKey;

        $attestationObject = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($this->clientData('webauthn.create', $options->challenge, self::ORIGIN)),
                'attestationObject' => Base64UrlSafe::encodeUnpadded((string) $attestationObject),
            ],
        ];
    }

    private function clientData(string $type, string $challenge, string $origin): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function assertRejected(callable $ceremony, string $reason): void
    {
        try {
            $ceremony();
        } catch (Throwable $e) {
            self::assertStringContainsString($reason, $e->getMessage());

            return;
        }

        self::fail('The WebAuthn ceremony was accepted but must be rejected.');
    }
}
