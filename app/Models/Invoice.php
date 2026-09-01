<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'company_id', 'client_id', 'invoice_type', 'serie', 'number',
        'issue_date', 'due_date', 'currency', 'subtotal', 'igv', 'total',
        'cdr_path', 'sunat_status', 'sunat_description', 'hash_cpe',
        'xml_path', 'pdf_path', 'production',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'production' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function paymentRecords()
    {
        return $this->hasMany(PaymentRecord::class);
    }

    public function getFullNumberAttribute()
    {
        return "{$this->serie}-{$this->number}";
    }
}
