<?php

namespace App\Services\Sunat;

use App\Models\Invoice;
use Greenter\See;
use Illuminate\Support\Facades\Storage;

class InvoiceSender
{
    public static function send(Invoice $invoice): array
    {
        $guard = static::guard($invoice);

        if ($guard !== null) {
            return $guard;
        }

        $company = $invoice->company;
        $environment = $company?->environment() ?? 'beta';

        try {
            // El usuario principal no tiene perfil de emisión: se envía con el secundario.
            $see = SeeFactory::make($company, true);
            $document = DocumentBuilder::build($invoice);
            $name = $document->getName();
        } catch (\Throwable $e) {
            SunatLogger::error('No se pudo preparar el comprobante', [
                'invoice_id' => $invoice->id,
                'documento' => $invoice->full_number,
                'error' => $e->getMessage(),
            ]);

            return static::fail($invoice, 'SETUP', $e->getMessage(), $environment);
        }

        $invoice->forceFill([
            'sunat_document_type' => $invoice->documentType(),
            'sunat_environment' => $environment,
            'production' => (bool) $company->production,
            'sunat_xml_name' => $name,
            'sunat_status' => 'sent',
            'sunat_sent_at' => now(),
            'sunat_code' => null,
            'sunat_description' => null,
            'sunat_notes' => null,
        ])->save();

        SunatLogger::info('Enviando comprobante a SUNAT', [
            'invoice_id' => $invoice->id,
            'documento' => $name,
            'ambiente' => $environment,
        ]);

        try {
            $result = $see->send($document);
        } catch (\Throwable $e) {
            static::storeXml($see, $invoice, $name);

            SunatLogger::error('Fallo de comunicación con SUNAT', [
                'invoice_id' => $invoice->id,
                'documento' => $name,
                'error' => $e->getMessage(),
            ]);

            return static::fail($invoice, 'EXCEPTION', $e->getMessage(), $environment);
        }

        static::storeXml($see, $invoice, $name);

        if ($result === null || ! $result->isSuccess()) {
            $error = $result?->getError();
            $code = (string) ($error?->getCode() ?? 'SEND');
            $message = (string) ($error?->getMessage() ?? 'SUNAT no devolvió una respuesta válida.');

            SunatLogger::error('SUNAT rechazó la operación', [
                'invoice_id' => $invoice->id,
                'documento' => $name,
                'code' => $code,
                'error' => $message,
            ]);

            return static::fail($invoice, $code, $message, $environment, ResponseMapper::status($code));
        }

        $cdr = $result->getCdrResponse();
        $status = ResponseMapper::status($cdr?->getCode());
        $notes = array_values(array_filter((array) ($cdr?->getNotes() ?? [])));

        $updates = [
            'sunat_status' => $status,
            'sunat_code' => $cdr?->getCode(),
            'sunat_description' => $cdr?->getDescription(),
            'sunat_notes' => $notes !== [] ? implode(' | ', $notes) : null,
            'hash_cpe' => $cdr?->getId(),
        ];

        $cdrZip = $result->getCdrZip();

        if (filled($cdrZip)) {
            $cdrPath = 'sunat/cdr/'.$name.'.zip';
            Storage::disk('local')->put($cdrPath, $cdrZip);
            $updates['cdr_path'] = $cdrPath;
        }

        $invoice->forceFill($updates)->save();

        SunatLogger::info('SUNAT respondió al comprobante', [
            'invoice_id' => $invoice->id,
            'documento' => $name,
            'status' => $status,
            'code' => $cdr?->getCode(),
        ]);

        return [
            'success' => true,
            'accepted' => ResponseMapper::isValid($status),
            'status' => $status,
            'code' => (string) ($cdr?->getCode() ?? ''),
            'description' => (string) ($cdr?->getDescription() ?? ResponseMapper::label($status)),
            'notes' => $notes,
        ];
    }

    private static function fail(Invoice $invoice, string $code, string $message, string $environment, string $status = ResponseMapper::ERROR): array
    {
        $invoice->forceFill([
            'sunat_status' => $status,
            'sunat_environment' => $environment,
            'sunat_code' => $code,
            'sunat_description' => $message,
        ])->save();

        return [
            'success' => false,
            'accepted' => false,
            'status' => $status,
            'code' => $code,
            'description' => $message,
            'notes' => [],
        ];
    }

    /**
     * Evita reenvíos duplicados: un comprobante ya válido o anulado no se vuelve a firmar.
     */
    private static function guard(Invoice $invoice): ?array
    {
        if ($invoice->isAccepted()) {
            SunatLogger::info('Comprobante ya aceptado: no se reenvía', [
                'invoice_id' => $invoice->id,
                'documento' => $invoice->full_number,
                'status' => $invoice->sunat_status,
            ]);

            return [
                'success' => true,
                'accepted' => true,
                'status' => (string) $invoice->sunat_status,
                'code' => (string) ($invoice->sunat_code ?? ''),
                'description' => (string) ($invoice->sunat_description ?: ResponseMapper::label($invoice->sunat_status)),
                'notes' => [],
                'skipped' => true,
            ];
        }

        if ($invoice->sunat_status === 'voided') {
            return [
                'success' => false,
                'accepted' => false,
                'status' => 'voided',
                'code' => 'VOIDED',
                'description' => 'El comprobante fue anulado por comunicación de baja y no puede reenviarse.',
                'notes' => [],
            ];
        }

        return null;
    }

    private static function storeXml(See $see, Invoice $invoice, string $name): void
    {
        try {
            $xml = $see->getFactory()->getLastXml();

            if (blank($xml)) {
                return;
            }

            $path = 'sunat/xml/'.$name.'.xml';
            Storage::disk('local')->put($path, $xml);

            $invoice->forceFill([
                'xml_path' => $path,
                'sunat_xml_name' => $name,
            ])->save();
        } catch (\Throwable $e) {
            SunatLogger::warning('No se pudo guardar el XML firmado', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
