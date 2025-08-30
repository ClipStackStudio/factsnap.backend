<?php

namespace App\Services;

use App\Models\Fact;
use App\Models\Package;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppleNotificationService
{
    private string $keyId;
    private string $teamId;
    private string $bundleId;
    private string $privateKey;
    private bool $production;

    public function __construct()
    {
        $this->keyId = config('services.apns.key_id');
        $this->teamId = config('services.apns.team_id');
        $this->bundleId = config('services.apns.bundle_id');
        $this->privateKey = config('services.apns.private_key');
        $this->production = config('services.apns.production', false);
    }

    public function sendFactNotification(string $deviceToken, Fact $fact, Package $package, array $options = []): bool
    {
        try {
            $isTimeSensitive = $options['time_sensitive'] ?? false;
            $collapseId = $options['collapse_id'] ?? "fact_{$package->id}";
            
            $payload = [
                'aps' => [
                    'alert' => [
                        'title' => $package->name,
                        'body' => $this->truncateText($fact->content, 100),
                    ],
                    'badge' => 1,
                    'sound' => 'default',
                    'category' => 'fact_delivery',
                ],
                'fact_id' => $fact->id,
                'package_id' => $package->id,
                'delivery_type' => 'scheduled',
            ];

            // Add interruption level for time-sensitive notifications (iOS 15+)
            if ($isTimeSensitive) {
                $payload['aps']['interruption-level'] = 'time-sensitive';
            }

            $jwt = $this->generateJWT();
            $url = $this->production 
                ? 'https://api.push.apple.com/3/device/' . $deviceToken
                : 'https://api.sandbox.push.apple.com/3/device/' . $deviceToken;

            $headers = [
                'Authorization' => 'Bearer ' . $jwt,
                'apns-topic' => $this->bundleId,
                'apns-push-type' => 'alert',
                'apns-priority' => $isTimeSensitive ? '10' : '5',
                'apns-collapse-id' => $collapseId,
            ];

            // Add expiration for non-time-sensitive notifications
            if (!$isTimeSensitive) {
                $headers['apns-expiration'] = strval(time() + 3600); // 1 hour
            }

            $response = Http::withHeaders($headers)->post($url, $payload);

            if ($response->successful()) {
                Log::info("APNS notification sent successfully", [
                    'device_token' => substr($deviceToken, 0, 10) . '...',
                    'fact_id' => $fact->id,
                ]);
                return true;
            } else {
                Log::error("APNS notification failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'device_token' => substr($deviceToken, 0, 10) . '...',
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error("APNS notification exception: " . $e->getMessage());
            return false;
        }
    }

    private function generateJWT(): string
    {
        $header = [
            'alg' => 'ES256',
            'kid' => $this->keyId,
        ];

        $payload = [
            'iss' => $this->teamId,
            'iat' => time(),
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->sign($headerEncoded . '.' . $payloadEncoded);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    private function sign(string $data): string
    {
        $privateKey = openssl_pkey_get_private($this->privateKey);
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function truncateText(string $text, int $limit): string
    {
        if (strlen($text) <= $limit) {
            return $text;
        }
        return substr($text, 0, $limit - 3) . '...';
    }
}
