<?php

namespace App\Services\WhatsApp;

use App\Models\Company;
use Illuminate\Support\Facades\Http;

class WahaClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $session,
    ) {}

    public static function forCompany(Company $company): ?self
    {
        $baseUrl = config('whatsapp.base_url');
        $apiKey = config('whatsapp.api_key');

        if (! filled($baseUrl) || ! filled($apiKey)) {
            return null;
        }

        return new self(
            rtrim((string) $baseUrl, '/'),
            (string) $apiKey,
            $company->whatsappSession(),
        );
    }

    /**
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    public function sendText(string $chatId, string $text): array
    {
        return $this->post('/api/sendText', [
            'session' => $this->session,
            'chatId' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    public function sendFile(string $chatId, string $filePath, string $caption = ''): array
    {
        $contents = @file_get_contents($filePath);

        if ($contents === false) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'error' => 'No se pudo leer el archivo: '.$filePath,
            ];
        }

        return $this->post('/api/sendFile', [
            'session' => $this->session,
            'chatId' => $chatId,
            'caption' => $caption,
            'file' => [
                'mimetype' => 'application/pdf',
                'filename' => basename($filePath),
                'data' => base64_encode($contents),
            ],
        ]);
    }

    /**
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    public function sessionStatus(): array
    {
        return $this->get('/api/sessions/'.$this->session);
    }

    /**
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    public function startSession(): array
    {
        return $this->post('/api/sessions/'.$this->session.'/start', [
            'session' => $this->session,
        ]);
    }

    /**
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    private function get(string $path): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout((int) config('whatsapp.timeout', 20))
                ->acceptJson()
                ->get($this->baseUrl.$path);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }

        return $this->parse($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout((int) config('whatsapp.timeout', 20))
                ->acceptJson()
                ->post($this->baseUrl.$path, $payload);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }

        return $this->parse($response);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'X-Api-Key' => $this->apiKey,
        ];
    }

    /**
     * @param  \Illuminate\Http\Client\Response  $response
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    private function parse($response): array
    {
        $body = $response->json();
        $error = null;

        if (! $response->successful()) {
            $error = data_get($body, 'message')
                ?: data_get($body, 'error')
                ?: trim($response->body())
                ?: 'Error HTTP '.$response->status();
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
            'error' => $error,
        ];
    }
}
