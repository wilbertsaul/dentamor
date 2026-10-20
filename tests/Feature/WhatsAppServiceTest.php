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

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.base_url' => 'http://waha.test:3000',
            'whatsapp.api_key' => 'test-api-key',
        ]);
    }

    public function test_envia_el_pdf_adjunto_con_caption_y_guarda_el_estado(): void
    {
        Storage::fake('local');
        Http::fake([
            'waha.test:3000/api/sendFile' => Http::response(['id' => 'wamid.TEST', '_data' => ['id' => 'wamid.TEST']], 201),
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

            return str_contains($request->url(), '/api/sendFile')
                && $request->hasHeader('X-Api-Key', 'test-api-key')
                && $data['session'] === 'default'
                && $data['chatId'] === '51987654321@c.us'
                && str_contains($data['caption'], 'JUAN PEREZ')
                && str_contains($data['caption'], 'boleta B001-1')
                && str_contains($data['caption'], '100.00')
                && $data['file']['mimetype'] === 'application/pdf'
                && $data['file']['filename'] === 'B001-1.pdf'
                && base64_decode((string) $data['file']['data'], true) !== false;
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

    public function test_registra_el_error_cuando_waha_rechaza_el_mensaje(): void
    {
        Storage::fake('local');
        Http::fake([
            'waha.test:3000/api/sendFile' => Http::response(['message' => 'session not connected'], 422),
        ]);

        $company = $this->company();
        $invoice = $this->invoice($company);
        Storage::disk('local')->put($invoice->pdf_path, '%PDF-1.4 prueba');

        $result = WhatsAppService::sendInvoice($invoice, '987654321');

        $this->assertFalse($result['ok']);

        $invoice->refresh();

        $this->assertSame('failed', $invoice->whatsapp_status);
        $this->assertStringContainsString('session not connected', (string) $invoice->whatsapp_error);
        $this->assertNull($invoice->whatsapp_sent_at);
    }

    public function test_envia_mensaje_de_prueba_por_texto(): void
    {
        Http::fake([
            'waha.test:3000/api/sendText' => Http::response(['id' => 'wamid.TEST'], 201),
        ]);

        $company = $this->company();

        $result = WhatsAppService::sendTest($company, '987654321');

        $this->assertTrue($result['ok']);
        $this->assertSame('+51987654321', $result['phone']);

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return str_contains($request->url(), '/api/sendText')
                && $data['chatId'] === '51987654321@c.us'
                && filled($data['text']);
        });
    }

    public function test_consulta_el_estado_de_la_sesion(): void
    {
        Http::fake([
            'waha.test:3000/api/sessions/default' => Http::response(['name' => 'default', 'status' => 'WORKING'], 200),
        ]);

        $company = $this->company();

        $status = WhatsAppService::sessionStatus($company);

        $this->assertTrue($status['ok']);
        $this->assertSame('WORKING', $status['status']);
        $this->assertSame('default', $status['name']);
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
            'whatsapp_session' => 'default',
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
