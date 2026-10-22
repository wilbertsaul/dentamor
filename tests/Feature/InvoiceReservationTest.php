<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Invoices\Index;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceReservationTest extends TestCase
{
    use RefreshDatabase;

    private ?Company $company = null;

    public function test_una_reserva_convertida_se_vincula_y_se_oculta(): void
    {
        $reservation = $this->reservation();
        $invoice = $this->invoice('B');

        $reservation->markConverted($invoice);

        $this->assertSoftDeleted($reservation);
        $this->assertSame(1, Invoice::count());

        $hidden = Invoice::withTrashed()->findOrFail($reservation->id);
        $this->assertSame('converted', $hidden->sunat_status);
        $this->assertSame($invoice->id, $hidden->converted_invoice_id);
        $this->assertFalse($hidden->is_reservation);

        $this->assertNotSoftDeleted($invoice);
        $this->assertSame('accepted', $invoice->fresh()->sunat_status);
    }

    public function test_una_reserva_convertida_no_aparece_en_la_lista_ni_suma_en_los_totales(): void
    {
        $reservation = $this->reservation();
        $invoice = $this->invoice('B');

        $reservation->markConverted($invoice);

        Livewire::test(Index::class)
            ->assertDontSee('RVA-1')
            ->assertSee('BP01-1')
            ->assertViewHas('totalMes', fn ($total) => (float) $total === 118.0)
            ->assertViewHas('reservasPendientes', 0);
    }

    public function test_el_tacho_elimina_una_reserva_pendiente_sin_tocar_sunat(): void
    {
        $reservation = $this->reservation();

        Livewire::test(Index::class)
            ->call('delete', $reservation->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($reservation);

        $hidden = Invoice::withTrashed()->findOrFail($reservation->id);
        $this->assertSame('voided', $hidden->sunat_status);
        $this->assertSame('Reserva eliminada manualmente', $hidden->sunat_description);
        $this->assertSame(0, $hidden->paymentRecords()->count());
    }

    public function test_el_tacho_no_elimina_comprobantes_emitidos(): void
    {
        $invoice = $this->invoice('B');

        Livewire::test(Index::class)
            ->call('delete', $invoice->id);

        $this->assertNotSoftDeleted($invoice);
        $this->assertSame('accepted', $invoice->fresh()->sunat_status);
    }

    public function test_el_tacho_no_elimina_borradores_ni_rechazados(): void
    {
        $draft = $this->invoice('B', ['sunat_status' => 'draft', 'number' => 2]);
        $rejected = $this->invoice('B', ['sunat_status' => 'rejected', 'number' => 3]);

        Livewire::test(Index::class)
            ->call('delete', $draft->id)
            ->call('delete', $rejected->id);

        $this->assertNotSoftDeleted($draft);
        $this->assertNotSoftDeleted($rejected);
    }

    private function company(): Company
    {
        return $this->company ??= Company::create([
            'ruc' => '20609937801',
            'business_name' => 'DENTAMOR TEST SOCIEDAD ANONIMA CERRADA',
            'sunat_username' => '72901015',
            'sunat_password' => 'clave',
            'production' => false,
        ]);
    }

    private function reservation(): Invoice
    {
        return Invoice::create([
            'company_id' => $this->company()->id,
            'invoice_type' => 'R',
            'serie' => 'RVA',
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'currency' => 'PEN',
            'subtotal' => 100,
            'igv' => 18,
            'total' => 118,
            'sunat_status' => 'draft',
            'is_reservation' => true,
            'reserved_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function invoice(string $type, array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'company_id' => $this->company()->id,
            'invoice_type' => $type,
            'serie' => $type === 'F' ? 'FP01' : 'BP01',
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'currency' => 'PEN',
            'subtotal' => 100,
            'igv' => 18,
            'total' => 118,
            'sunat_status' => 'accepted',
            'sunat_code' => '0',
            'production' => false,
        ], $attributes));
    }
}
