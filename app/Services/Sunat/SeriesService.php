<?php

namespace App\Services\Sunat;

use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\Invoice;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SeriesService
{
    public const FACTURA_SERIE = 'F002';

    public const BOLETA_SERIE = 'B002';

    public static function forInvoiceType(Company $company, string $invoiceType): string
    {
        return $invoiceType === 'F' ? $company->facturaSerie() : $company->boletaSerie();
    }

    /**
     * Las notas de crédito usan series propias (FC02/BC02) ligadas al tipo 07.
     */
    public static function creditNoteSeries(Company $company, string $invoiceType): string
    {
        return $invoiceType === 'F' ? $company->ncFacturaSerie() : $company->ncBoletaSerie();
    }

    public static function documentTypeFor(string $serie): ?string
    {
        if (str_starts_with($serie, 'FC') || str_starts_with($serie, 'BC')) {
            return Invoice::TYPE_NOTA_CREDITO;
        }

        if (str_starts_with($serie, 'F')) {
            return Invoice::TYPE_FACTURA;
        }

        if (str_starts_with($serie, 'B')) {
            return Invoice::TYPE_BOLETA;
        }

        // Series internas (RVA) que nunca se firman ante SUNAT.
        return null;
    }

    /**
     * Reserva el siguiente correlativo de forma atómica. Varias peticiones simultáneas
     * (dos usuarios, o el proceso de facturación en bloque) nunca obtienen el mismo número.
     */
    public static function next(Company $company, string $serie, ?string $documentType = null): int
    {
        static::ensureExists($company, $serie, $documentType);

        return (int) DB::transaction(function () use ($company, $serie): int {
            $row = DocumentSeries::query()
                ->where('company_id', $company->id)
                ->where('serie', $serie)
                ->lockForUpdate()
                ->firstOrFail();

            $number = (int) $row->next_number;
            $row->next_number = $number + 1;
            $row->save();

            return $number;
        }, 5);
    }

    public static function peek(Company $company, string $serie): int
    {
        $row = DocumentSeries::query()
            ->where('company_id', $company->id)
            ->where('serie', $serie)
            ->first();

        return $row ? (int) $row->next_number : 1;
    }

    public static function syncFromIssued(Company $company, string $serie): int
    {
        $next = ((int) Invoice::withTrashed()
            ->where('company_id', $company->id)
            ->where('serie', $serie)
            ->max('number')) + 1;

        DocumentSeries::query()->updateOrCreate(
            ['company_id' => $company->id, 'serie' => $serie],
            ['document_type' => static::documentTypeFor($serie), 'next_number' => max($next, 1)]
        );

        return max($next, 1);
    }

    private static function ensureExists(Company $company, string $serie, ?string $documentType): void
    {
        $exists = DocumentSeries::query()
            ->where('company_id', $company->id)
            ->where('serie', $serie)
            ->exists();

        if ($exists) {
            return;
        }

        $lastIssued = (int) Invoice::withTrashed()
            ->where('company_id', $company->id)
            ->where('serie', $serie)
            ->max('number');

        try {
            DocumentSeries::create([
                'company_id' => $company->id,
                'serie' => $serie,
                'document_type' => $documentType ?? static::documentTypeFor($serie),
                'next_number' => $lastIssued + 1,
            ]);
        } catch (QueryException) {
            // Otra petición creó la serie primero; seguimos con el bloqueo de fila.
        }
    }
}
