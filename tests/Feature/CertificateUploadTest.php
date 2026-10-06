<?php

namespace Tests\Feature;

use App\Services\Sunat\CertificateService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CertificateUploadTest extends TestCase
{
    public function test_acepta_las_extensiones_de_certificado_soportadas(): void
    {
        foreach (['certificado.pem', 'certificado.crt', 'certificado.cer', 'certificado.pfx', 'certificado.p12'] as $name) {
            $validator = validator(
                ['certificate' => UploadedFile::fake()->createWithContent($name, 'contenido')],
                ['certificate' => ['nullable', 'file', 'extensions:'.implode(',', CertificateService::EXTENSIONS), 'max:2048']]
            );

            $this->assertTrue($validator->passes(), "El formulario debe aceptar {$name}");
        }
    }

    public function test_rechaza_archivos_que_no_son_certificados(): void
    {
        $validator = validator(
            ['certificate' => UploadedFile::fake()->createWithContent('certificado.exe', 'contenido')],
            ['certificate' => ['nullable', 'file', 'extensions:'.implode(',', CertificateService::EXTENSIONS)]]
        );

        $this->assertTrue($validator->fails(), 'El formulario no debe aceptar un .exe');
    }

    public function test_conserva_la_ruta_del_certificado_anterior(): void
    {
        $this->assertSame(
            'sunat/certificates/test_cert.pem',
            CertificateService::resolvePath('sunat/certificates/test_cert.pem')
        );
    }

    public function test_sin_ruta_previa_genera_un_archivo_en_el_directorio_de_certificados(): void
    {
        foreach ([null, '', '   '] as $current) {
            $path = CertificateService::resolvePath($current);

            $this->assertStringStartsWith(CertificateService::DIRECTORY.'/', $path);
            $this->assertStringEndsWith('.pem', $path);
        }
    }

    public function test_una_ruta_insegura_o_absoluta_nunca_se_reutiliza(): void
    {
        $unsafe = [
            '../../fuera/del/disco.pem',
            'certificates/../../escape.pem',
            '/ruta/absoluta.pem',
            'C:\windows\cert.pem',
        ];

        foreach ($unsafe as $current) {
            $path = CertificateService::resolvePath($current);

            $this->assertStringStartsWith(CertificateService::DIRECTORY.'/', $path);
            $this->assertStringNotContainsString('..', $path);
        }
    }
}
