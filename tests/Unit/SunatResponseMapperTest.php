<?php

namespace Tests\Unit;

use App\Services\Sunat\ResponseMapper;
use PHPUnit\Framework\TestCase;

class SunatResponseMapperTest extends TestCase
{
    public function test_codigo_cero_es_aceptado(): void
    {
        $this->assertSame(ResponseMapper::ACCEPTED, ResponseMapper::status('0'));
    }

    public function test_codigo_uno_es_aceptado_con_observaciones(): void
    {
        $this->assertSame(ResponseMapper::ACCEPTED_WITH_OBSERVATIONS, ResponseMapper::status('1'));
    }

    public function test_validaciones_4000_no_invalidan_el_comprobante(): void
    {
        $this->assertSame(ResponseMapper::ACCEPTED_WITH_OBSERVATIONS, ResponseMapper::status('4000'));
        $this->assertSame(ResponseMapper::ACCEPTED_WITH_OBSERVATIONS, ResponseMapper::status('4330'));
    }

    public function test_errores_de_sistema_no_son_validos(): void
    {
        $this->assertSame(ResponseMapper::ERROR, ResponseMapper::status('98'));
        $this->assertSame(ResponseMapper::ERROR, ResponseMapper::status('99'));
    }

    public function test_cualquier_otro_codigo_es_rechazo(): void
    {
        $this->assertSame(ResponseMapper::REJECTED, ResponseMapper::status('2'));
        $this->assertSame(ResponseMapper::REJECTED, ResponseMapper::status('2335'));
    }

    public function test_respuesta_vacia_se_trata_como_error(): void
    {
        $this->assertSame(ResponseMapper::ERROR, ResponseMapper::status(null));
        $this->assertSame(ResponseMapper::ERROR, ResponseMapper::status(''));
    }

    public function test_solo_los_estados_validos_tienen_validez_tributaria(): void
    {
        $this->assertTrue(ResponseMapper::isValid(ResponseMapper::ACCEPTED));
        $this->assertTrue(ResponseMapper::isValid(ResponseMapper::ACCEPTED_WITH_OBSERVATIONS));
        $this->assertFalse(ResponseMapper::isValid(ResponseMapper::REJECTED));
        $this->assertFalse(ResponseMapper::isValid(ResponseMapper::ERROR));
        $this->assertFalse(ResponseMapper::isValid(null));
    }
}
