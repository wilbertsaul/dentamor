<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatOperation extends Model
{
    public const TYPE_SUMMARY = 'RC';

    public const TYPE_VOIDED = 'RA';

    protected $fillable = [
        'company_id', 'type', 'reference', 'reference_date', 'issue_date',
        'environment', 'status', 'ticket', 'code', 'description',
        'xml_path', 'cdr_path', 'documents_count', 'voids_documents', 'resolved_at',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'issue_date' => 'date',
        'documents_count' => 'integer',
        'voids_documents' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function isSummary(): bool
    {
        return $this->type === self::TYPE_SUMMARY;
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'sent', 'processing'], true);
    }

    public function voidsDocuments(): bool
    {
        return (bool) $this->voids_documents;
    }

    public function typeLabel(): string
    {
        if ($this->isSummary()) {
            return $this->voidsDocuments() ? 'Resumen diario (baja)' : 'Resumen diario';
        }

        return 'Comunicación de baja';
    }
}
