<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice as GreenterInvoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;

class SunatService
{
    protected static function createSee(Company $company): See
    {
        $certContent = Storage::disk('local')->get($company->certificate_path);

        $see = new See();
        $see->setCertificate($certContent);
        $see->setClaveSOL($company->ruc, $company->sunat_username, $company->sunat_password);
        $see->setCachePath(storage_path('app/sunat/cache'));

        if ($company->production) {
            $see->setService(SunatEndpoints::FE_PRODUCCION);
        } else {
            $see->setService(SunatEndpoints::FE_BETA);
        }

        return $see;
    }

    protected static function buildGreenterCompany(Company $company): \Greenter\Model\Company\Company
    {
        $address = new Address();
        $address->setUbigueo('150101')
            ->setDepartamento('LIMA')
            ->setProvincia('LIMA')
            ->setDistrito('LIMA')
            ->setDireccion($company->address ?? '')
            ->setCodLocal('0000');

        $emitter = new \Greenter\Model\Company\Company();
        $emitter->setRuc($company->ruc)
            ->setRazonSocial($company->business_name)
            ->setNombreComercial($company->commercial_name ?? $company->business_name)
            ->setAddress($address);

        return $emitter;
    }

    protected static function buildGreenterClient(?\App\Models\Client $client, ?string $fallbackName, ?string $fallbackDoc): Client
    {
        $greenterClient = new Client();

        if ($client) {
            $docTypeMap = ['DNI' => '1', 'RUC' => '6', 'CE' => '4'];
            $greenterClient->setTipoDoc($docTypeMap[$client->doc_type] ?? '1')
                ->setNumDoc($client->doc_number)
                ->setRznSocial($client->name);
        } else {
            $greenterClient->setTipoDoc('-')
                ->setNumDoc($fallbackDoc ?? '00000000')
                ->setRznSocial($fallbackName ?? 'CONSUMIDOR FINAL');
        }

        return $greenterClient;
    }

