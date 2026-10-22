<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Services\Sunat\SummaryService;
use Carbon\Carbon;
use Greenter\Xml\Builder\SummaryBuilder;
use Tests\TestCase;

class SunatSummaryDatesTest extends TestCase
{
    public function test_el_resumen_referencia_la_fecha_de_las_boletas_y_se_genera_hoy(): void
    {
        $company = $this->company();
        $invoice = $this->invoice();

        $generatedAt = Carbon::parse('2026-10-22 10:05:00');

        $summary = SummaryService::buildVoidedSummary($company, $invoice, '1', $generatedAt);
        $xml = (string) (new SummaryBuilder)->build($summary);

        $this->assertStringContainsString('<cbc:ReferenceDate>2026-10-21</cbc:ReferenceDate>', $xml);
        $this->assertStringContainsString('<cbc:IssueDate>2026-10-22</cbc:IssueDate>', $xml);
        $this->assertStringContainsString('<cbc:ID>RC-20261022-1</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:ID>B002-6</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:ConditionCode>3</cbc:ConditionCode>', $xml);
    }

    public function test_el_resumen_diario_tambien_separa_la_fecha_de_los_documentos(): void
    {
        $company = $this->company();
        $invoice = $this->invoice();

        $generatedAt = Carbon::parse('2026-10-22 10:05:00');

        $summary = SummaryService::buildSummary(
            $company,
            collect([$invoice]),
            Carbon::parse('2026-10-21'),
            '2',
            $generatedAt
        );
        $xml = (string) (new SummaryBuilder)->build($summary);

        $this->assertStringContainsString('<cbc:ReferenceDate>2026-10-21</cbc:ReferenceDate>', $xml);
        $this->assertStringContainsString('<cbc:IssueDate>2026-10-22</cbc:IssueDate>', $xml);
        $this->assertStringContainsString('<cbc:ID>RC-20261022-2</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:ConditionCode>1</cbc:ConditionCode>', $xml);
    }

    private function company(): Company
    {
        return new Company([
            'ruc' => '20609937531',
            'business_name' => 'DENTAMOR COMPANY CLINICA ODONTOLOGICA SOCIEDAD ANONIMA CERRADA',
            'sunat_ubigeo' => '150101',
            'sunat_department' => 'LIMA',
            'sunat_province' => 'LIMA',
            'sunat_district' => 'LIMA',
            'address' => 'AV. SIEMPRE VIVA 123',
        ]);
    }

    private function invoice(): Invoice
    {
        $invoice = new Invoice([
            'invoice_type' => 'B',
            'serie' => 'B002',
            'number' => 6,
            'issue_date' => '2026-10-21',
            'currency' => 'PEN',
            'subtotal' => 84.75,
            'igv' => 15.25,
            'total' => 100,
        ]);

        $invoice->setRelation('client', null);

        return $invoice;
    }
}
