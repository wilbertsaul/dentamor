<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'ruc', 'business_name', 'commercial_name', 'address',
        'logo_path', 'sunat_username', 'sunat_password',
        'certificate_path', 'certificate_password', 'production',
    ];

    protected $casts = [
        'production' => 'boolean',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
