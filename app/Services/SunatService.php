<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\SunatOperation;
use App\Services\Sunat\ConnectionTest;
use App\Services\Sunat\InvoiceSender;
use App\Services\Sunat\ResponseMapper;
use App\Services\Sunat\SeriesService;
use App\Services\Sunat\SummaryService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class SunatService
{
    /**
     * Envía una factura o boleta electrónica a SUNAT y registra la trazabilidad.
     *
     * @return array{success: bool, accepted: bool, status: string, code: string, description: string, notes: array<int, string>}
     */
    public static function sendInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['company', 'client', 'items', 'relatedInvoice']);

        return InvoiceSender::send($invoice);
    }

    /**
     * Envía una nota de crédito (07) vinculada a su comprobante.
     */
    public static function sendCreditNote(Invoice $creditNote): array
    {
        if ($creditNote->documentType() !== Invoice::TYPE_NOTA_CREDITO) {
            throw new RuntimeException('El comprobante no es una nota de crédito.');
        }

        return static::sendInvoice($creditNote);
    }

    /**
     * Registra la baja de un comprobante.
     *
     * La factura (y sus notas) usa comunicación de baja (RA); la boleta de
     * venta se da de baja en un resumen diario (RC) con estado "Anulado".
     */
    public static function voidInvoice(Invoice $invoice, ?string $motivo = null): SunatOperation
    {
        $invoice->loadMissing('company');

        $operation = $invoice->documentType() === Invoice::TYPE_BOLETA
            ? SummaryService::sendVoidedSummary($invoice->company, $invoice)
            : SummaryService::sendVoided($invoice->company, $invoice, (string) $motivo);

        if (ResponseMapper::isValid($operation->status)) {
            $invoice->forceFill(['sunat_status' => 'voided'])->save();
        }

        return $operation;
    }

    /**
     * Genera el resumen diario (RC) de las boletas enviadas de una fecha.
     *
     * @return array<int, SunatOperation>
     */
    public static function sendDailySummary(Company $company, ?Carbon $date = null, ?Collection $invoices = null): array
    {
        $invoices ??= SummaryService::pendingFor($company, $date);

        if ($invoices->isEmpty()) {
            throw new RuntimeException('No hay boletas pendientes de resumir para esa fecha.');
        }

        if ($invoices->count() > SummaryService::MAX_DOCUMENTS) {
            return $invoices
                ->chunk(SummaryService::MAX_DOCUMENTS)
                ->flatMap(fn (Collection $chunk) => [SummaryService::sendSummary($company, $chunk, $date)])
                ->all();
        }

        return [SummaryService::sendSummary($company, $invoices, $date)];
    }

    public static function refreshOperation(SunatOperation $operation): SunatOperation
    {
        $operation->loadMissing('company');

        return SummaryService::refresh($operation);
    }

    /**
     * @return array{ok: bool, message: string, errors: array<int, string>, warnings: array<int, string>, details: array<int, string>}
     */
    public static function testConnection(Company $company, ?string $certificate = null): array
    {
        return ConnectionTest::run($company, $certificate);
    }

    public static function nextNumber(Company $company, string $invoiceType): string
    {
        return (string) SeriesService::peek($company, SeriesService::forInvoiceType($company, $invoiceType));
    }

    public static function statusLabel(?string $status): string
    {
        return ResponseMapper::label($status);
    }

    public static function numberToWords($number): string
    {
        $parts = explode('.', number_format((float) $number, 2, '.', ''));
        $integer = (int) $parts[0];
        $decimals = $parts[1] ?? '00';

        // SUNAT no exige tildes en la leyenda 1000; se usan las formas de su manual.
        $units = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $tens = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $teens = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $hundreds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($integer === 0) {
            return 'CERO';
        }

        if ($integer === 1) {
            return 'CON UN';
        }

        $words = '';

        if ($integer >= 1000000) {
            $millions = intdiv($integer, 1000000);
            $words .= ($millions === 1) ? 'UN MILLÓN' : static::convertHundreds($millions, $units, $tens, $teens, $hundreds).' MILLONES';
            $integer %= 1000000;
            if ($integer > 0) {
                $words .= ' ';
            }
        }

        if ($integer >= 1000) {
            $thousands = intdiv($integer, 1000);
            $words .= ($thousands === 1) ? 'MIL' : static::convertHundreds($thousands, $units, $tens, $teens, $hundreds).' MIL';
            $integer %= 1000;
            if ($integer > 0) {
                $words .= ' ';
            }
        }

        if ($integer > 0) {
            $words .= static::convertHundreds($integer, $units, $tens, $teens, $hundreds);
        }

        return trim($words)." CON {$decimals}/100";
    }

    protected static function convertHundreds($num, $units, $tens, $teens, $hundreds): string
    {
        $result = '';
        $h = intdiv($num, 100);
        $t = ($num % 100);

        if ($h > 0) {
            $result .= ($h === 1 && $t === 0) ? 'CIEN' : $hundreds[$h].' ';
        }

        if ($t > 0) {
            if ($t < 10) {
                $result .= $units[$t];
            } elseif ($t < 20) {
                $result .= $teens[$t - 10];
            } elseif ($t < 30) {
                // VEINTIUNO, VEINTIDÓS, VEINTISÉIS... sin la "y" que exige la regla del español.
                $result .= 'VEINTI'.($t === 21 ? 'UN' : $units[$t - 20]);
            } else {
                $u = $t % 10;
                $d = intdiv($t, 10);
                $result .= $tens[$d];

                if ($u > 0) {
                    // TREINTA Y UNO, CIENTO UNO: la forma "UN" solo se usa como número suelto.
                    $result .= ' Y '.($u === 1 ? 'UNO' : $units[$u]);
                }
            }
        }

        return trim($result);
    }
}
