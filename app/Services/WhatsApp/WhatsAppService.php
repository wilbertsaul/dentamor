<?php

namespace App\Services\WhatsApp;

use App\Models\Company;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    public static function isConfigured(?Company $company): bool
    {
        return $company !== null && $company->whatsappConfigured();
    }

    /**
     * Envia el comprobante por WhatsApp con el PDF adjunto.
     *
     * @return array{ok: bool, skipped: bool, message: string, phone: ?string, status: ?int}
     */
    public static function sendInvoice(Invoice $invoice, ?string $phone = null): array
    {
        $invoice->loadMissing('company', 'client');

        $company = $invoice->company;

        if (! self::isConfigured($company)) {
            return self::result(false, true, 'WhatsApp no está configurado para la empresa.');
        }

        $rawPhone = filled($phone) ? $phone : ($invoice->customer_phone ?: $invoice->client?->phone);
        $apiPhone = PhoneNormalizer::toApiNumber($rawPhone);

        if ($apiPhone === null) {
            return self::result(false, true, 'El comprobante no tiene un teléfono válido.');
        }

        if (filled($phone) && $phone !== $invoice->customer_phone) {
            $invoice->forceFill(['customer_phone' => $phone])->save();
        }

        $pdfPath = $invoice->pdf_path;

        if (! filled($pdfPath) || ! Storage::disk('local')->exists($pdfPath)) {
            try {
                $pdfPath = PdfService::generate($invoice);
            } catch (\Throwable $e) {
                return self::markFailed($invoice, 'No se pudo generar el PDF: '.$e->getMessage());
            }
        }

        $absolutePath = Storage::disk('local')->path($pdfPath);

        $client = WahaClient::forCompany($company);

        if ($client === null) {
            return self::markFailed($invoice, 'Faltan credenciales de WhatsApp (WAHA_API_KEY / WHATSAPP_BASE_URL).');
        }

        $chatId = $apiPhone.'@c.us';

        $response = $client->sendFile($chatId, $absolutePath, self::caption($invoice));

        if (! $response['ok']) {
            return self::markFailed($invoice, (string) ($response['error'] ?: 'Error desconocido de WAHA.'), $response['status']);
        }

        $messageId = data_get($response['body'], 'id')
            ?: data_get($response['body'], '_data.id');

        $invoice->forceFill([
            'whatsapp_status' => 'sent',
            'whatsapp_sent_at' => now(),
            'whatsapp_message_id' => $messageId,
            'whatsapp_error' => null,
        ])->save();

        Log::info('WhatsApp enviado', [
            'invoice_id' => $invoice->id,
            'phone' => $apiPhone,
            'message_id' => $messageId,
        ]);

        return self::result(true, false, 'Comprobante enviado por WhatsApp.', '+'.$apiPhone, $response['status']);
    }

    /**
     * Envío de prueba desde la configuración de la empresa.
     *
     * @return array{ok: bool, skipped: bool, message: string, phone: ?string, status: ?int}
     */
    public static function sendTest(Company $company, ?string $phone): array
    {
        if (! filled(config('whatsapp.base_url')) || ! filled(config('whatsapp.api_key'))) {
            return self::result(false, true, 'Falta WHATSAPP_BASE_URL o WHATSAPP_API_KEY en la configuración del servidor.');
        }

        $apiPhone = PhoneNormalizer::toApiNumber($phone);

        if ($apiPhone === null) {
            return self::result(false, true, 'Ingresa un teléfono válido.');
        }

        $client = WahaClient::forCompany($company);

        if ($client === null) {
            return self::result(false, true, 'Faltan credenciales de WhatsApp.');
        }

        $response = $client->sendText(
            $apiPhone.'@c.us',
            'Mensaje de prueba de Dentamor. Si lo recibes, la conexión con WhatsApp funciona correctamente.',
        );

        return self::result(
            $response['ok'],
            false,
            $response['ok'] ? 'Mensaje de prueba enviado.' : (string) ($response['error'] ?: 'No se pudo enviar.'),
            '+'.$apiPhone,
            $response['status'],
        );
    }

    /**
     * Estado de la sesión de WAHA desde la configuración.
     *
     * @return array{ok: bool, status: ?string, name: ?string, error: ?string}
     */
    public static function sessionStatus(Company $company): array
    {
        $client = WahaClient::forCompany($company);

        if ($client === null) {
            return ['ok' => false, 'status' => null, 'name' => null, 'error' => 'Faltan credenciales de WhatsApp.'];
        }

        $response = $client->sessionStatus();

        if (! $response['ok']) {
            return [
                'ok' => false,
                'status' => null,
                'name' => null,
                'error' => (string) ($response['error'] ?: 'No se pudo consultar la sesión.'),
            ];
        }

        return [
            'ok' => true,
            'status' => data_get($response['body'], 'status'),
            'name' => data_get($response['body'], 'name'),
            'error' => null,
        ];
    }

    /**
     * Fuerza la reconexión de la sesión de WAHA (por ejemplo, si el QR expiró).
     *
     * @return array{ok: bool, message: string}
     */
    public static function startSession(Company $company): array
    {
        $client = WahaClient::forCompany($company);

        if ($client === null) {
            return ['ok' => false, 'message' => 'Faltan credenciales de WhatsApp.'];
        }

        $response = $client->startSession();

        return [
            'ok' => $response['ok'],
            'message' => $response['ok']
                ? 'Solicitud de reconexión enviada. Escanea el QR con el teléfono.'
                : (string) ($response['error'] ?: 'No se pudo iniciar la sesión.'),
        ];
    }

    public static function documentLabel(Invoice $invoice): string
    {
        if ($invoice->isCreditNote()) {
            return 'nota de crédito '.$invoice->full_number;
        }

        $type = $invoice->invoice_type === 'F' ? 'factura' : 'boleta';

        return $type.' '.$invoice->full_number;
    }

    private static function caption(Invoice $invoice): string
    {
        $clientName = $invoice->client?->name ?: 'cliente';
        $label = self::documentLabel($invoice);
        $total = number_format((float) $invoice->total, 2, '.', '');

        return sprintf(
            "Estimado %s,\nAdjunto el comprobante %s por S/. %s.\nGracias por su preferencia.",
            $clientName,
            $label,
            $total,
        );
    }

    /**
     * @return array{ok: bool, skipped: bool, message: string, phone: ?string, status: ?int}
     */
    private static function markFailed(Invoice $invoice, string $message, ?int $status = null): array
    {
        $invoice->forceFill([
            'whatsapp_status' => 'failed',
            'whatsapp_error' => mb_substr($message, 0, 1000),
        ])->save();

        Log::warning('WhatsApp no enviado', [
            'invoice_id' => $invoice->id,
            'error' => $message,
        ]);

        return self::result(false, false, $message, null, $status);
    }

    /**
     * @return array{ok: bool, skipped: bool, message: string, phone: ?string, status: ?int}
     */
    private static function result(bool $ok, bool $skipped, string $message, ?string $phone = null, ?int $status = null): array
    {
        return [
            'ok' => $ok,
            'skipped' => $skipped,
            'message' => $message,
            'phone' => $phone,
            'status' => $status,
        ];
    }
}
