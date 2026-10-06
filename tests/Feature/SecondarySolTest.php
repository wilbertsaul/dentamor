<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Services\Sunat\SeeFactory;
use RuntimeException;
use Tests\TestCase;

class SecondarySolTest extends TestCase
{
    public function test_en_produccion_exige_usuario_secundario(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/secundario/i');

        SeeFactory::assertReady($this->company(production: true), true);
    }

    public function test_en_beta_sin_usuario_secundario_usa_el_principal(): void
    {
        $company = $this->company(production: false);

        $this->assertTrue($company->fallsBackToPrimarySol());

        SeeFactory::assertReady($company, true);

        [$username, $password] = SeeFactory::credentials($company, true);

        $this->assertSame('MODDATOS', $username);
        $this->assertSame('clavePrincipal', $password);
    }

    public function test_con_usuario_secundario_lo_usa_siempre(): void
    {
        $company = $this->company(production: true, secondary: true);

        $this->assertFalse($company->fallsBackToPrimarySol());

        [$username, $password] = SeeFactory::credentials($company, true);

        $this->assertSame('MODDATOS2', $username);
        $this->assertSame('claveSecundaria', $password);
    }

    public function test_detecta_el_usuario_secundario_igual_al_principal(): void
    {
        $company = $this->company(production: true, secondary: true, secondaryUser: 'MODDATOS');

        $this->assertTrue($company->secondarySolDuplicatesPrimary());
    }

    private function company(bool $production, bool $secondary = false, ?string $secondaryUser = null): Company
    {
        return new Company([
            'ruc' => '20600123456',
            'sunat_username' => 'MODDATOS',
            'sunat_password' => 'clavePrincipal',
            'sunat_username_secondary' => $secondary ? ($secondaryUser ?? 'MODDATOS2') : null,
            'sunat_password_secondary' => $secondary ? 'claveSecundaria' : null,
            'production' => $production,
            'certificate_path' => 'certificates/demo.pem',
        ]);
    }
}
