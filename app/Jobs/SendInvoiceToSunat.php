<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\Sunat\InvoiceSender;
use App\Services\Sunat\ResponseMapper;
use App\Services\Sunat\SunatLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvoiceToSunat implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * El envío puede fallar por cortesías de red o timeouts; reintentamos con espera creciente.
     */
    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $invoiceId) {}

    public function handle(): void
    {
        $invoice = Invoice::withTrashed()
            ->with(['company', 'client', 'items', 'relatedInvoice'])
            ->find($this->invoiceId);

        if ($invoice === null) {
            return;
        }

        $result = InvoiceSender::send($invoice);

        if ($result['accepted'] ?? false) {
            return;
        }

        $status = (string) ($result['status'] ?? '');

        // Un rechazo con CDR (código 2) no mejora reintentando: solo los fallos técnicos se reencolan.
        if (! in_array($status, [ResponseMapper::ERROR, 'sent', 'pending'], true)) {
            return;
        }

        SunatLogger::warning('Envío sin resultado válido; se reencola el reintento', [
            'invoice_id' => $invoice->id,
            'documento' => $invoice->full_number,
            'status' => $status,
            'code' => (string) ($result['code'] ?? ''),
        ]);

        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff[$this->attempts() - 1] ?? 900);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $invoice = Invoice::withTrashed()->with('company')->find($this->invoiceId);

        if ($invoice === null) {
            return;
        }

        $invoice->forceFill([
            'sunat_status' => ResponseMapper::ERROR,
            'sunat_description' => 'Reintentos agotados: '.$exception->getMessage(),
        ])->save();

        SunatLogger::error('Envío a SUNAT agotó los reintentos', [
            'invoice_id' => $invoice->id,
            'documento' => $invoice->full_number,
            'error' => $exception->getMessage(),
        ]);
    }
}
