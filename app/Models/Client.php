<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'doc_type', 'doc_number', 'name', 'email', 'phone', 'address',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentRecords()
    {
        return $this->hasMany(PaymentRecord::class);
    }
}
