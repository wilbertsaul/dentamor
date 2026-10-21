<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Invoice;
use App\Services\Sunat\SeriesService;
use App\Services\SunatService;
use PHPUnit\Framework\TestCase;

class SunatSeriesTest extends TestCase
{
    public function test_la_leyenda_en_letras_incluye_el_importe_en_centesimos(): void
    {
        $this->assertSame('CIENTO DIECIOCHO CON 00/100', SunatService::numberToWords(118));
        $this->assertSame('MIL CON 50/100', SunatService::numberToWords(1000.5));
        $this->assertSame('VEINTIUN CON 00/100', SunatService::numberToWords(21));
        $this->assertSame('VEINTISEIS CON 00/100', SunatService::numberToWords(26));
        $this->assertSame('TREINTA Y UNO CON 00/100', SunatService::numberToWords(31));
        $this->assertSame('TRESCIENTOS CUARENTA Y CINCO CON 90/100', SunatService::numberToWords(345.9));
    }

    public function test_el_tipo_de_documento_depende_de_la_serie(): void
    {
        $this->assertSame(Invoice::TYPE_FACTURA, SeriesService::documentTypeFor('F002'));
        $this->assertSame(Invoice::TYPE_BOLETA, SeriesService::documentTypeFor('B002'));
        $this->assertSame(Invoice::TYPE_NOTA_CREDITO, SeriesService::documentTypeFor('FC02'));
        $this->assertSame(Invoice::TYPE_NOTA_CREDITO, SeriesService::documentTypeFor('BC02'));
        $this->assertNull(SeriesService::documentTypeFor('RVA'));
    }

    public function test_las_series_de_nota_de_credito_siguen_al_tipo_original(): void
    {
        $company = $this->company();

        $this->assertSame('FC02', SeriesService::creditNoteSeries($company, 'F'));
        $this->assertSame('BC02', SeriesService::creditNoteSeries($company, 'B'));
    }

    public function test_la_serie_se_deriva_del_tipo_de_comprobante(): void
    {
        $company = $this->company();

        $this->assertSame('F002', SeriesService::forInvoiceType($company, 'F'));
        $this->assertSame('B002', SeriesService::forInvoiceType($company, 'B'));
    }

    public function test_en_beta_se_usan_las_series_de_prueba(): void
    {
        $company = new Company([
            'production' => false,
            'boleta_serie' => 'B002',
            'factura_serie' => 'F002',
            'nc_boleta_serie' => 'BC02',
            'nc_factura_serie' => 'FC02',
        ]);

        $this->assertSame('PR01', SeriesService::forInvoiceType($company, 'B'));
        $this->assertSame('PR02', SeriesService::forInvoiceType($company, 'F'));
        $this->assertSame('PR03', SeriesService::creditNoteSeries($company, 'B'));
        $this->assertSame('PR04', SeriesService::creditNoteSeries($company, 'F'));
    }

    public function test_las_series_de_prueba_se_pueden_personalizar(): void
    {
        $company = new Company([
            'production' => false,
            'boleta_serie_beta' => 'T001',
            'factura_serie_beta' => 'T002',
        ]);

        $this->assertSame('T001', SeriesService::forInvoiceType($company, 'B'));
        $this->assertSame('T002', SeriesService::forInvoiceType($company, 'F'));
    }

    private function company(): Company
    {
        return new Company([
            'production' => true,
            'boleta_serie' => 'B002',
            'factura_serie' => 'F002',
            'nc_boleta_serie' => 'BC02',
            'nc_factura_serie' => 'FC02',
        ]);
    }
}
