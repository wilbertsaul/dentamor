<?php

namespace App\Services\Sunat;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\SunatOperation;
use Carbon\Carbon;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SummaryService
{
    /**
     * Un resumen diario admite hasta 500 comprobantes (Anexo 05-A).
     */
    public const MAX_DOCUMENTS = 500;

    public const TICKET_PENDING_CODES = ['98'];

    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    public static function sendSummary(Company $company, Collection $invoices, ?Carbon $date = null): SunatOperation
    {
        if ($invoices->isEmpty()) {
            throw new RuntimeException('No hay comprobantes para resumir.');
        }

        $date = ($date ?? Carbon::parse($invoices->first()->issue_date))->startOfDay();

        $distinctDates = $invoices
            ->map(fn (Invoice $invoice) => Carbon::parse($invoice->issue_date)->startOfDay()->toDateString())
            ->unique();

        if ($distinctDates->count() > 1) {
            throw new RuntimeException('El resumen diario solo admite comprobantes de una misma fecha de emisión.');
        }

        $distinctCurrencies = $invoices->pluck('currency')->unique();

        if ($distinctCurrencies->count() > 1) {
            throw new RuntimeException('El resumen diario solo admite comprobantes de una misma moneda.');
        }

        $reference = static::nextReference($company, SunatOperation::TYPE_SUMMARY, $date);
        $correlativo = static::correlativoFromReference($reference);

        $operation = SunatOperation::create([
            'company_id' => $company->id,
            'type' => SunatOperation::TYPE_SUMMARY,
            'reference' => $reference,
            'reference_date' => $date->toDateString(),
            'issue_date' => now()->toDateString(),
            'environment' => $company->environment(),
            'status' => 'pending',
            'documents_count' => $invoices->count(),
        ]);

        $summary = new Summary;
        $summary->setCorrelativo($correlativo)
            ->setFecGeneracion(now())
            ->setFecResumen($date)
            ->setMoneda((string) $invoices->first()->currency)
            ->setCompany(CompanyBuilder::build($company))
            ->setDetails($invoices
                ->map(fn (Invoice $invoice) => static::summaryDetail($invoice))
                ->all());

        return static::dispatch($operation, $summary, $invoices->pluck('id')->all(), 'RC');
    }

    public static function sendVoided(Company $company, Invoice $invoice, string $motivo): SunatOperation
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException('Indica el motivo de la baja.');
        }

        if (mb_strlen($motivo) > 100) {
            throw new RuntimeException('El motivo de baja admite máximo 100 caracteres.');
        }

        if (! $invoice->isAccepted()) {
            throw new RuntimeException('Solo se pueden dar de baja comprobantes aceptados por SUNAT.');
        }

        $issueDate = Carbon::parse($invoice->issue_date)->startOfDay();
        $reference = static::nextReference($company, SunatOperation::TYPE_VOIDED, now());
        $correlativo = static::correlativoFromReference($reference);

        $operation = SunatOperation::create([
            'company_id' => $company->id,
            'type' => SunatOperation::TYPE_VOIDED,
            'reference' => $reference,
            'reference_date' => $issueDate->toDateString(),
            'issue_date' => now()->toDateString(),
            'environment' => $company->environment(),
            'status' => 'pending',
            'documents_count' => 1,
            'voids_documents' => true,
        ]);

        $detail = new VoidedDetail;
        $detail->setTipoDoc($invoice->documentType())
            ->setSerie($invoice->serie)
            ->setCorrelativo((string) $invoice->number)
            ->setDesMotivoBaja($motivo);

        $voided = new Voided;
        $voided->setCorrelativo($correlativo)
            ->setFecGeneracion(now())
            ->setFecComunicacion(now())
            ->setCompany(CompanyBuilder::build($company))
            ->setDetails([$detail]);

        return static::dispatch($operation, $voided, [$invoice->id], 'RA');
    }

    /**
     * Da de baja una boleta de venta electrónica.
     *
     * SUNAT no admite boletas (03) en una comunicación de baja (RA): la baja
     * de la boleta se comunica en un resumen diario (RC) del día de emisión
     * consignando la línea con estado "3" (Anulado).
     */
    public static function sendVoidedSummary(Company $company, Invoice $invoice): SunatOperation
    {
        if ($invoice->documentType() !== Invoice::TYPE_BOLETA) {
            throw new RuntimeException('Solo las boletas de venta se dan de baja mediante el resumen diario.');
        }

        if (! $invoice->isAccepted()) {
            throw new RuntimeException('Solo se pueden dar de baja comprobantes aceptados por SUNAT.');
        }

        $date = Carbon::parse($invoice->issue_date)->startOfDay();
        $reference = static::nextReference($company, SunatOperation::TYPE_SUMMARY, $date);
        $correlativo = static::correlativoFromReference($reference);

        $operation = SunatOperation::create([
            'company_id' => $company->id,
            'type' => SunatOperation::TYPE_SUMMARY,
            'reference' => $reference,
            'reference_date' => $date->toDateString(),
            'issue_date' => now()->toDateString(),
            'environment' => $company->environment(),
            'status' => 'pending',
            'documents_count' => 1,
            'voids_documents' => true,
        ]);

        $summary = new Summary;
        $summary->setCorrelativo($correlativo)
            ->setFecGeneracion(now())
            ->setFecResumen($date)
            ->setMoneda((string) $invoice->currency)
            ->setCompany(CompanyBuilder::build($company))
            ->setDetails([static::summaryDetail($invoice, '3')]);

        return static::dispatch($operation, $summary, [$invoice->id], 'RC');
    }

    public static function refresh(SunatOperation $operation): SunatOperation
    {
        if (blank($operation->ticket)) {
            throw new RuntimeException('Esta operación todavía no tiene ticket de SUNAT.');
        }

        $company = $operation->company;

        $see = SeeFactory::make($company, true);
        $status = $see->getStatus($operation->ticket);

        if (in_array((string) $status->getCode(), static::TICKET_PENDING_CODES, true)) {
            $operation->forceFill(['status' => 'processing'])->save();

            return $operation;
        }

        if (! $status->isSuccess()) {
            $error = $status->getError();
            $operation->forceFill([
                'status' => ResponseMapper::ERROR,
                'code' => (string) ($error?->getCode() ?? ''),
                'description' => (string) ($error?->getMessage() ?? 'SUNAT no devolvió el CDR del resumen.'),
                'resolved_at' => now(),
            ])->save();

            return $operation;
        }

        $cdr = $status->getCdrResponse();
        $newStatus = ResponseMapper::status($cdr?->getCode());
        $updates = [
            'status' => $newStatus,
            'code' => $cdr?->getCode(),
            'description' => $cdr?->getDescription(),
            'resolved_at' => now(),
        ];

        $cdrZip = $status->getCdrZip();

        if (filled($cdrZip)) {
            $path = 'sunat/cdr/'.$operation->reference.'.zip';
            Storage::disk('local')->put($path, $cdrZip);
            $updates['cdr_path'] = $path;
        }

        $operation->forceFill($updates)->save();

        static::markVoidedDocuments($operation, $newStatus);

        return $operation;
    }

    /**
     * Cuando una baja (RA o RC) es aceptada por SUNAT, deja constancia en los
     * comprobantes afectados. Un resumen diario normal no anula sus boletas.
     */
    public static function markVoidedDocuments(SunatOperation $operation, string $status): void
    {
        if (! ResponseMapper::isValid($status) || ! $operation->voidsDocuments()) {
            return;
        }

        $operation->invoices()->update(['sunat_status' => 'voided']);
    }

    /**
     * Liga los comprobantes incluidos en la comunicación con su operación SUNAT.
     * Sin este vínculo, markVoidedDocuments() no puede dejarlos como anulados y
     * pendingFor() vuelve a resumir boletas ya informadas.
     *
     * @param  array<int, int|string>  $invoiceIds
     */
    public static function linkInvoices(SunatOperation $operation, array $invoiceIds): void
    {
        if ($invoiceIds === []) {
            return;
        }

        Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->update(['sunat_operation_id' => $operation->id]);
    }

    public static function pendingFor(Company $company, ?Carbon $date = null): Collection
    {
        $date = ($date ?? now())->startOfDay();

        return Invoice::query()
            ->where('company_id', $company->id)
            ->where(fn ($query) => $query->where('is_reservation', false)->orWhereNull('is_reservation'))
            ->where('invoice_type', 'B')
            ->where('issue_date', $date->toDateString())
            ->whereNull('sunat_operation_id')
            ->whereIn('sunat_status', [
                ResponseMapper::ACCEPTED,
                ResponseMapper::ACCEPTED_WITH_OBSERVATIONS,
            ])
            ->get();
    }

    public static function nextReference(Company $company, string $type, Carbon $date): string
    {
        $prefix = $type.'-'.$date->format('Ymd');

        // El bloqueo de fila sobre la empresa evita que dos resúmenes simultáneos
        // obtengan el mismo correlativo de comunicación.
        return (string) DB::transaction(function () use ($company, $type, $prefix): string {
            Company::query()->whereKey($company->id)->lockForUpdate()->first();

            $last = SunatOperation::query()
                ->where('company_id', $company->id)
                ->where('type', $type)
                ->where('reference', 'like', $prefix.'-%')
                ->orderByDesc('reference')
                ->value('reference');

            $next = (int) static::correlativoFromReference((string) $last) + 1;

            while (SunatOperation::query()
                ->where('company_id', $company->id)
                ->where('reference', $prefix.'-'.$next)
                ->exists()) {
                $next++;
            }

            return $prefix.'-'.$next;
        }, 5);
    }

    private static function correlativoFromReference(string $reference): string
    {
        if ($reference === '' || ! str_contains($reference, '-')) {
            return '0';
        }

        $position = strrpos($reference, '-');

        return substr($reference, $position + 1);
    }

    private static function summaryDetail(Invoice $invoice, string $estado = '1'): SummaryDetail
    {
        $detail = new SummaryDetail;
        $detail->setTipoDoc($invoice->documentType())
            ->setSerieNro($invoice->full_number)
            ->setClienteTipo(ClientBuilder::tipoDocFor($invoice->client))
            ->setClienteNro($invoice->client?->doc_number ?: ClientBuilder::ANONYMOUS_DOC)
            ->setEstado($estado)
            ->setTotal((float) $invoice->total)
            ->setPorcentajeIgv(DocumentBuilder::IGV_RATE)
            ->setMtoOperGravadas((float) $invoice->subtotal)
            ->setMtoIGV((float) $invoice->igv);

        return $detail;
    }

    private static function dispatch(SunatOperation $operation, Summary|Voided $document, array $invoiceIds, string $label): SunatOperation
    {
        try {
            $see = SeeFactory::make($operation->company, true);
            $name = $operation->reference;
            $result = $see->send($document);
        } catch (\Throwable $e) {
            $operation->forceFill([
                'status' => ResponseMapper::ERROR,
                'code' => 'EXCEPTION',
                'description' => $e->getMessage(),
                'resolved_at' => now(),
            ])->save();

            SunatLogger::error('Fallo al enviar el resumen a SUNAT', [
                'reference' => $operation->reference,
                'error' => $e->getMessage(),
            ]);

            return $operation;
        }

        $xml = $see->getFactory()->getLastXml();

        if (filled($xml)) {
            $path = 'sunat/xml/'.$name.'.xml';
            Storage::disk('local')->put($path, $xml);
            $operation->forceFill(['xml_path' => $path])->save();
        }

        if ($result === null || ! $result->isSuccess()) {
            $error = $result?->getError();
            $operation->forceFill([
                'status' => ResponseMapper::status((string) ($error?->getCode() ?? '')),
                'code' => (string) ($error?->getCode() ?? ''),
                'description' => (string) ($error?->getMessage() ?? 'SUNAT no devolvió un ticket válido.'),
                'resolved_at' => now(),
            ])->save();

            return $operation;
        }

        $operation->forceFill([
            'status' => 'sent',
            'ticket' => $result->getTicket(),
        ])->save();

        static::linkInvoices($operation, $invoiceIds);

        SunatLogger::info('Resumen enviado a SUNAT', [
            'reference' => $operation->reference,
            'label' => $label,
            'documentos' => count($invoiceIds),
            'ticket' => $operation->ticket,
        ]);

        return static::refresh($operation);
    }
}
