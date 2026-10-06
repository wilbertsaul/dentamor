<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use RuntimeException;

class SeeFactory
{
    public static function make(Company $company, bool $secondary = false): See
    {
        static::assertReady($company, $secondary);

        $certificate = CertificateService::contentFor($company);

        if (blank($certificate)) {
            throw new RuntimeException('No se encontró el certificado digital vigente. Súbelo en Configuración → Empresa.');
        }

        [$username, $password] = static::credentials($company, $secondary);

        $see = new See;
        $see->setCertificate($certificate);
        $see->setClaveSOL($company->ruc, $username, $password);
        $see->setCachePath(storage_path('app/sunat/cache'));
        $see->setService($company->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);

        return $see;
    }

    /**
     * En beta se permite enviar el resumen y la baja con el usuario principal si
     * aún no hay un usuario secundario configurado. En producción nunca.
     *
     * @return array{0: string, 1: string}
     */
    public static function credentials(Company $company, bool $secondary = false): array
    {
        if ($secondary && ! $company->hasSecondarySol() && ! $company->production) {
            SunatLogger::warning(
                'Se envía con el usuario SOL principal: no hay usuario secundario configurado (solo permitido en beta).',
                ['ruc' => $company->ruc]
            );

            return [(string) $company->sunat_username, (string) $company->sunat_password];
        }

        return $secondary
            ? [(string) $company->sunat_username_secondary, (string) $company->sunat_password_secondary]
            : [(string) $company->sunat_username, (string) $company->sunat_password];
    }

    public static function assertReady(Company $company, bool $secondary = false): void
    {
        if (strlen((string) $company->ruc) !== 11) {
            throw new RuntimeException('Falta configurar el RUC de la empresa. Vaya a Configuración → Empresa.');
        }

        if ($secondary) {
            if (! $company->hasSecondarySol() && $company->production) {
                throw new RuntimeException('Esta operación requiere un usuario SOL secundario. Configúrelo en Configuración → Empresa.');
            }

            if (! $company->hasPrimarySol()) {
                throw new RuntimeException('Faltan las credenciales SOL (usuario/contraseña). Configúrelas en Configuración → Empresa.');
            }
        } elseif (! $company->hasPrimarySol()) {
            throw new RuntimeException('Faltan las credenciales SOL (usuario/contraseña). Configúrelas en Configuración → Empresa.');
        }

        if (blank($company->certificate_path)) {
            throw new RuntimeException('Falta el certificado digital. Súbalo en Configuración → Empresa.');
        }
    }

    public static function assertAddress(Company $company): void
    {
        if (! $company->hasAddress()) {
            throw new RuntimeException(
                'Falta el domicilio fiscal del emisor (ubigeo, departamento, provincia, distrito y dirección). '
                .'SUNAT rechaza el comprobante si no coincide con la ficha RUC. Vaya a Configuración → Empresa.'
            );
        }

        if (preg_match('/^\d{6}$/', (string) $company->sunat_ubigeo) !== 1) {
            throw new RuntimeException('El ubigeo del domicilio fiscal debe tener 6 dígitos.');
        }
    }
}
