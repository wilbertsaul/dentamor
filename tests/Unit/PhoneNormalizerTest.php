<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\WhatsApp\PhoneNormalizer;
use Tests\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_normaliza_un_movil_peruano_de_nueve_digitos(): void
    {
        $this->assertSame('+51987654321', PhoneNormalizer::normalize('987654321'));
        $this->assertSame('51987654321', PhoneNormalizer::toApiNumber('987 654 321'));
    }

    public function test_respeta_el_codigo_de_pais_y_limpia_simbolos(): void
    {
        $this->assertSame('+51987654321', PhoneNormalizer::normalize('+51 987 654 321'));
        $this->assertSame('+51987654321', PhoneNormalizer::normalize('(51) 987654321'));
    }

    public function test_quita_el_prefijo_internacional_00(): void
    {
        $this->assertSame('+51987654321', PhoneNormalizer::normalize('0051987654321'));
    }

    public function test_respeta_numeros_internacionales_con_mas(): void
    {
        $this->assertSame('+15551234567', PhoneNormalizer::normalize('+1 555 123 4567'));
    }

    public function test_quita_el_cero_de_marcacion_local(): void
    {
        $this->assertSame('+51987654321', PhoneNormalizer::normalize('0987654321'));
        $this->assertSame('+5114567890', PhoneNormalizer::normalize('01 456 7890'));
    }

    public function test_rechaza_numeros_vacios_o_demasiado_cortos(): void
    {
        $this->assertNull(PhoneNormalizer::normalize(null));
        $this->assertNull(PhoneNormalizer::normalize(''));
        $this->assertNull(PhoneNormalizer::normalize('123'));
        $this->assertNull(PhoneNormalizer::toApiNumber('abc'));
    }
}
