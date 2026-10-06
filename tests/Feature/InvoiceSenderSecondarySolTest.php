<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Services\Sunat\InvoiceSender;
use App\Services\Sunat\ResponseMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSenderSecondarySolTest extends TestCase
{
    use RefreshDatabase;

    private int $rucSuffix = 20609937600;

    public function test_el_envio_de_comprobantes_exige_el_usuario_secundario_en_produccion(): void
    {
        $invoice = $this->invoice($this->company(['certificate_path' => 'certificates/demo.pem']));

        $result = InvoiceSender::send($invoice);

        $this->assertFalse($result['success']);
        $this->assertSame('SETUP', $result['code']);
        $this->assertMatchesRegularExpression('/secundario/i', $result['description']);
        $this->assertSame(ResponseMapper::ERROR, $invoice->fresh()->sunat_status);
    }

    public function test_con_usuario_secundario_la_validacion_de_credenciales_se_supera(): void
    {
        $invoice = $this->invoice($this->company([
            'sunat_username_secondary' => '72351663',
            'sunat_password_secondary' => 'claveSecundaria',
            'certificate_path' => '',
        ]));

        $result = InvoiceSender::send($invoice);

        $this->assertFalse($result['success']);
        $this->assertSame('SETUP', $result['code']);
        $this->assertDoesNotMatchRegularExpression('/secundario/i', $result['description']);
        $this->assertMatchesRegularExpression('/certificado/i', $result['description']);
    }

    private function company(array $attributes): Company
    {
        $this->rucSuffix++;

        return Company::create([
            'ruc' => (string) $this->rucSuffix,
            'business_name' => 'DENTAMOR COMPANY CLINICA ODONTOLOGICA SOCIEDAD ANONIMA CERRADA',
            'sunat_username' => '72901015',
            'sunat_password' => 'clavePrincipal',
            'production' => true,
            ...$attributes,
        ]);
    }

    private function invoice(Company $company): Invoice
    {
        return Invoice::create([
            'company_id' => $company->id,
            'invoice_type' => 'B',
            'serie' => 'B002',
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'currency' => 'PEN',
            'subtotal' => 100,
            'igv' => 18,
            'total' => 118,
        ]);
    }
}
