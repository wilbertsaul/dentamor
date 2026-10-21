<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Models\Company;
use App\Services\Sunat\CertificateService;
use App\Services\Sunat\ConnectionTest;
use App\Services\Sunat\SeriesService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

    public $sunat_ubigeo;

    public $sunat_department;

    public $sunat_province;

    public $sunat_district;

    public $sunat_urbanization;

    public $sunat_establishment_code = '0000';

    public $sunat_email;

    public $sunat_phone;

    public $sunat_username;

    public $sunat_password;

    public $sunat_username_secondary;

    public $sunat_password_secondary;

    public $boleta_serie = 'B002';

    public $factura_serie = 'F002';

    public $nc_boleta_serie = 'BC02';

    public $nc_factura_serie = 'FC02';

    public $boleta_serie_beta = 'PR01';

    public $factura_serie_beta = 'PR02';

    public $nc_boleta_serie_beta = 'PR03';

    public $nc_factura_serie_beta = 'PR04';

    public $boleta_numero = 1;

    public $factura_numero = 1;

    public $boleta_numero_beta = 1;

    public $factura_numero_beta = 1;

    public $certificate_password;

    public $certificate;

    public $logo;

    public $logo_preview = null;

    public $production = false;

    public $sunatTest = null;

    public $whatsapp_enabled = false;

    public $whatsapp_session = 'default';

    public $whatsapp_test_phone = '';

    public $whatsappTest = null;

    public $whatsappStatus = null;

    public function mount()
    {
        $this->company = Company::first();

        if ($this->company) {
            $this->ruc = $this->company->ruc;
            $this->business_name = $this->company->business_name;
            $this->commercial_name = $this->company->commercial_name;
            $this->address = $this->company->address;
            $this->sunat_ubigeo = $this->company->sunat_ubigeo;
            $this->sunat_department = $this->company->sunat_department;
            $this->sunat_province = $this->company->sunat_province;
            $this->sunat_district = $this->company->sunat_district;
            $this->sunat_urbanization = $this->company->sunat_urbanization;
            $this->sunat_establishment_code = $this->company->sunat_establishment_code ?: '0000';
            $this->sunat_email = $this->company->sunat_email;
            $this->sunat_phone = $this->company->sunat_phone;
            $this->sunat_username = $this->company->sunat_username;
            $this->sunat_username_secondary = $this->company->sunat_username_secondary;
            $this->boleta_serie = $this->company->boletaSerie();
            $this->factura_serie = $this->company->facturaSerie();
            $this->nc_boleta_serie = $this->company->ncBoletaSerie();
            $this->nc_factura_serie = $this->company->ncFacturaSerie();
            $this->boleta_serie_beta = $this->company->boletaSerieBeta();
            $this->factura_serie_beta = $this->company->facturaSerieBeta();
            $this->nc_boleta_serie_beta = $this->company->ncBoletaSerieBeta();
            $this->nc_factura_serie_beta = $this->company->ncFacturaSerieBeta();
            $this->boleta_numero = SeriesService::peek($this->company, $this->boleta_serie);
            $this->factura_numero = SeriesService::peek($this->company, $this->factura_serie);
            $this->boleta_numero_beta = SeriesService::peek($this->company, $this->boleta_serie_beta);
            $this->factura_numero_beta = SeriesService::peek($this->company, $this->factura_serie_beta);
            $this->production = $this->company->production ?? false;
            $this->whatsapp_enabled = $this->company->whatsapp_enabled ?? false;
            $this->whatsapp_session = $this->company->whatsappSession();
            $this->logo_preview = $this->logoDataUri($this->company->logo_path);
        }
    }

    public function save()
    {
        $this->validate([
            'ruc' => 'required|size:11',
            'business_name' => 'required',
            'commercial_name' => 'nullable',
            'address' => 'required',
            'sunat_ubigeo' => 'required|size:6',
            'sunat_department' => 'required',
            'sunat_province' => 'required',
            'sunat_district' => 'required',
            'sunat_urbanization' => 'nullable',
            'sunat_establishment_code' => 'required|size:4',
            'sunat_email' => 'nullable|email',
            'sunat_phone' => 'nullable|max:30',
            'sunat_username' => 'required',
            'sunat_username_secondary' => 'nullable',
            'boleta_serie' => 'required|string|max:10|regex:/^B\d{3}$/',
            'factura_serie' => 'required|string|max:10|regex:/^F\d{3}$/',
            'nc_boleta_serie' => 'required|string|max:10|regex:/^BC\d{2}$/',
            'nc_factura_serie' => 'required|string|max:10|regex:/^FC\d{2}$/',
            'boleta_serie_beta' => 'required|string|max:4|regex:/^[A-Z0-9]{4}$/',
            'factura_serie_beta' => 'required|string|max:4|regex:/^[A-Z0-9]{4}$/',
            'nc_boleta_serie_beta' => 'required|string|max:4|regex:/^[A-Z0-9]{4}$/',
            'nc_factura_serie_beta' => 'required|string|max:4|regex:/^[A-Z0-9]{4}$/',
            'certificate' => ['nullable', 'file', 'extensions:'.implode(',', CertificateService::EXTENSIONS), 'max:2048'],
            'certificate_password' => 'nullable',
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,gif|max:2048',
            'production' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'whatsapp_session' => 'nullable|max:50',
        ]);

        $data = [
            'ruc' => $this->ruc,
            'business_name' => $this->business_name,
            'commercial_name' => $this->commercial_name,
            'address' => $this->address,
            'sunat_ubigeo' => $this->sunat_ubigeo,
            'sunat_department' => $this->sunat_department,
            'sunat_province' => $this->sunat_province,
            'sunat_district' => $this->sunat_district,
            'sunat_urbanization' => $this->sunat_urbanization,
            'sunat_establishment_code' => $this->sunat_establishment_code,
            'sunat_email' => $this->sunat_email,
            'sunat_phone' => $this->sunat_phone,
            'sunat_username' => $this->sunat_username,
            'sunat_username_secondary' => $this->sunat_username_secondary,
            'boleta_serie' => strtoupper(trim($this->boleta_serie)),
            'factura_serie' => strtoupper(trim($this->factura_serie)),
            'nc_boleta_serie' => strtoupper(trim($this->nc_boleta_serie)),
            'nc_factura_serie' => strtoupper(trim($this->nc_factura_serie)),
            'boleta_serie_beta' => strtoupper(trim($this->boleta_serie_beta)),
            'factura_serie_beta' => strtoupper(trim($this->factura_serie_beta)),
            'nc_boleta_serie_beta' => strtoupper(trim($this->nc_boleta_serie_beta)),
            'nc_factura_serie_beta' => strtoupper(trim($this->nc_factura_serie_beta)),
            'production' => $this->production,
            'whatsapp_enabled' => $this->whatsapp_enabled,
            'whatsapp_session' => $this->whatsapp_session ?: 'default',
        ];

        // Las claves SOL nunca se devuelven al navegador: solo se reemplazan si se escriben.
        foreach (['sunat_password', 'sunat_password_secondary'] as $secret) {
            if (filled($this->{$secret})) {
                $data[$secret] = $this->{$secret};
            }
        }

        if ($this->certificate) {
            try {
                $data['certificate_path'] = CertificateService::store(
                    $this->certificate,
                    $this->certificate_password,
                    $this->company?->certificate_path
                );
                $data['certificate_password'] = null;
            } catch (\Throwable $e) {
                throw ValidationException::withMessages(['certificate' => $e->getMessage()]);
            }
        }

        if ($this->logo) {
            $path = 'logos/logo.'.strtolower($this->logo->extension());

            Storage::disk('local')->put($path, file_get_contents($this->logo->getRealPath()));

            if ($this->company && $this->company->logo_path && $this->company->logo_path !== $path) {
                Storage::disk('local')->delete($this->company->logo_path);
            }

            $data['logo_path'] = $path;
        }

        if ($this->company) {
            $this->company->update($data);
            $this->company = $this->company->fresh();
        } else {
            $this->company = Company::create($data);
        }

        $this->sunat_password = null;
        $this->sunat_password_secondary = null;
        $this->certificate_password = null;
        $this->logo = null;
        $this->logo_preview = $this->company ? $this->logoDataUri($this->company->logo_path) : null;

        session()->flash('message', 'Configuración guardada correctamente.');
        $this->sunatTest = null;
        $this->whatsappTest = null;
        $this->whatsappStatus = null;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, [
            'ruc', 'sunat_username', 'sunat_password', 'sunat_username_secondary',
            'sunat_password_secondary', 'production', 'certificate', 'sunat_ubigeo',
            'boleta_serie', 'factura_serie', 'nc_boleta_serie', 'nc_factura_serie',
            'boleta_serie_beta', 'factura_serie_beta', 'nc_boleta_serie_beta', 'nc_factura_serie_beta',
            'whatsapp_enabled', 'whatsapp_session',
        ], true)) {
            $this->sunatTest = null;
            $this->whatsappTest = null;
            $this->whatsappStatus = null;
        }
    }

    public function updatedLogo()
    {
        if (! $this->logo) {
            $this->logo_preview = $this->company ? $this->logoDataUri($this->company->logo_path) : null;

            return;
        }

        $this->validate(['logo' => 'nullable|file|mimes:png,jpg,jpeg,gif|max:2048']);
        $this->logo_preview = $this->logo->temporaryUrl();
    }

    public function removeLogo()
    {
        if ($this->company && $this->company->logo_path) {
            Storage::disk('local')->delete($this->company->logo_path);
            $this->company->update(['logo_path' => null]);
            $this->company = $this->company->fresh();
        }

        $this->logo = null;
        $this->logo_preview = null;
    }

    private function logoDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mime = mime_content_type(Storage::disk('local')->path($path)) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }

    public function testSunat()
    {
        $this->sunatTest = null;

        $this->validate([
            'ruc' => 'required|size:11',
            'sunat_username' => 'required',
        ]);

        $certificate = $this->certificate ? $this->certificate->get() : null;

        if ($certificate) {
            try {
                $certificate = CertificateService::normalize($certificate, (string) $this->certificate_password);
            } catch (\Throwable $e) {
                $this->sunatTest = [
                    'ok' => false,
                    'message' => 'No se pudo leer el certificado subido.',
                    'errors' => [$e->getMessage()],
                    'warnings' => [],
                    'details' => [],
                ];

                return;
            }
        }

        $company = $this->company ? clone $this->company : new Company;
        $company->forceFill([
            'ruc' => $this->ruc,
            'sunat_username' => $this->sunat_username,
            'production' => $this->production,
        ]);

        if (filled($this->address)) {
            $company->forceFill(['address' => $this->address]);
        }

        if (filled($this->sunat_ubigeo)) {
            $company->forceFill([
                'sunat_ubigeo' => $this->sunat_ubigeo,
                'sunat_department' => $this->sunat_department,
                'sunat_province' => $this->sunat_province,
                'sunat_district' => $this->sunat_district,
            ]);
        }

        $this->sunatTest = ConnectionTest::run($company, $certificate, [
            'sunat_username' => $this->sunat_username,
            'sunat_password' => filled($this->sunat_password) ? $this->sunat_password : null,
            'sunat_username_secondary' => $this->sunat_username_secondary ?: null,
            'sunat_password_secondary' => filled($this->sunat_password_secondary) ? $this->sunat_password_secondary : null,
        ]);
    }

    public function testWhatsApp()
    {
        $this->whatsappTest = null;

        $this->validate([
            'whatsapp_test_phone' => 'required|max:20',
        ]);

        $company = $this->company ? clone $this->company : new Company;
        $company->forceFill([
            'whatsapp_enabled' => true,
            'whatsapp_session' => $this->whatsapp_session ?: 'default',
        ]);

        $this->whatsappTest = WhatsAppService::sendTest($company, $this->whatsapp_test_phone);
    }

    public function checkWhatsAppSession()
    {
        $this->whatsappStatus = null;

        $company = $this->company ? clone $this->company : new Company;
        $company->forceFill([
            'whatsapp_session' => $this->whatsapp_session ?: 'default',
        ]);

        $this->whatsappStatus = WhatsAppService::sessionStatus($company);
    }

    public function startWhatsAppSession()
    {
        $this->whatsappStatus = null;

        $company = $this->company ? clone $this->company : new Company;
        $company->forceFill([
            'whatsapp_session' => $this->whatsapp_session ?: 'default',
        ]);

        $result = WhatsAppService::startSession($company);

        $this->whatsappStatus = [
            'ok' => $result['ok'],
            'status' => null,
            'name' => null,
            'error' => $result['ok'] ? null : $result['message'],
            'started' => $result['ok'],
            'message' => $result['message'],
        ];
    }

    public function render()
    {
        return view('livewire.company.settings');
    }
}
