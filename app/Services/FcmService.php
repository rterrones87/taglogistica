<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Google\Auth\CredentialsLoader;

class FcmService
{
    protected string $projectId;
    protected array $saJson; // service account JSON decodificado
    protected array $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

    public function __construct()
    {
        $this->projectId = (string) env('FIREBASE_PROJECT_ID');

        $rawPath = (string) env('FIREBASE_CREDENTIALS');

        $path = $this->resolveCredentialsPath($rawPath);

        if (!is_readable($path)) {
            throw new \RuntimeException("No puedo leer credenciales FCM en: {$path}");
        }

        $json = file_get_contents($path);
        $this->saJson = json_decode($json, true) ?: [];

        $saProject = $this->saJson['project_id'] ?? null;
        if (!$saProject || $saProject !== $this->projectId) {
            throw new \RuntimeException("FIREBASE_PROJECT_ID ({$this->projectId}) no coincide con project_id de la service account ({$saProject})");
        }
    }

    protected function resolveCredentialsPath(?string $rawPath): string
    {
        $rawPath = trim((string) $rawPath);
        if ($rawPath === '') {
            return base_path('storage/keys/tag-logistica-firebase-adminsdk-fbsvc-197af33b22.json');
        }

        $isAbsolute =
            str_starts_with($rawPath, DIRECTORY_SEPARATOR) ||
            preg_match('/^[A-Za-z]:\\\\/', $rawPath) === 1;

        if ($isAbsolute) {
            return $rawPath;
        }

        $normalized = str_replace(['\\', '//'], ['/', '/'], $rawPath);

        if (str_starts_with($normalized, 'storage/')) {
            return base_path($normalized);
        }

        if (!str_contains($normalized, '/')) {
            return base_path('storage/keys/' . $normalized);
        }

        return base_path($normalized);
    }

    protected function getAccessToken(): string
    {
        $credentials = CredentialsLoader::makeCredentials($this->scopes, $this->saJson);
        $tokenArray = $credentials->fetchAuthToken();

        if (empty($tokenArray['access_token'])) {
            throw new \RuntimeException('No se pudo obtener access token para FCM');
        }

        return $tokenArray['access_token'];
    }

    /**
     * Envía un push a un registration token de Android.
     * $data debe ser [string => string]
     */
    public function sendToToken(string $registrationToken, string $title, string $body, array $data = []): array
    {
        // Asegura strings en data
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        $client = new Client(['timeout' => 10]);
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        // Construir mensaje base
        $message = [
            'token' => $registrationToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'android' => [
                'priority' => 'high',
            ],
        ];

        // Solo incluir 'data' si tiene contenido (FCM rechaza arrays vacíos [] como objeto)
        if (!empty($stringData)) {
            $message['data'] = $stringData;
        }

        $payload = [
            'message' => $message,
        ];

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type'  => 'application/json',
                ],
                'json'        => $payload,
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();
            $body   = (string) $response->getBody();
            $json   = json_decode($body, true);

            if ($status >= 200 && $status < 300) {
                return $json ?? ['success' => true, 'raw' => $body];
            }

            $reason = $json['error']['message'] ?? $body;
            throw new \RuntimeException("FCM error {$status}: {$reason}");
        } catch (RequestException $e) {
            throw new \RuntimeException("FCM request failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * validateOnly para probar el token sin enviar.
     */
    public function validateToken(string $registrationToken): array
    {
        $client = new Client(['timeout' => 10]);
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $payload = [
            'validateOnly' => true,
            'message' => [
                'token' => $registrationToken,
                'notification' => ['title' => 't', 'body' => 'b'],
            ],
        ];

        $resp = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type'  => 'application/json',
            ],
            'json'        => $payload,
            'http_errors' => false,
        ]);

        $json = json_decode((string) $resp->getBody(), true);
        return [
            'status' => $resp->getStatusCode(),
            'body'   => $json ?? (string) $resp->getBody(),
        ];
    }
}
