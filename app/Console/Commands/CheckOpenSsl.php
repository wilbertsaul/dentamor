<?php

namespace App\Console\Commands;

use App\Services\Sunat\CertificateService;
use Illuminate\Console\Command;

class CheckOpenSsl extends Command
{
    protected $signature = 'sunat:openssl';

    protected $description = 'Diagnostica si PHP puede convertir certificados .p12/.pfx y PEM cifrados';

    public function handle(): int
    {
        $this->components->info('Diagnóstico de OpenSSL para certificados SUNAT');

        $config = (string) (getenv('OPENSSL_CONF') ?: '');
        $modules = (string) (getenv('OPENSSL_MODULES') ?: '');

        $this->line('PHP '.PHP_VERSION.' · OpenSSL '.OPENSSL_VERSION_TEXT);

        $this->check('OPENSSL_CONF', $config, match (true) {
            $config === '' => 'no definida: PHP no encuentra openssl.cnf y no puede exportar claves privadas',
            ! is_file($config) => 'la ruta no existe: '.$config,
            default => 'ok',
        });

        $this->check('OPENSSL_MODULES', $modules, match (true) {
            $modules === '' => 'no definida: faltará el proveedor legacy para leer .p12 con RC2/3DES',
            ! is_dir($modules) => 'la carpeta no existe: '.$modules,
            ! is_file($modules.'/legacy.dll') && ! is_file($modules.'/legacy.so') => 'no se encuentra legacy.dll en '.$modules,
            default => 'ok',
        });

        $ready = CertificateService::openSslReady();
        $this->check('generar y exportar claves', $ready ? 'ok' : 'FALLA', $ready
            ? 'ok'
            : 'OpenSSL no puede operar; revisa OPENSSL_CONF y reinicia el servidor web');

        if ($ready) {
            $this->components->twoColumnDetail('Convertir .p12/.pfx y PEM cifrados', '<fg=green>disponible</>');
            $this->components->twoColumnDetail('Certificados .pem sin cifrar', '<fg=green>disponible</>');
        }

        if (! $ready) {
            $this->newLine();
            $this->components->error('No se pueden subir certificados. Reinicia el servidor web tras cambiar las variables.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Si un .p12 sigue sin abrirse, el error indica si falta el proveedor legacy.');

        return self::SUCCESS;
    }

    private function check(string $label, string $value, string $state): void
    {
        $style = $state === 'ok' ? 'fg=green' : 'fg=yellow';

        $this->components->twoColumnDetail($label, "<$style>$state</>");

        if ($state !== 'ok') {
            $this->line('    <fg=gray>'.$value.'</>');
        }
    }
}
