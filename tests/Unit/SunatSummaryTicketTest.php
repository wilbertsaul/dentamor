<?php

namespace Tests\Unit;

use App\Services\Sunat\SummaryService;
use PHPUnit\Framework\TestCase;

class SunatSummaryTicketTest extends TestCase
{
    public function test_el_codigo_pendiente_con_ceros_a_la_izquierda_se_reconoce(): void
    {
        $this->assertTrue(SummaryService::isPendingTicketCode('0098'));
        $this->assertFalse(SummaryService::isPendingTicketCode('0099'));
    }

    public function test_el_codigo_pendiente_sin_ceros_se_reconoce(): void
    {
        $this->assertTrue(SummaryService::isPendingTicketCode('98'));
        $this->assertFalse(SummaryService::isPendingTicketCode('99'));
    }

    public function test_los_codigos_resueltos_no_son_pendientes(): void
    {
        $this->assertFalse(SummaryService::isPendingTicketCode('0'));
        $this->assertFalse(SummaryService::isPendingTicketCode('2663'));
        $this->assertFalse(SummaryService::isPendingTicketCode(''));
        $this->assertFalse(SummaryService::isPendingTicketCode(null));
    }
}
