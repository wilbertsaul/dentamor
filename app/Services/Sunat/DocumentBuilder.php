<?php

namespace App\Services\Sunat;

use App\Models\Invoice as InvoiceModel;
use App\Services\SunatService;
use Greenter\Model\Sale\BaseSale;
use Greenter\Model\Sale\Document;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice as GreenterInvoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use RuntimeException;

class DocumentBuilder
{
    /**
     * Catálogo 03 - Unidad de medida. ZZ = servicios (mutatis mutandis).
     */
    public const UNIT_SERVICE = 'ZZ';

    public const IGV_RATE = 18.00;

    /**
     * Catálogo 07 - Afectación del IGV. 10 = Gravada - Operación Onerosa.
     */
    public const AFFECTION_GRAVADA = '10';

    /**
     * Catálogo 09 - Motivo de emisión de nota de crédito. 01 = Anulación de la operación.
     */
    public const CREDIT_NOTE_REASON_CODE = '01';

    public const CREDIT_NOTE_REASON = 'ANULACION DE LA OPERACION';

    public const MAX_DESCRIPTION = 500;

    public static function build(InvoiceModel $invoice): BaseSale
    {
        $invoice->loadMissing(['items', 'relatedInvoice']);
        static::assertEmitible($invoice);

        $company = $invoice->company;
        SeeFactory::assertAddress($company);

        $type = $invoice->documentType();
        $document = $type === InvoiceModel::TYPE_NOTA_CREDITO ? new Note : new GreenterInvoice;

        $document->setUblVersion('2.1')
            ->setTipoDoc($type)
            ->setSerie($invoice->serie)
            ->setCorrelativo((string) $invoice->number)
            ->setFechaEmision(static::issueDate($invoice))
            ->setCompany(CompanyBuilder::build($company))
            ->setClient(ClientBuilder::build($invoice->client))
            ->setTipoMoneda($invoice->currency ?: 'PEN')
            ->setDetails(static::details($invoice));

        $subtotal = (float) $invoice->subtotal;
        $igv = (float) $invoice->igv;
        $total = (float) $invoice->total;

        $document->setMtoOperGravadas($subtotal)
            ->setMtoIGV($igv)
            ->setTotalImpuestos($igv)
            ->setValorVenta($subtotal)
            ->setMtoImpVenta($total)
            ->setFormaPago(new FormaPagoContado);

        if ($document instanceof GreenterInvoice) {
            $document->setTipoOperacion('0101')
                ->setSubTotal($total);
        }

        if ($document instanceof Note) {
            static::applyCreditNote($document, $invoice);
        }

        $document->setLegends([static::legend($total, (string) $invoice->currency)]);

        return $document;
    }

    public static function assertEmitible(InvoiceModel $invoice): void
    {
        if ($invoice->is_reservation) {
            throw new RuntimeException('Una reserva no puede enviarse a SUNAT; primero debe girarse.');
        }

        if (! $invoice->isElectronicSerie()) {
            throw new RuntimeException(
                "La serie {$invoice->serie} no corresponde a un comprobante electrónico (debe iniciar con F o B)."
            );
        }

        if ((int) $invoice->number < 1) {
            throw new RuntimeException('El número de comprobante debe ser mayor o igual a 1.');
        }

        if (! in_array($invoice->currency, ['PEN', 'USD'], true)) {
            throw new RuntimeException("La moneda {$invoice->currency} no está soportada por SUNAT (use PEN o USD).");
        }

        if ($invoice->items->isEmpty()) {
            throw new RuntimeException('El comprobante no tiene ítems; SUNAT no acepta comprobantes sin detalle.');
        }
    }

    private static function applyCreditNote(Note $note, InvoiceModel $invoice): void
    {
        $related = $invoice->relatedInvoice;

        if ($related === null) {
            throw new RuntimeException('La nota de crédito debe indicar el comprobante que afecta.');
        }

        $note->setTipDocAfectado($related->documentType())
            ->setNumDocfectado($related->full_number)
            ->setCodMotivo(static::CREDIT_NOTE_REASON_CODE)
            ->setDesMotivo(static::CREDIT_NOTE_REASON);
    }

    private static function details(InvoiceModel $invoice): array
    {
        $details = [];

        foreach ($invoice->items as $item) {
            $quantity = (float) $item->quantity;

            if ($quantity <= 0) {
                throw new RuntimeException('La cantidad de cada ítem debe ser mayor que cero.');
            }

            $base = (float) $item->subtotal;
            $lineTotal = round((float) $item->unit_price * $quantity, 2);
            $igv = round($lineTotal - $base, 2);

            $detail = new SaleDetail;
            $detail->setUnidad(static::UNIT_SERVICE)
                ->setCantidad($quantity)
                ->setDescripcion(static::description((string) $item->description))
                ->setMtoValorUnitario(round($base / $quantity, 6))
                ->setMtoPrecioUnitario((float) $item->unit_price)
                ->setMtoBaseIgv($base)
                ->setPorcentajeIgv(static::IGV_RATE)
                ->setIgv($igv)
                ->setTipAfeIgv(static::AFFECTION_GRAVADA)
                ->setTotalImpuestos($igv)
                ->setMtoValorVenta($base);

            $details[] = $detail;
        }

        return $details;
    }

    private static function description(string $description): string
    {
        $description = trim(preg_replace('/\s+/u', ' ', $description) ?? '');

        if ($description === '') {
            throw new RuntimeException('Cada ítem necesita una descripción.');
        }

        return mb_substr($description, 0, static::MAX_DESCRIPTION);
    }

    private static function legend(float $total, string $currency): Legend
    {
        $legend = new Legend;
        $legend->setCode('1000')
            ->setValue(SunatService::numberToWords($total).' '.($currency === 'USD' ? 'DOLARES' : 'SOLES'));

        return $legend;
    }

    private static function issueDate(InvoiceModel $invoice): \DateTimeInterface
    {
        $date = $invoice->issue_date?->copy() ?? now();

        return $date->setTime(0, 0, 0);
    }

    public static function relatedDocument(InvoiceModel $invoice): Document
    {
        $document = new Document;
        $document->setTipoDoc($invoice->documentType())
            ->setNroDoc($invoice->full_number)
            ->setFechaEmision(static::issueDate($invoice));

        return $document;
    }
}
