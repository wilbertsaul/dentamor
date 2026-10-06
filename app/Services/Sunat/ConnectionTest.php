<?php

namespace App\Services\Sunat;

use App\Models\Company;

class ConnectionTest
{
    /**
     * @param  array{sunat_username?: string|null, sunat_password?: string|null, sunat_username_secondary?: string|null, sunat_password_secondary?: string|null}  $credentials
     * @return array{ok: bool, message: string, errors: array<int, string>, warnings: array<int, string>, details: array<int, string>}
     */
    public static function run(Company $company, ?string $certificate = null, array $credentials = []): array
    {
        $errors = [];
        $warnings = [];
        $details = [];
        $environment = $company->environment();

        $username = $credentials['sunat_username'] ?? $company->sunat_username;
        $password = $credentials['sunat_password'] ?? $company->sunat_password;
        $usernameSecondary = $credentials['sunat_username_secondary'] ?? $company->sunat_username_secondary;
        $passwordSecondary = $credentials['sunat_password_secondary'] ?? $company->sunat_password_secondary;

        if (strlen((string) $company->ruc) !== 11) {
            $errors[] = 'Falta configurar el RUC de la empresa.';

            return static::result($errors, $warnings, $details, $environment);
        }

        if (blank($username) || blank($password)) {
            $errors[] = 'Faltan las credenciales SOL (usuario y contraseña).';
        }

        if (blank($usernameSecondary) || blank($passwordSecondary)) {
            $warnings[] = $company->production
                ? 'No hay usuario SOL secundario: el resumen diario y la comunicación de baja no podrán enviarse.'
                : 'No hay usuario SOL secundario: en beta el resumen y la baja saldrán con el usuario principal.';
        } elseif ($username === $usernameSecondary) {
            $warnings[] = 'El usuario SOL secundario es igual al principal: SUNAT no lo acepta para el resumen diario ni la baja.';
        }

        if (! CertificateService::openSslReady()) {
            $warnings[] = 'PHP no tiene OpenSSL configurado: no se podrán convertir certificados .p12/.pfx. Define OPENSSL_CONF y reinicia Apache.';
        }

        if (! $company->hasAddress()) {
            $errors[] = 'Falta el domicilio fiscal del emisor (ubigeo, departamento, provincia, distrito y dirección). SUNAT rechazará los comprobantes.';
        } elseif (preg_match('/^\d{6}$/', (string) $company->sunat_ubigeo) !== 1) {
            $errors[] = 'El ubigeo debe tener 6 dígitos.';
        }

        $details[] = 'Ambiente: '.($company->production ? 'PRODUCCIÓN' : 'beta (homologación)').'.';
        $details[] = 'Establecimiento: '.$company->establishmentCode().'.';

        $certificate ??= CertificateService::contentFor($company);

        if (blank($certificate)) {
            $errors[] = 'No hay certificado digital cargado.';

            return static::result($errors, $warnings, $details, $environment);
        }

        try {
            $info = CertificateService::inspect($certificate);
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();

            return static::result($errors, $warnings, $details, $environment);
        }

        if (! $info['has_private_key']) {
            $errors[] = 'El certificado cargado no incluye la clave privada.';
        }

        $details[] = 'Titular: '.($info['subject'] !== '' ? $info['subject'] : 'no informado').'.';

        if ($info['subject'] !== '' && ! str_contains(preg_replace('/\s+/', '', $info['subject']) ?? '', (string) $company->ruc)) {
            $warnings[] = 'El titular del certificado no menciona el RUC '.$company->ruc.'; SUNAT exige que el certificado esté emitido para ese RUC.';
        }

        if ($info['valid_from'] && $info['valid_to']) {
            $details[] = 'Vigencia: '.date('d/m/Y', $info['valid_from']).' al '.date('d/m/Y', $info['valid_to']).'.';

            if (time() < $info['valid_from']) {
                $errors[] = 'El certificado aún no entra en vigencia.';
            } elseif (time() > $info['valid_to']) {
                $errors[] = 'El certificado venció el '.date('d/m/Y H:i', $info['valid_to']).'; SUNAT lo rechazará.';
            } elseif ($info['valid_to'] - time() < 2592000) {
                $warnings[] = 'El certificado vence en menos de 30 días.';
            }
        }

        if (! $company->production) {
            $details[] = 'En beta los comprobantes no tienen validez tributaria y solo sirven para pruebas.';

            return static::result($errors, $warnings, $details, $environment);
        }

        $details[] = 'En producción la conectividad se valida al emitir el primer comprobante: '
            .'el sondeo getStatus de SUNAT no es concluyente sin un ticket real de un resumen enviado.';

        return static::result($errors, $warnings, $details, $environment);
    }

    private static function result(array $errors, array $warnings, array $details, string $environment): array
    {
        return [
            'ok' => $errors === [],
            'message' => $errors === []
                ? 'Configuración lista para emitir en '.$environment.'.'
                : 'La configuración tiene '.count($errors).' problema(s) que corregir.',
            'errors' => $errors,
            'warnings' => $warnings,
            'details' => $details,
        ];
    }
}
