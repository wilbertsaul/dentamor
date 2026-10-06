<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSSLAsymmetricKey;
use RuntimeException;

class CertificateService
{
    public const DISK = 'local';

    public const DIRECTORY = 'certificates';

    /**
     * Se valida la extensión real del archivo, no el tipo MIME: un PEM se
     * reporta como text/plain y la regla "mimes" lo rechazaría.
     *
     * @var list<string>
     */
    public const EXTENSIONS = ['pem', 'crt', 'cer', 'pfx', 'p12'];

    private static ?bool $openSslReady = null;

    /**
     * Sin un openssl.cnf alcanzable, PHP lee certificados pero no genera ni
     * exporta claves privadas: los .p12/.pfx y los PEM cifrados fallan.
     */
    public static function openSslReady(): bool
    {
        if (static::$openSslReady !== null) {
            return static::$openSslReady;
        }

        $key = @openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $exported = '';

        static::$openSslReady = $key !== false && @openssl_pkey_export($key, $exported);

        if (! static::$openSslReady) {
            static::drainOpenSslErrors();
        }

        return static::$openSslReady;
    }

    public static function assertOpenSslReady(): void
    {
        if (static::openSslReady()) {
            return;
        }

        throw new RuntimeException(
            'PHP no tiene OpenSSL configurado, por lo que no se puede convertir un certificado .p12/.pfx '
            .'ni descifrar un PEM. Define la variable de entorno OPENSSL_CONF con la ruta al openssl.cnf '
            .'de tu PHP o al config/openssl.cnf del proyecto, y reinicia Apache.'
        );
    }

    private static function exportPrivateKey(OpenSSLAsymmetricKey|string $key): string
    {
        $exported = '';

        if (@openssl_pkey_export($key, $exported)) {
            return $exported;
        }

        static::drainOpenSslErrors();
        static::assertOpenSslReady();

        throw new RuntimeException('No se pudo exportar la clave privada del archivo PKCS12.');
    }

    private static function drainOpenSslErrors(): void
    {
        static::openSslErrors();
    }

    /**
     * @return array<int, string>
     */
    private static function openSslErrors(): array
    {
        $errors = [];

        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return $errors;
    }

