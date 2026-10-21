<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Services\Sunat\SeriesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private int $rucSuffix = 20609937500;

    public function test_la_serie_configurada_se_usa_en_lugar_de_las_constantes(): void
    {
        $company = $this->company([
            'boleta_serie' => 'B002',
            'factura_serie' => 'F002',
            'nc_boleta_serie' => 'BC02',
            'nc_factura_serie' => 'FC02',
        ]);

        $this->assertSame('B002', SeriesService::forInvoiceType($company, 'B'));
        $this->assertSame('F002', SeriesService::forInvoiceType($company, 'F'));
        $this->assertSame('BC02', SeriesService::creditNoteSeries($company, 'B'));
        $this->assertSame('FC02', SeriesService::creditNoteSeries($company, 'F'));
    }

    public function test_los_accesores_del_modelo_devuelven_la_serie_configurada(): void
    {
        $company = $this->company([
            'boleta_serie' => 'B010',
            'factura_serie' => 'F010',
            'nc_boleta_serie' => 'BC10',
            'nc_factura_serie' => 'FC10',
        ]);

        $this->assertSame('B010', $company->boletaSerie());
        $this->assertSame('F010', $company->facturaSerie());
        $this->assertSame('BC10', $company->ncBoletaSerie());
        $this->assertSame('FC10', $company->ncFacturaSerie());
    }

    public function test_los_accesores_aplican_el_default_cuando_la_serie_esta_vacia(): void
    {
        $company = $this->company([
            'boleta_serie' => '',
            'factura_serie' => '',
            'nc_boleta_serie' => '',
            'nc_factura_serie' => '',
        ]);

        $this->assertSame('B002', $company->boletaSerie());
        $this->assertSame('F002', $company->facturaSerie());
        $this->assertSame('BC02', $company->ncBoletaSerie());
        $this->assertSame('FC02', $company->ncFacturaSerie());
    }

    public function test_una_serie_nueva_empieza_en_uno_e_incrementa(): void
    {
        $company = $this->company(['boleta_serie' => 'B002']);

        $this->assertSame(1, SeriesService::peek($company, 'B002'));
        $this->assertSame(1, SeriesService::next($company, 'B002'));
        $this->assertSame(2, SeriesService::next($company, 'B002'));
        $this->assertSame(3, SeriesService::peek($company, 'B002'));
    }

    public function test_las_series_no_se_comparten_entre_tipos(): void
    {
        $company = $this->company([
            'boleta_serie' => 'B002',
            'factura_serie' => 'F002',
        ]);

        SeriesService::next($company, 'B002');

        $this->assertSame(1, SeriesService::peek($company, 'F002'));
        $this->assertSame(2, SeriesService::peek($company, 'B002'));
    }

    public function test_en_beta_se_usan_las_series_de_prueba_por_defecto(): void
    {
        $company = $this->company(['production' => false]);

        $this->assertSame('PR01', SeriesService::forInvoiceType($company, 'B'));
        $this->assertSame('PR02', SeriesService::forInvoiceType($company, 'F'));
        $this->assertSame('PR03', SeriesService::creditNoteSeries($company, 'B'));
        $this->assertSame('PR04', SeriesService::creditNoteSeries($company, 'F'));
    }

    public function test_las_series_de_prueba_se_pueden_personalizar(): void
    {
        $company = $this->company([
            'production' => false,
            'boleta_serie_beta' => 'T001',
            'factura_serie_beta' => 'T002',
        ]);

        $this->assertSame('T001', SeriesService::forInvoiceType($company, 'B'));
        $this->assertSame('T002', SeriesService::forInvoiceType($company, 'F'));
        $this->assertSame('T001', $company->boletaSerieBeta());
    }

    public function test_los_accesores_beta_aplican_el_default_cuando_estan_vacios(): void
    {
        $company = $this->company([
            'production' => false,
            'boleta_serie_beta' => '',
            'factura_serie_beta' => '',
            'nc_boleta_serie_beta' => '',
            'nc_factura_serie_beta' => '',
        ]);

        $this->assertSame('PR01', $company->boletaSerieBeta());
        $this->assertSame('PR02', $company->facturaSerieBeta());
        $this->assertSame('PR03', $company->ncBoletaSerieBeta());
        $this->assertSame('PR04', $company->ncFacturaSerieBeta());
    }

    public function test_al_activar_producción_se_vuelven_a_usar_las_series_reales(): void
    {
        $company = $this->company([
            'production' => false,
            'boleta_serie' => 'B002',
            'factura_serie' => 'F002',
        ]);

        $this->assertSame('PR01', SeriesService::forInvoiceType($company, 'B'));

        $company->update(['production' => true]);

        $this->assertSame('B002', SeriesService::forInvoiceType($company, 'B'));
        $this->assertSame('F002', SeriesService::forInvoiceType($company, 'F'));
    }

    private function company(array $attributes): Company
    {
        $this->rucSuffix++;

        return Company::create([
            'ruc' => (string) $this->rucSuffix,
            'business_name' => 'DENTAMOR COMPANY CLINICA ODONTOLOGICA SOCIEDAD ANONIMA CERRADA',
            'sunat_username' => '72901015',
            'sunat_password' => 'clave',
            'production' => true,
            ...$attributes,
        ]);
    }
}
