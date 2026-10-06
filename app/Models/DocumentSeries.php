<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSeries extends Model
{
    protected $fillable = [
        'company_id', 'serie', 'document_type', 'next_number',
    ];

    protected $casts = [
        'next_number' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isElectronic(): bool
    {
        return preg_match('/^[FB][A-Z0-9]{3}$/', $this->serie) === 1;
    }
}
