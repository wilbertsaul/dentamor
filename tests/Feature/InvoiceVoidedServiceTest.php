<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\SunatOperation;
use App\Services\Sunat\ResponseMapper;
use App\Services\Sunat\SummaryService;
use App\Services\SunatService;
use Greenter\Model\Response\CdrResponse;
use Greenter\Model\Response\Error;
use Greenter\Model\Response\StatusResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InvoiceVoidedServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $rucSuffix = 20609937800;

    public function test_una_factura_se_da_de_baja_con_comunicacion_ra(): void
    {
        $invoice = $this->acceptedInvoice('F');

        $operation = SunatService::voidInvoice($invoice, 'Error en la emision del comprobante');

        $this->assertSame(SunatOperation::TYPE_VOIDED, $operation->type);
        $this->assertTrue($operation->fresh()->voidsDocuments());
    }

    public function test_una_factura_exige_motivo_de_baja(): void
    {
        $this->expectException(RuntimeException::class);

        SunatService::voidInvoice($this->acceptedInvoice('F'));
    }

    public function test_una_boleta_se_da_de_baja_con_resumen_diario_que_anula(): void
    {
        $invoice = $this->acceptedInvoice('B');

        $operation = SunatService::voidInvoice($invoice);

        $this->assertSame(SunatOperation::TYPE_SUMMARY, $operation->type);
        $this->assertTrue($operation->fresh()->voidsDocuments());
    }

    public function test_el_estado_anulado_no_requiere_motivo_en_boletas(): void
    {
        $operation = SunatService::voidInvoice($this->acceptedInvoice('B'));

        $this->assertSame(1, $operation->documents_count);
    }

    public function test_al_aceptarse_una_baja_se_marca_el_comprobante_como_anulado(): void
    {
        $invoice = $this->acceptedInvoice('B');
        $operation = $this->operation($invoice, SunatOperation::TYPE_SUMMARY, true);

        SummaryService::markVoidedDocuments($operation, 'accepted');

        $this->assertSame('voided', $invoice->fresh()->sunat_status);
    }

    public function test_una_operacion_queda_ligada_a_sus_comprobantes(): void
    {
        $invoice = $this->acceptedInvoice('B');
        $operation = $this->unlinkedOperation($invoice, SunatOperation::TYPE_SUMMARY, true);

        $this->assertNull($invoice->fresh()->sunat_operation_id);

        SummaryService::linkInvoices($operation, [$invoice->id]);

        $this->assertSame($operation->id, $invoice->fresh()->sunat_operation_id);

        SummaryService::markVoidedDocuments($operation->fresh(), 'accepted');

        $this->assertSame('voided', $invoice->fresh()->sunat_status);
    }

    public function test_una_boleta_ligada_deja_de_estar_pendiente(): void
    {
        $company = $this->company();
        $linked = $this->acceptedInvoice('B', $company, 1);
        $pending = $this->acceptedInvoice('B', $company, 2);
        $operation = $this->unlinkedOperation($linked, SunatOperation::TYPE_SUMMARY, false);

        SummaryService::linkInvoices($operation, [$linked->id]);

        $ids = SummaryService::pendingFor($company)->pluck('id');

        $this->assertFalse($ids->contains($linked->id));
        $this->assertTrue($ids->contains($pending->id));
    }

    public function test_un_resumen_diario_normal_no_anula_sus_boletas(): void
    {
        $invoice = $this->acceptedInvoice('B');
        $operation = $this->operation($invoice, SunatOperation::TYPE_SUMMARY, false);

        SummaryService::markVoidedDocuments($operation, 'accepted');

        $this->assertSame('accepted', $invoice->fresh()->sunat_status);
    }

    public function test_una_baja_rechazada_no_marca_el_comprobante(): void
    {
        $invoice = $this->acceptedInvoice('F');
        $operation = $this->operation($invoice, SunatOperation::TYPE_VOIDED, true);

        SummaryService::markVoidedDocuments($operation, 'rejected');

        $this->assertSame('accepted', $invoice->fresh()->sunat_status);
    }

    public function test_un_ticket_99_lee_el_cdr_y_registra_el_rechazo(): void
    {
        $invoice = $this->acceptedInvoice('B');
        $operation = $this->operation($invoice, SunatOperation::TYPE_SUMMARY, true);

        $status = (new StatusResult)
            ->setCode('99')
            ->setSuccess(true)
            ->setCdrResponse((new CdrResponse)
                ->setCode('2671')
                ->setDescription('La fecha de generación del resumen debe ser mayor o igual a la fecha de emisión de los documentos'));

        SummaryService::applyStatus($operation, $status);

        $operation = $operation->fresh();

        $this->assertSame(ResponseMapper::REJECTED, $operation->status);
        $this->assertSame('2671', $operation->code);
        $this->assertStringContainsString('fecha de generación', (string) $operation->description);
        $this->assertNotNull($operation->resolved_at);
        $this->assertSame('accepted', $invoice->fresh()->sunat_status);
    }

    public function test_un_ticket_98_sigue_en_proceso_y_no_anula_nada(): void
    {
        $invoice = $this->acceptedInvoice('B');
        $operation = $this->operation($invoice, SunatOperation::TYPE_SUMMARY, true);

        $status = (new StatusResult)
            ->setCode('98')
            ->setError(new Error('98', 'El procesamiento del comprobante aún no ha terminado'));

        SummaryService::applyStatus($operation, $status);

        $operation = $operation->fresh();

        $this->assertSame('processing', $operation->status);
        $this->assertSame('98', $operation->code);
        $this->assertNull($operation->resolved_at);
        $this->assertSame('accepted', $invoice->fresh()->sunat_status);
    }

    public function test_un_cdr_aceptado_anula_la_boleta(): void
    {
        $invoice = $this->acceptedInvoice('B');
        $operation = $this->operation($invoice, SunatOperation::TYPE_SUMMARY, true);

        $status = (new StatusResult)
            ->setCode('0')
            ->setSuccess(true)
            ->setCdrResponse((new CdrResponse)
                ->setCode('0')
                ->setDescription('El comprobante ha sido aceptado'));

        SummaryService::applyStatus($operation, $status);

        $this->assertSame(ResponseMapper::ACCEPTED, $operation->fresh()->status);
        $this->assertSame('voided', $invoice->fresh()->sunat_status);
    }

    private function operation(Invoice $invoice, string $type, bool $voids): SunatOperation
    {
        $operation = $this->unlinkedOperation($invoice, $type, $voids);

        SummaryService::linkInvoices($operation, [$invoice->id]);

        return $operation;
    }

    private function unlinkedOperation(Invoice $invoice, string $type, bool $voids): SunatOperation
    {
        return SunatOperation::create([
            'company_id' => $invoice->company_id,
            'type' => $type,
            'reference' => $type.'-20261003-1',
            'reference_date' => now()->toDateString(),
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'voids_documents' => $voids,
            'documents_count' => 1,
        ]);
    }

    private function company(): Company
    {
        $this->rucSuffix++;

        return Company::create([
            'ruc' => (string) $this->rucSuffix,
            'business_name' => 'DENTAMOR COMPANY CLINICA ODONTOLOGICA SOCIEDAD ANONIMA CERRADA',
            'sunat_username' => '72901015',
            'sunat_password' => 'clavePrincipal',
            'sunat_ubigeo' => '150101',
            'sunat_department' => 'LIMA',
            'sunat_province' => 'LIMA',
            'sunat_district' => 'LIMA',
            'address' => 'AV. SIEMPRE VIVA 123',
            'production' => true,
        ]);
    }

    private function acceptedInvoice(string $type, ?Company $company = null, int $number = 1): Invoice
    {
        $company ??= $this->company();

        return Invoice::create([
            'company_id' => $company->id,
            'invoice_type' => $type,
            'serie' => $type === 'F' ? 'F002' : 'B002',
            'number' => $number,
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
