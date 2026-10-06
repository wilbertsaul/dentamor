<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Sunat\CertificateService;
use RuntimeException;
use Tests\TestCase;

/**
 * Cubre la conversión de certificados .p12/.pfx, que es el formato con el que
 * SUNAT entrega el CDT.
 *
 * El fixture es un certificado autofirmado descartable, cifrado con RC2/3DES
 * como los reales, para cubrir también el proveedor legacy de OpenSSL. No sirve
 * para emitir nada.
 */
class CertificatePkcs12Test extends TestCase
{
    private const PASSWORD = 'ClavePrueba123';

    private const FIXTURE = __DIR__.'/../Fixtures/certificado-prueba.p12';

    public function test_convierte_un_p12_cifrado_a_pem_con_clave_privada(): void
    {
        $pem = CertificateService::normalize($this->pkcs12(), self::PASSWORD);
        $info = CertificateService::inspect($pem);

        $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $pem);
        $this->assertStringContainsString('PRIVATE KEY-----', $pem);
        $this->assertTrue($info['has_private_key']);
        $this->assertStringContainsString('20600123456', $info['subject']);
    }

    public function test_el_pem_convertido_conserva_la_vigencia_del_certificado(): void
    {
        $info = CertificateService::inspect(CertificateService::normalize($this->pkcs12(), self::PASSWORD));

        $this->assertNotNull($info['valid_to']);
        $this->assertGreaterThan(time(), $info['valid_to']);
    }

    public function test_rechaza_contrasena_incorrecta(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/PKCS12|contraseña|OpenSSL/i');

        CertificateService::normalize($this->pkcs12(), 'otra-clave');
    }

    public function test_rechaza_un_archivo_que_no_es_un_certificado(): void
    {
        $this->expectException(RuntimeException::class);

        CertificateService::normalize('contenido binario sin certificado valido', self::PASSWORD);
    }

    public function test_openssl_esta_configurado_para_convertir_certificados(): void
    {
        $this->assertTrue(
            CertificateService::openSslReady(),
            'Define OPENSSL_CONF apuntando a config/openssl.cnf y reinicia Apache.'
        );
    }

    private function pkcs12(): string
    {
        if (! CertificateService::openSslReady()) {
            $this->markTestSkipped('OpenSSL no disponible: define OPENSSL_CONF para probar archivos PKCS12.');
        }

        if (! is_file(self::FIXTURE)) {
            $this->markTestSkipped(
                'Falta tests/Fixtures/certificado-prueba.p12 (no se versiona por ser una clave privada). '
                .'Genera uno autofirmado con password '.self::PASSWORD.' y cifrado RC2/3DES.'
            );
        }

        return (string) file_get_contents(self::FIXTURE);
    }
}
