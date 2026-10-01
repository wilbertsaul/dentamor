<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    protected $fillable = [
        'client_id', 'client_name', 'client_doc', 'amount',
        'payment_method', 'reference', 'payment_date', 'notes',
        'invoice_type', 'invoice_id', 'status', 'user_id',
        'source', 'client_phone', 'original_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'original_data' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
