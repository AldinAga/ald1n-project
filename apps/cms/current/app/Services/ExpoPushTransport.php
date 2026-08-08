<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MobilePushOutbox;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

final class ExpoPushTransport
{
    /** @return array{status:'ok'|'retry'|'error',ticket_id?:string,error?:string,error_code?:string} */
    public function send(MobilePushOutbox $row): array
    {
        $device = $row->device()->first();
        $token = trim((string) ($device?->push_token ?? ''));
        if ($device === null || !$device->isActive() || !$device->notifications_enabled || $device->push_provider !== 'expo' || $token === '') {
            return ['status' => 'error', 'error' => 'Push uređaj više nije aktivan ili nema Expo token.', 'error_code' => 'DeviceNotRegistered'];
        }

        if (!preg_match('/^(Expo|Exponent)PushToken\[[^\]]+\]$/', $token)) {
            return ['status' => 'error', 'error' => 'Sačuvani Expo push token nema očekivani format.', 'error_code' => 'DeviceNotRegistered'];
        }

        $payload = [
            'to' => $token,
            'title' => $row->title,
            'body' => $row->message,
            'sound' => 'default',
            'channelId' => 'business-updates',
            'priority' => 'high',
            'data' => is_array($row->data_json) ? $row->data_json : [],
        ];

        try {
            $response = $this->request()->post((string) config('mobile.push.expo.send_url'), $payload);
        } catch (ConnectionException $exception) {
            return ['status' => 'retry', 'error' => $exception->getMessage()];
        } catch (Throwable $exception) {
            return ['status' => 'retry', 'error' => $exception->getMessage()];
        }

        if ($response->status() === 429 || $response->serverError()) {
            return ['status' => 'retry', 'error' => 'Expo Push HTTP '.$response->status().'.'];
        }
        if (!$response->successful()) {
            return ['status' => 'error', 'error' => 'Expo Push HTTP '.$response->status().': '.$this->responseMessage($response->json())];
        }

        $ticket = $response->json('data');
        if (is_array($ticket) && array_is_list($ticket)) {
            $ticket = $ticket[0] ?? null;
        }
        if (!is_array($ticket)) {
            return ['status' => 'retry', 'error' => 'Expo Push odgovor ne sadrži validan ticket.'];
        }

        if (($ticket['status'] ?? null) === 'ok' && filled($ticket['id'] ?? null)) {
            return ['status' => 'ok', 'ticket_id' => (string) $ticket['id']];
        }

        $code = trim((string) ($ticket['details']['error'] ?? ''));
        $message = trim((string) ($ticket['message'] ?? 'Expo Push ticket je odbijen.'));
        if ($code === 'MessageRateExceeded') {
            return ['status' => 'retry', 'error' => $message, 'error_code' => $code];
        }

        return ['status' => 'error', 'error' => $message, 'error_code' => $code !== '' ? $code : null];
    }

    /** @param list<string> $ticketIds @return array{status:'ok'|'retry'|'error',receipts?:array<string,array<string,mixed>>,error?:string} */
    public function receipts(array $ticketIds): array
    {
        if ($ticketIds === []) {
            return ['status' => 'ok', 'receipts' => []];
        }

        try {
            $response = $this->request()->post((string) config('mobile.push.expo.receipts_url'), [
                'ids' => array_values(array_slice($ticketIds, 0, 1000)),
            ]);
        } catch (ConnectionException $exception) {
            return ['status' => 'retry', 'error' => $exception->getMessage()];
        } catch (Throwable $exception) {
            return ['status' => 'retry', 'error' => $exception->getMessage()];
        }

        if ($response->status() === 429 || $response->serverError()) {
            return ['status' => 'retry', 'error' => 'Expo receipt HTTP '.$response->status().'.'];
        }
        if (!$response->successful()) {
            return ['status' => 'error', 'error' => 'Expo receipt HTTP '.$response->status().': '.$this->responseMessage($response->json())];
        }

        $data = $response->json('data');
        return is_array($data)
            ? ['status' => 'ok', 'receipts' => $data]
            : ['status' => 'retry', 'error' => 'Expo receipt odgovor nema data mapu.'];
    }

    private function request(): PendingRequest
    {
        $request = Http::asJson()
            ->acceptJson()
            ->timeout(max(3, (int) config('mobile.push.expo.timeout_seconds', 10)));

        $accessToken = trim((string) config('mobile.push.expo.access_token', ''));
        return $accessToken !== '' ? $request->withToken($accessToken) : $request;
    }

    private function responseMessage(mixed $json): string
    {
        if (!is_array($json)) {
            return 'nepoznata greška';
        }
        if (isset($json['errors'][0]['message'])) {
            return trim((string) $json['errors'][0]['message']);
        }
        if (isset($json['message'])) {
            return trim((string) $json['message']);
        }
        return 'nepoznata greška';
    }
}
