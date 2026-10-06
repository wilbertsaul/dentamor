<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Invoices\Voided;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceVoidedTest extends TestCase
{
    use RefreshDatabase;

    private int $rucSuffix = 20609937700;

    public function test_la_ruta_de_baja_resuelve_el_comprobante_como_modelo(): void
    {
        $invoice = $this->acceptedInvoice();

        $this->actingAs($this->verifiedUser())
            ->get(route('invoices.anular', $invoice))
            ->assertOk()
            ->assertSee($invoice->full_number);
    }

    public function test_la_ruta_de_baja_responde_404_si_el_comprobante_no_lo_admite(): void
    {
        $invoice = $this->acceptedInvoice();
        $invoice->forceFill(['sunat_status' => 'rejected'])->save();

        $this->actingAs($this->verifiedUser())
            ->get(route('invoices.anular', $invoice))
            ->assertNotFound();
    }

    public function test_el_motivo_escrito_llega_a_la_validacion(): void
    {
        $component = Livewire::test(Voided::class, ['invoice' => $this->acceptedInvoice('F')])
            ->set('motivo', 'Error en la emision del comprobante')
            ->call('anular')
            ->assertHasNoErrors()
            ->assertSet('motivo', 'Error en la emision del comprobante');

        $this->assertNotSame('', (string) $component->get('errorMessage'));
    }

    public function test_una_boleta_puede_anularse_sin_motivo(): void
    {
        Livewire::test(Voided::class, ['invoice' => $this->acceptedInvoice('B')])
            ->set('motivo', '')
            ->call('anular')
            ->assertHasNoErrors();
    }

    public function test_una_factura_rechaza_un_motivo_muy_corto(): void
    {
        Livewire::test(Voided::class, ['invoice' => $this->acceptedInvoice('F')])
            ->set('motivo', 'x')
            ->call('anular')
            ->assertHasErrors(['motivo']);
    }

    private function verifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function acceptedInvoice(string $type = 'B'): Invoice
    {
        $this->rucSuffix++;

        $company = Company::create([
            'ruc' => (string) $this->rucSuffix,
            'business_name' => 'DENTAMOR COMPANY CLINICA ODONTOLOGICA SOCIEDAD ANONIMA CERRADA',
            'sunat_username' => '72901015',
            'sunat_password' => 'clavePrincipal',
            'production' => true,
        ]);

        return Invoice::create([
            'company_id' => $company->id,
            'invoice_type' => $type,
            'serie' => $type === 'F' ? 'F002' : 'B002',
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'currency' => 'PEN',
            'subtotal' => 100,
            'igv' => 18,
            'total' => 118,
            'sunat_status' => 'accepted',
            'sunat_code' => '0',
            'production' => true,
        ]);
    }
}
