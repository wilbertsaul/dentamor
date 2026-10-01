<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Models\Company;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public $company;
    public $ruc;
    public $business_name;
    public $commercial_name;
    public $address;
    public $sunat_username;
    public $sunat_password;
    public $certificate;
    public $production = false;
    public $sunatTest = null;

    public function mount()
    {
        $this->company = Company::first();
        if ($this->company) {
            $this->ruc = $this->company->ruc;
            $this->business_name = $this->company->business_name;
            $this->commercial_name = $this->company->commercial_name;
            $this->address = $this->company->address;
            $this->sunat_username = $this->company->sunat_username;
            $this->sunat_password = $this->company->sunat_password;
            $this->production = $this->company->production ?? false;
        }
    }

    public function save()
    {
        $this->validate([
            'ruc' => 'required|size:11',
            'business_name' => 'required',
            'commercial_name' => 'nullable',
            'address' => 'nullable',
            'sunat_username' => 'nullable',
            'sunat_password' => 'nullable',
            'certificate' => 'nullable|file|mimes:pem,crt,cer|max:2048',
            'production' => 'boolean',
        ]);

        $data = [
            'ruc' => $this->ruc,
            'business_name' => $this->business_name,
            'commercial_name' => $this->commercial_name,
            'address' => $this->address,
            'sunat_username' => $this->sunat_username,
            'sunat_password' => $this->sunat_password,
            'production' => $this->production,
        ];

        if ($this->certificate) {
            $data['certificate_path'] = $this->certificate->store('certificates', 'local');
        }

        if ($this->company) {
            $this->company->update($data);
        } else {
            $this->company = Company::create($data);
        }

        session()->flash('message', 'Configuración guardada correctamente.');
        $this->sunatTest = null;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['ruc', 'sunat_username', 'sunat_password', 'production', 'certificate'], true)) {
            $this->sunatTest = null;
        }
    }

    public function testSunat()
    {
        $this->sunatTest = null;

        $this->validate([
            'ruc' => 'required|size:11',
            'sunat_username' => 'required',
            'sunat_password' => 'required',
        ]);

        $environment = $this->production ? 'producción' : 'beta (homologación)';
        $errors = [];
        $warnings = [];
        $details = [];

        try {
            $certificate = $this->resolveCertificate();

            if (!$certificate) {
                $this->sunatTest = [
                    'ok' => false,
                    'message' => 'Falta el certificado digital.',
                    'errors' => ['Sube el archivo .pem en la sección "Certificado Digital".'],
                    'warnings' => [],
                    'details' => [],
                ];
                return;
            }

            $pem = $this->extractPem($certificate);
            $x509 = $pem ? @openssl_x509_read($pem) : false;

            if (!$x509) {
                $errors[] = 'El archivo no contiene un certificado X.509 legible.';
            } else {
                $data = openssl_x509_parse($x509);
                $from = $data['validFrom_time_t'] ?? null;
                $to = $data['validTo_time_t'] ?? null;

                if ($from && $to) {
                    $details[] = 'Certificado vigente del ' . date('d/m/Y', $from) . ' al ' . date('d/m/Y', $to) . '.';

                    if (time() < $from) {
                        $errors[] = 'El certificado todavía no entra en vigencia (inicia el ' . date('d/m/Y H:i', $from) . ').';
                    } elseif (time() > $to) {
                        $errors[] = 'El certificado venció el ' . date('d/m/Y H:i', $to) . '; SUNAT lo rechazará.';
                    } elseif ($to - time() < 2592000) {
                        $warnings[] = 'El certificado vence en menos de 30 días.';
                    }
                }

                $holder = trim($data['name'] ?? '');
                $details[] = 'Titular: ' . ($holder !== '' ? $holder : 'no informado') . '.';

                $subject = preg_replace('/\s+/', '', $holder . ' ' . ($data['subjectAltName'] ?? ''));
                if ($subject !== '' && !str_contains($subject, $this->ruc)) {
                    $warnings[] = 'El titular del certificado no menciona el RUC ' . $this->ruc . '; SUNAT exige que el certificado esté emitido para ese RUC.';
                }
            }

            try {
                $see = new See();
                $see->setCertificate($certificate);
                $see->setClaveSOL($this->ruc, $this->sunat_username, $this->sunat_password);
                $see->setCachePath(storage_path('app/sunat/cache'));
                $see->setService($this->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);

                $status = $see->getStatus(null);

                if ($status->isSuccess()) {
                    $details[] = 'SUNAT respondió afirmativamente (' . $environment . ').';
                } else {
                    $error = $status->getError();
                    $code = (string) ($error?->getCode() ?? '');

                    if ($code === '0127') {
                        $details[] = 'SUNAT recibió y firmó correctamente la solicitud (' . $environment . '). Nota: este chequeo confirma el certificado y la conectividad, no la validez de la clave SOL.';
                    } else {
                        $message = trim(($code ? '[' . $code . '] ' : '') . ($error?->getMessage() ?? ''));
                        $errors[] = $message !== ''
                            ? 'SUNAT respondió con error: ' . $message
                            : 'SUNAT no respondió correctamente al saludo de prueba.';
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = 'No se pudo completar la conexión con SUNAT: ' . $e->getMessage();
            }
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }

        $this->sunatTest = [
            'ok' => empty($errors),
            'message' => empty($errors)
                ? 'Configuración lista para emitir en ' . $environment . '.'
                : 'La configuración tiene ' . count($errors) . ' problema(s) que corregí.',
            'errors' => $errors,
            'warnings' => $warnings,
            'details' => $details,
        ];
    }

    protected function resolveCertificate(): ?string
    {
        if ($this->certificate) {
            return $this->certificate->get();
        }

        if ($this->company && $this->company->certificate_path) {
            $content = Storage::disk('local')->get($this->company->certificate_path);
            return $content ?: null;
        }

        return null;
    }

    protected function extractPem(string $content): ?string
    {
        if (preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $content, $matches)) {
            return $matches[0];
        }

        return null;
    }

    public function render()
    {
        return view('livewire.company.settings');
    }
}