    public static function sendInvoice(Invoice $invoice): array
    {
        $company = $invoice->company;
        $see = static::createSee($company);

        $emitter = static::buildGreenterCompany($company);
        $greenterClient = static::buildGreenterClient($invoice->client, null, null);

        $greenterInvoice = new GreenterInvoice();
        $greenterInvoice->setUblVersion('2.1')
            ->setTipoDoc($invoice->invoice_type === 'F' ? '01' : '03')
            ->setSerie($invoice->serie)
            ->setCorrelativo((string) $invoice->number)
            ->setFechaEmision(new DateTime($invoice->issue_date))
            ->setCompany($emitter)
            ->setClient($greenterClient)
            ->setTipoMoneda($invoice->currency)
            ->setTipoOperacion('0101')
            ->setMtoOperGravadas($invoice->subtotal)
            ->setMtoIGV($invoice->igv)
            ->setTotalImpuestos($invoice->igv)
            ->setMtoImpVenta($invoice->total)
            ->setValorVenta($invoice->subtotal)
            ->setSubTotal($invoice->total)
            ->setFormaPago(new FormaPagoContado());

        $details = [];
        foreach ($invoice->items as $item) {
            $precioTotal = (float) $item->unit_price * (int) $item->quantity;
            $base = (float) $item->subtotal;
            $igvLine = round($precioTotal - $base, 2);
            $valorUnitario = round((float) $item->unit_price / 1.18, 6);

            $detail = new SaleDetail();
            $detail->setUnidad('ZZ')
                ->setCantidad((float) $item->quantity)
                ->setDescripcion($item->description)
                ->setMtoValorUnitario($valorUnitario)
                ->setMtoPrecioUnitario((float) $item->unit_price)
                ->setMtoBaseIgv($base)
                ->setPorcentajeIgv(18.00)
                ->setIgv($igvLine)
                ->setTipAfeIgv('10')
                ->setTotalImpuestos($igvLine)
                ->setMtoValorVenta($base);

            $details[] = $detail;
        }
        $greenterInvoice->setDetails($details);

        $totalWords = static::numberToWords($invoice->total) . ' ' . ($invoice->currency === 'PEN' ? 'SOLES' : 'DOLARES');
        $legend = new Legend();
        $legend->setCode('1000')->setValue($totalWords);
        $greenterInvoice->setLegends([$legend]);

        try {
            $result = $see->send($greenterInvoice);

            $xmlContent = $see->getFactory()->getLastXml();
            $xmlPath = 'sunat/xml/' . $greenterInvoice->getName() . '.xml';
            Storage::disk('local')->put($xmlPath, $xmlContent);
            $invoice->update(['xml_path' => $xmlPath]);

            if ($result->isSuccess()) {
                $cdr = $result->getCdrResponse();
                $cdrZip = $result->getCdrZip();

                $cdrPath = 'sunat/cdr/' . $greenterInvoice->getName() . '.zip';
                Storage::disk('local')->put($cdrPath, $cdrZip);

                $invoice->update([
                    'sunat_status' => $cdr->isAccepted() ? 'accepted' : 'rejected',
                    'sunat_description' => $cdr->getDescription(),
                    'hash_cpe' => $cdr->getId(),
                    'cdr_path' => $cdrPath,
                ]);

                return [
                    'success' => true,
                    'code' => $cdr->getCode(),
                    'description' => $cdr->getDescription(),
                    'accepted' => $cdr->isAccepted(),
                ];
            }

            $error = $result->getError();
            $invoice->update([
                'sunat_status' => 'error',
                'sunat_description' => $error->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => $error->getCode(),
                'description' => $error->getMessage(),
            ];
        } catch (\Throwable $e) {
            try {
                $xmlContent = $see->getFactory()->getLastXml();
                if ($xmlContent) {
                    $xmlPath = 'sunat/xml/' . $greenterInvoice->getName() . '.xml';
                    Storage::disk('local')->put($xmlPath, $xmlContent);
                    $invoice->update(['xml_path' => $xmlPath]);
                }
            } catch (\Throwable $ignored) {}

            $invoice->update([
                'sunat_status' => 'error',
                'sunat_description' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => 'EXCEPTION',
                'description' => $e->getMessage(),
            ];
        }
    }

    public static function numberToWords($number): string
    {
        $parts = explode('.', number_format($number, 2, '.', ''));
        $integer = (int) $parts[0];
        $decimals = $parts[1] ?? '00';

        $units = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $tens = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $teens = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $hundreds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($integer === 0) return 'CERO';
        if ($integer === 1) return 'CON UN';

        $words = '';
        if ($integer >= 1000000) {
            $millions = intdiv($integer, 1000000);
            $words .= ($millions === 1) ? 'UN MILLÓN' : static::convertHundreds($millions, $units, $tens, $teens, $hundreds) . ' MILLONES';
            $integer %= 1000000;
            if ($integer > 0) $words .= ' ';
        }
        if ($integer >= 1000) {
            $thousands = intdiv($integer, 1000);
            $words .= ($thousands === 1) ? 'MIL' : static::convertHundreds($thousands, $units, $tens, $teens, $hundreds) . ' MIL';
            $integer %= 1000;
            if ($integer > 0) $words .= ' ';
        }
        if ($integer > 0) {
            $words .= static::convertHundreds($integer, $units, $tens, $teens, $hundreds);
        }

        return trim($words) . " CON {$decimals}/100";
    }

    protected static function convertHundreds($num, $units, $tens, $teens, $hundreds): string
    {
        $result = '';
        $h = intdiv($num, 100);
        $t = ($num % 100);
        if ($h > 0) {
            $result .= ($h === 1 && $t === 0) ? 'CIEN' : $hundreds[$h] . ' ';
        }
        if ($t > 0) {
            if ($t < 10) {
                $result .= $units[$t];
            } elseif ($t < 20) {
                $result .= $teens[$t - 10];
            } else {
                $u = $t % 10;
                $d = intdiv($t, 10);
                $result .= $tens[$d];
                if ($u > 0) {
                    $result .= 'Y ' . $units[$u];
                }
            }
        }
        return trim($result);
    }
}
