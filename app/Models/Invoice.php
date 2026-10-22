<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    public const TYPE_FACTURA = '01';

    public const TYPE_BOLETA = '03';

    public const TYPE_NOTA_CREDITO = '07';

    public const TYPE_NOTA_DEBITO = '08';

    protected $fillable = [
        'company_id', 'client_id', 'invoice_type', 'serie', 'number',
        'issue_date', 'due_date', 'currency', 'subtotal', 'igv', 'total',
        'cdr_path', 'sunat_status', 'sunat_description', 'hash_cpe',
        'xml_path', 'pdf_path', 'production', 'is_reservation', 'reserved_at',
        'sunat_document_type', 'related_invoice_id', 'sunat_operation_id',
        'converted_invoice_id',
        'sunat_environment', 'sunat_code', 'sunat_notes', 'sunat_ticket',
        'sunat_xml_name', 'sunat_sent_at',
        'customer_phone', 'customer_email', 'whatsapp_status',
        'whatsapp_sent_at', 'whatsapp_message_id', 'whatsapp_error',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'production' => 'boolean',
        'is_reservation' => 'boolean',
        'reserved_at' => 'datetime',
        'sunat_sent_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
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

    public function relatedInvoice()
    {
        return $this->belongsTo(self::class, 'related_invoice_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(self::class, 'related_invoice_id');
    }

    public function operation()
    {
        return $this->belongsTo(SunatOperation::class, 'sunat_operation_id');
    }

    public function convertedInvoice()
    {
        return $this->belongsTo(self::class, 'converted_invoice_id');
    }

    public function getFullNumberAttribute()
    {
        return "{$this->serie}-{$this->number}";
    }

    public function documentType(): string
    {
        if (filled($this->sunat_document_type)) {
            return (string) $this->sunat_document_type;
        }

        $serie = (string) $this->serie;

        if (str_starts_with($serie, 'FC') || str_starts_with($serie, 'BC')) {
            return self::TYPE_NOTA_CREDITO;
        }

        return $this->invoice_type === 'F' ? self::TYPE_FACTURA : self::TYPE_BOLETA;
    }

    public function isCreditNote(): bool
    {
        return $this->documentType() === self::TYPE_NOTA_CREDITO;
    }

    public function isElectronicSerie(): bool
    {
        return preg_match('/^[FB][A-Z0-9]{3}$/', (string) $this->serie) === 1;
    }

    public function isAccepted(): bool
    {
        return in_array($this->sunat_status, ['accepted', 'accepted_with_observations'], true);
    }

    public function isRejected(): bool
    {
        return $this->sunat_status === 'rejected';
    }

    public function hasVoidedRequest(): bool
    {
        return $this->sunat_status === 'voided' || $this->creditNotes()->exists();
    }

    public function isReservation(): bool
    {
        return (bool) $this->is_reservation;
    }

    public function isPendingReservation(): bool
    {
        return $this->isReservation() && $this->sunat_status !== 'converted';
    }

    public function canBeDeleted(): bool
    {
        return $this->isPendingReservation();
    }

    public function markConverted(Invoice $invoice): void
    {
        $this->forceFill([
            'is_reservation' => false,
            'sunat_status' => 'converted',
            'converted_invoice_id' => $invoice->id,
        ])->save();

        $this->delete();
    }

    public function whatsappPhone(): ?string
    {
        return $this->customer_phone ?: $this->client?->phone;
    }

    public function canBeVoided(): bool
    {
        return $this->isAccepted()
            && ! $this->isCreditNote()
            && $this->sunat_status !== 'voided'
            && ! $this->creditNotes()->exists();
    }
}
