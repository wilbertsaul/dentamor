<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Company\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanySettingsSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardar_configuracion_muestra_un_mensaje_de_exito(): void
    {
        Livewire::test(Settings::class)
            ->set('ruc', '20609937500')
            ->set('business_name', 'DENTAMOR CLINICA ODONTOLOGICA SAC')
            ->set('address', 'Av. Los Olivos 123')
            ->set('sunat_ubigeo', '150101')
            ->set('sunat_department', 'LIMA')
            ->set('sunat_province', 'LIMA')
            ->set('sunat_district', 'LIMA')
            ->set('sunat_establishment_code', '0000')
            ->set('sunat_username', '72901015')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Configuración guardada correctamente');
    }

    public function test_sin_llenar_lo_obligatorio_se_muestran_los_errores_sin_mensaje_de_exito(): void
    {
        Livewire::test(Settings::class)
            ->call('save')
            ->assertHasErrors(['ruc', 'business_name', 'address'])
            ->assertDontSee('Configuración guardada correctamente');
    }
}
