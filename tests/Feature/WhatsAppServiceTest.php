<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_la_plantilla_con_el_enlace_firmado_y_guarda_el_estado(): void
    {
        Storage::fake('local');
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]], 200),
        ]);

        $company = $this->company();
        $invoice = $this->invoice($company);
        Storage::disk('local')->put($invoice->pdf_path, '%PDF-1.4 prueba');

        $result = WhatsAppService::sendInvoice($invoice, '987654321');

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['skipped']);
        $this->assertSame('+51987654321', $result['phone']);

        $invoice->refresh();

        $this->assertSame('sent', $invoice->whatsapp_status);
        $this->assertSame('wamid.TEST', $invoice->whatsapp_message_id);
        $this->assertNotNull($invoice->whatsapp_sent_at);
        $this->assertSame('987654321', $invoice->customer_phone);
        $this->assertNull($invoice->whatsapp_error);

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();
            $parameters = $data['template']['components'][0]['parameters'] ?? [];

            return str_contains($request->url(), '/v21.0/1234567890/messages')
                && $data['to'] === '51987654321'
                && $data['template']['name'] === 'comprobante_emitido'
                && $data['template']['language']['code'] === 'es'
                && ($parameters[0]['text'] ?? null) === 'JUAN PEREZ'
                && ($parameters[1]['text'] ?? null) === 'boleta B001-1'
                && ($parameters[2]['text'] ?? null) === '100.00'
                && str_contains((string) ($parameters[3]['text'] ?? ''), 'signature=');
        });
    }

    public function test_no_envia_si_whatsapp_esta_desactivado(): void
    {
        Http::fake();

        $company = $this->company(['whatsapp_enabled' => false]);
        $invoice = $this->invoice($company);

        $result = WhatsAppService::sendInvoice($invoice, '987654321');

        $this->assertTrue($result['skipped']);
        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_no_envia_si_el_telefono_no_es_valido(): void
    {
        Http::fake();

        $company = $this->company();
        $invoice = $this->invoice($company);

        $result = WhatsAppService::sendInvoice($invoice, '123');

        $this->assertTrue($result['skipped']);
        $this->assertStringContainsString('teléfono', $result['message']);
        Http::assertNothingSent();
    }

    public function test_registra_el_error_cuando_meta_rechaza_el_mensaje(): void
    {
        Storage::fake('local');
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not found']], 404),
        ]);

        $company = $this->company();
        $invoice = $this->invoice($company);
        Storage::disk('local')->put($invoice->pdf_path, '%PDF-1.4 prueba');

        $result = WhatsAppService::sendInvoice($invoice, '987654321');

        $this->assertFalse($result['ok']);

        $invoice->refresh();

        $this->assertSame('failed', $invoice->whatsapp_status);
        $this->assertStringContainsString('Template not found', (string) $invoice->whatsapp_error);
        $this->assertNull($invoice->whatsapp_sent_at);
    }

    public function test_el_enlace_publico_solo_funciona_con_firma_valida(): void
    {
        Storage::fake('local');

        $company = $this->company();
        $invoice = $this->invoice($company);
        Storage::disk('local')->put($invoice->pdf_path, '%PDF-1.4 prueba');

        $signed = URL::temporarySignedRoute(
            'invoices.pdf.public',
            now()->addMinutes(5),
            ['invoice' => $invoice->id],
        );

        $this->get($signed)->assertOk();
        $this->get(route('invoices.pdf.public', $invoice))->assertForbidden();
    }

    private function company(array $attributes = []): Company
    {
        return Company::create([
            'ruc' => '20609937531',
            'business_name' => 'DENTAMOR SAC',
            'sunat_username' => '72901015',
            'sunat_password' => 'clave',
            'whatsapp_enabled' => true,
            'whatsapp_phone_number_id' => '1234567890',
            'whatsapp_token' => 'EAAB-test-token',
            'whatsapp_api_version' => 'v21.0',
            'whatsapp_template_name' => 'comprobante_emitido',
            'whatsapp_template_lang' => 'es',
            ...$attributes,
        ]);
    }

    private function invoice(Company $company): Invoice
    {
        $client = Client::create([
            'doc_type' => 'DNI',
            'doc_number' => '12345678',
            'name' => 'JUAN PEREZ',
            'phone' => '987654321',
        ]);

        return Invoice::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'invoice_type' => 'B',
            'serie' => 'B001',
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'currency' => 'PEN',
            'subtotal' => 84.75,
            'igv' => 15.25,
            'total' => 100.00,
            'sunat_status' => 'accepted',
            'pdf_path' => 'sunat/pdf/B001-1.pdf',
        ]);
    }
}
