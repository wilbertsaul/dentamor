<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'ruc', 'business_name', 'commercial_name', 'address',
        'logo_path', 'sunat_username', 'sunat_password',
        'certificate_path', 'certificate_password', 'production',
        'sunat_ubigeo', 'sunat_department', 'sunat_province', 'sunat_district',
        'sunat_urbanization', 'sunat_establishment_code', 'sunat_email', 'sunat_phone',
        'sunat_username_secondary', 'sunat_password_secondary',
        'boleta_serie', 'factura_serie', 'nc_boleta_serie', 'nc_factura_serie',
        'whatsapp_enabled', 'whatsapp_phone_number_id', 'whatsapp_business_account_id',
        'whatsapp_token', 'whatsapp_api_version', 'whatsapp_template_name',
        'whatsapp_template_lang',
    ];

    protected $hidden = [
        'sunat_password',
        'sunat_password_secondary',
        'certificate_password',
        'whatsapp_token',
    ];

    protected $casts = [
        'production' => 'boolean',
        'sunat_password' => 'encrypted',
        'sunat_password_secondary' => 'encrypted',
        'certificate_password' => 'encrypted',
        'whatsapp_enabled' => 'boolean',
        'whatsapp_token' => 'encrypted',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function series()
    {
        return $this->hasMany(DocumentSeries::class);
    }

    public function operations()
    {
        return $this->hasMany(SunatOperation::class);
    }

    public function hasPrimarySol(): bool
    {
        return filled($this->ruc)
            && filled($this->sunat_username)
            && filled($this->sunat_password);
    }

    public function hasSecondarySol(): bool
    {
        return filled($this->sunat_username_secondary)
            && filled($this->sunat_password_secondary);
    }

    public function hasAddress(): bool
    {
        return filled($this->sunat_ubigeo)
            && filled($this->sunat_department)
            && filled($this->sunat_province)
            && filled($this->sunat_district)
            && filled($this->address);
    }

    public function establishmentCode(): string
    {
        $code = preg_replace('/\D/', '', (string) $this->sunat_establishment_code);

        return $code !== '' ? $code : '0000';
    }

    public function environment(): string
    {
        return $this->production ? 'produccion' : 'beta';
    }

    /**
     * En beta el resumen diario y la baja pueden salir con el usuario principal
     * mientras no exista un usuario secundario configurado.
     */
    public function fallsBackToPrimarySol(): bool
    {
        return ! $this->hasSecondarySol() && ! $this->production;
    }

    public function secondarySolDuplicatesPrimary(): bool
    {
        return $this->hasSecondarySol()
            && filled($this->sunat_username)
            && $this->sunat_username_secondary === $this->sunat_username;
    }

    public function boletaSerie(): string
    {
        return $this->boleta_serie ?: 'B002';
    }

    public function facturaSerie(): string
    {
        return $this->factura_serie ?: 'F002';
    }

    public function ncBoletaSerie(): string
    {
        return $this->nc_boleta_serie ?: 'BC02';
    }

    public function ncFacturaSerie(): string
    {
        return $this->nc_factura_serie ?: 'FC02';
    }

    public function whatsappConfigured(): bool
    {
        return (bool) $this->whatsapp_enabled
            && filled($this->whatsapp_phone_number_id)
            && filled($this->whatsapp_token)
            && filled($this->whatsapp_template_name);
    }

    public function whatsappApiVersion(): string
    {
        return $this->whatsapp_api_version ?: config('whatsapp.api_version', 'v21.0');
    }

    public function whatsappTemplateLang(): string
    {
        return $this->whatsapp_template_lang ?: config('whatsapp.template_lang', 'es');
    }
}
