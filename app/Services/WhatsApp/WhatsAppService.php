<?php

namespace App\Services\WhatsApp;

use App\Models\Company;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class WhatsAppService
{
    public static function isConfigured(?Company $company): bool
    {
        return $company !== null && $company->whatsappConfigured();
    }

    /**
     * Envia el comprobante por WhatsApp usando la plantilla configurada.
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

        $link = URL::temporarySignedRoute(
            'invoices.pdf.public',
            now()->addDays((int) config('whatsapp.link_days', 30)),
            ['invoice' => $invoice->id],
        );

        $client = MetaCloudClient::forCompany($company);

        if ($client === null) {
            return self::markFailed($invoice, 'Faltan credenciales de WhatsApp.');
        }

        $response = $client->sendTemplate(
            $apiPhone,
            (string) $company->whatsapp_template_name,
            $company->whatsappTemplateLang(),
            [
                $invoice->client?->name ?: 'cliente',
                self::documentLabel($invoice),
                number_format((float) $invoice->total, 2, '.', ''),
                $link,
            ],
        );

        if (! $response['ok']) {
            return self::markFailed($invoice, (string) ($response['error'] ?: 'Error desconocido de Meta.'), $response['status']);
        }

        $messageId = data_get($response['body'], 'messages.0.id');

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
        if (! filled($company->whatsapp_phone_number_id) || ! filled($company->whatsapp_token) || ! filled($company->whatsapp_template_name)) {
            return self::result(false, true, 'Completa Phone Number ID, token y plantilla.');
        }

        $apiPhone = PhoneNormalizer::toApiNumber($phone);

        if ($apiPhone === null) {
            return self::result(false, true, 'Ingresa un teléfono válido.');
        }

        $client = MetaCloudClient::forCompany($company);

        if ($client === null) {
            return self::result(false, true, 'Faltan credenciales de WhatsApp.');
        }

        $response = $client->sendTemplate(
            $apiPhone,
            (string) $company->whatsapp_template_name,
            $company->whatsappTemplateLang(),
            [
                $company->commercial_name ?: $company->business_name ?: 'cliente',
                'boleta de prueba',
                '0.00',
                url('/'),
            ],
        );

        return self::result(
            $response['ok'],
            false,
            $response['ok'] ? 'Mensaje de prueba enviado.' : (string) ($response['error'] ?: 'No se pudo enviar.'),
            '+'.$apiPhone,
            $response['status'],
        );
    }

    public static function documentLabel(Invoice $invoice): string
    {
        if ($invoice->isCreditNote()) {
            return 'nota de crédito '.$invoice->full_number;
        }

        $type = $invoice->invoice_type === 'F' ? 'factura' : 'boleta';

        return $type.' '.$invoice->full_number;
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