    /**
     * OpenSSL 3 rechaza RC2/3DES si no se cargó el proveedor "legacy", que es
     * justo como vienen cifrados los .p12 de SUNAT y de las entidades
     * certificadoras.
     *
     * @param  array<int, string>  $errors
     */
    private static function missingLegacyProvider(array $errors): bool
    {
        foreach ($errors as $error) {
            if (str_contains(strtolower($error), 'unsupported')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza el certificado y lo guarda. Si la empresa ya tenía un certificado
     * en otra ruta, se sobrescribe ese mismo archivo para que certificate_path y
     * el disco nunca diverjan.
     */
    public static function store(UploadedFile $file, ?string $password = null, ?string $currentPath = null): string
    {
        $password = trim((string) $password);

        $pem = static::normalize($file->get(), $password);
        $info = static::inspect($pem);

        if (! $info['has_private_key']) {
            throw new RuntimeException('El archivo no contiene la clave privada necesaria para firmar.');
        }

        $path = static::resolvePath($currentPath);
        Storage::disk(static::DISK)->put($path, $pem);

        return $path;
    }

    /**
     * Ruta final del certificado: reutiliza la existente si es válida y segura.
     */
    public static function resolvePath(?string $currentPath): string
    {
        $currentPath = trim((string) $currentPath);

        if ($currentPath === '' || ! static::isSafeRelativePath($currentPath)) {
            return static::DIRECTORY.'/'.Str::random(40).'.pem';
        }

        return $currentPath;
    }

    /**
     * Evita escribir fuera del disco local (rutas absolutas o con "..").
     */
    private static function isSafeRelativePath(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:/', $path) === 1) {
            return false;
        }

        return ! in_array('..', preg_split('#[\\\\/]#', $path) ?: [], true);
    }

    public static function contentFor(Company $company): ?string
    {
        if (blank($company->certificate_path)) {
            return null;
        }

        if (! Storage::disk(static::DISK)->exists($company->certificate_path)) {
            return null;
        }

        $content = Storage::disk(static::DISK)->get($company->certificate_path);

        return filled($content) ? $content : null;
    }

    public static function inspect(string $content): array
    {
        $certificate = static::extractCertificate($content);

        if ($certificate === null) {
            throw new RuntimeException('No se encontró un certificado X.509 en el archivo.');
        }

        $x509 = @openssl_x509_read($certificate);

        if ($x509 === false) {
            throw new RuntimeException('El certificado X.509 no se pudo leer; el archivo puede estar dañado.');
        }

        $data = @openssl_x509_parse($x509);

        return [
            'subject' => trim((string) ($data['name'] ?? '')),
            'serial' => (string) ($data['serialNumberHex'] ?? $data['serialNumber'] ?? ''),
            'valid_from' => $data['validFrom_time_t'] ?? null,
            'valid_to' => $data['validTo_time_t'] ?? null,
            'has_private_key' => static::hasPrivateKey($content),
        ];
    }

    public static function normalize(string $content, string $password = ''): string
    {
        if ($content === '') {
            throw new RuntimeException('El archivo del certificado está vacío.');
        }

        // Un .p12/.pfx es binario (DER): normalizar el texto lo corrompe, porque
        // trim() y el reemplazo de CR alteran bytes del archivo. Se procesa tal cual.
        if (static::isPkcs12($content) || static::looksBinary($content)) {
            return static::fromPkcs12($content, $password);
        }

        $content = str_replace(["\r\n", "\r"], "\n", trim($content));

        if (static::hasPrivateKey($content) && static::extractCertificate($content) !== null) {
            return static::hasEncryptedPrivateKey($content)
                ? static::decryptPrivateKey($content, $password)
                : static::canonicalize($content);
        }

        if (static::extractCertificate($content) !== null) {
            throw new RuntimeException('El archivo contiene solo el certificado; falta la clave privada para firmar.');
        }

        throw new RuntimeException('El archivo no es un certificado PEM ni un PKCS12 (.pfx/.p12) legible.');
    }

    public static function extractCertificate(string $content): ?string
    {
        if (preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $content, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    private static function hasPrivateKey(string $content): bool
    {
        return preg_match('/-----BEGIN (RSA |EC |ENCRYPTED )?PRIVATE KEY-----/s', $content) === 1;
    }

    private static function hasEncryptedPrivateKey(string $content): bool
    {
        return str_contains($content, '-----BEGIN ENCRYPTED PRIVATE KEY-----')
            || preg_match('/Proc-Type:\s*4,\s*ENCRYPTED/i', $content) === 1;
    }

    private static function isPkcs12(string $content): bool
    {
        return str_contains($content, '-----BEGIN PKCS12-----')
            || str_contains($content, '-----BEGIN PFX-----');
    }

    private static function looksBinary(string $content): bool
    {
        return ! mb_check_encoding($content, 'UTF-8')
            || str_contains($content, "\0")
            || preg_match('%[\x00-\x08\x0E-\x1F]%', $content) === 1;
    }

    private static function fromPkcs12(string $content, string $password): string
    {
        $payload = $content;

        if (static::isPkcs12($content)) {
            $payload = (string) preg_replace('/-----(BEGIN|END)[A-Z0-9 ]*-----|\s+/', '', $content);
        }

        $result = [];

        if (! @openssl_pkcs12_read($payload, $result, $password)) {
            $errors = static::openSslErrors();
            static::assertOpenSslReady();

            if (static::missingLegacyProvider($errors)) {
                throw new RuntimeException(
                    'El archivo .p12 está cifrado con RC2/3DES y PHP no tiene el proveedor legacy de OpenSSL. '
                    .'Define OPENSSL_MODULES con la carpeta que contiene legacy.dll '
                    .'(en Laragon: <laragon>\bin\php\php-X.Y.Z-Win32-vs16-x64\extras\ssl) y reinicia Apache.'
                );
            }

            throw new RuntimeException('No se pudo abrir el archivo PKCS12 (.pfx/.p12). Verifique la contraseña.');
        }

        if (empty($result['pkey']) || empty($result['cert'])) {
            throw new RuntimeException('El archivo PKCS12 no contiene el certificado y su clave privada.');
        }

        $key = (string) $result['pkey'];

        if (! str_contains($key, 'PRIVATE KEY-----')) {
            $key = static::exportPrivateKey($key);
        }

        return static::canonicalize(
            static::firstPem((string) $result['cert'])."\n".$key."\n".static::chainPem($result['chain'] ?? [])
        );
    }

    private static function decryptPrivateKey(string $content, string $password): string
    {
        $key = $password === '' ? @openssl_pkey_get_private($content) : @openssl_pkey_get_private($content, $password);

        if ($key === false) {
            static::drainOpenSslErrors();
            static::assertOpenSslReady();

            throw new RuntimeException('La clave privada está cifrada: indica la contraseña del certificado para convertirlo.');
        }

        return static::canonicalize(
            static::firstPem(static::extractCertificate($content) ?? '')."\n".static::exportPrivateKey($key)
        );
    }

    private static function canonicalize(string $pem): string
    {
        $certificates = [];

        if (preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem, $matches) > 0) {
            $certificates = $matches[0];
        }

        $key = '';

        if (preg_match('/-----BEGIN (?:RSA |EC |ENCRYPTED )?PRIVATE KEY-----.*?-----END (?:RSA |EC |ENCRYPTED )?PRIVATE KEY-----/s', $pem, $matches) === 1) {
            $key = trim($matches[0]);
        }

        return trim(implode("\n", $certificates)."\n".$key)."\n";
    }

    private static function firstPem(string $pem): string
    {
        return static::extractCertificate($pem) ?? '';
    }

    private static function chainPem(mixed $chain): string
    {
        if (! is_array($chain)) {
            return '';
        }

        $out = '';

        foreach ($chain as $item) {
            if (! is_string($item)) {
                continue;
            }

            $out .= static::firstPem($item)."\n";
        }

        return $out;
    }
}
