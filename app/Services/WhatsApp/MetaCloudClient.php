<?php

namespace App\Services\WhatsApp;

use App\Models\Company;
use Illuminate\Support\Facades\Http;

class MetaCloudClient
{
    public function __construct(
        private readonly string $token,
        private readonly string $phoneNumberId,
        private readonly string $apiVersion,
    ) {}

    public static function forCompany(Company $company): ?self
    {
        if (! filled($company->whatsapp_phone_number_id) || ! filled($company->whatsapp_token)) {
            return null;
        }

        return new self(
            (string) $company->whatsapp_token,
            (string) $company->whatsapp_phone_number_id,
            $company->whatsappApiVersion(),
        );
    }

    /**
     * @param  array<int, string|int|float>  $bodyParameters
     * @return array{ok: bool, status: int, body: mixed, error: ?string}
     */
    public function sendTemplate(string $to, string $template, string $language, array $bodyParameters): array
    {
        $url = rtrim((string) config('whatsapp.graph_url', 'https://graph.facebook.com'), '/')
            .'/'.$this->apiVersion.'/'.$this->phoneNumberId.'/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $language],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn ($value) => ['type' => 'text', 'text' => (string) $value],
                        array_values($bodyParameters),
                    ),
                ]],
            ],
        ];

        try {
            $response = Http::withToken($this->token)
                ->timeout((int) config('whatsapp.timeout', 20))
                ->acceptJson()
                ->post($url, $payload);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }

        $body = $response->json();
        $error = null;

        if (! $response->successful()) {
            $error = data_get($body, 'error.message')
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
