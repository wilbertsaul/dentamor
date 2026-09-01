<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SunatLookupService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.jsonpe.key', '');
        $this->baseUrl = config('services.jsonpe.base_url', 'https://api.json.pe/api');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function lookupRuc(string $ruc): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(8)
                ->post($this->baseUrl . '/ruc', ['ruc' => $ruc]);

            if ($response->failed()) {
                return null;
            }

            $body = $response->json();

            if (empty($body['success'])) {
                return null;
            }

            $data = $body['data'] ?? [];

            return [
                'name' => $data['nombre_o_razon_social'] ?? null,
                'address' => $data['direccion_completa'] ?? $data['direccion'] ?? null,
                'district' => $data['distrito'] ?? null,
                'province' => $data['provincia'] ?? null,
                'department' => $data['departamento'] ?? null,
                'ubigeo' => $data['ubigeo_sunat'] ?? null,
                'status' => $data['estado'] ?? null,
                'condition' => $data['condicion'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('SunatLookupService::lookupRuc failed: ' . $e->getMessage());
            return null;
        }
    }

    public function lookupDni(string $dni): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(8)
                ->post($this->baseUrl . '/dni', ['dni' => $dni]);

            if ($response->failed()) {
                return null;
            }

            $body = $response->json();

            if (empty($body['success'])) {
                return null;
            }

            $data = $body['data'] ?? [];

            $fullName = trim(
                ($data['apellido_paterno'] ?? '') . ' ' .
                ($data['apellido_materno'] ?? '') . ' ' .
                ($data['nombres'] ?? '')
            );

            return [
                'name' => $fullName ?: ($data['nombre_completo'] ?? null),
                'first_name' => $data['nombres'] ?? null,
                'last_name_paternal' => $data['apellido_paterno'] ?? null,
                'last_name_maternal' => $data['apellido_materno'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('SunatLookupService::lookupDni failed: ' . $e->getMessage());
            return null;
        }
    }
}
