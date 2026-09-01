<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentRecord;
use App\Services\PdfService;
use App\Services\SunatService;
use Livewire\Component;

class BulkInvoice extends Component
{
    public $payments;
    public $selected = [];

    public function mount()
    {
        $this->payments = PaymentRecord::where('status', 'pending')->latest()->get();
    }

    public function toggleAll()
    {
        if (count($this->selected) === $this->payments->count()) {
            $this->selected = [];
        } else {
            $this->selected = $this->payments->pluck('id')->toArray();
        }
    }

    public function processSelected()
    {
        if (empty($this->selected)) {
            session()->flash('error', 'Seleccione al menos un pago para facturar.');
            return;
        }

        $company = Company::first();
        if (!$company) {
            session()->flash('error', 'Debe configurar la empresa primero.');
            return;
        }

        $results = ['success' => 0, 'error' => 0, 'errors' => []];

        foreach ($this->selected as $paymentId) {
            $payment = PaymentRecord::find($paymentId);
            if (!$payment || $payment->status !== 'pending') {
                continue;
            }

            try {
                $client = null;
                if ($payment->client_doc) {
                    $client = Client::where('doc_number', $payment->client_doc)->first();
                }
                if (!$client) {
                    $client = Client::create([
                        'doc_type' => 'DNI',
                        'doc_number' => $payment->client_doc ?? '00000000',
                        'name' => $payment->client_name,
                    ]);
                }

                $serie = $payment->invoice_type === 'F' ? 'F001' : 'B001';
                $lastInvoice = Invoice::withTrashed()->where('serie', $serie)->orderBy('number', 'desc')->first();
                $number = $lastInvoice ? $lastInvoice->number + 1 : 1;

                $subtotal = round($payment->amount / 1.18, 2);
                $igv = round($payment->amount - $subtotal, 2);

                $invoice = Invoice::create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'invoice_type' => $payment->invoice_type,
                    'serie' => $serie,
                    'number' => $number,
                    'issue_date' => now()->toDateString(),
                    'currency' => 'PEN',
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'total' => $payment->amount,
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Servicio odontológico',
                    'quantity' => 1,
                    'unit_price' => $payment->amount,
                    'subtotal' => $subtotal,
                ]);

                try {
                    $invoice->load('items');
                    SunatService::sendInvoice($invoice);
                    PdfService::generate($invoice);
                } catch (\Throwable $e) {
                    $invoice->update([
                        'sunat_status' => 'error',
                        'sunat_description' => $e->getMessage(),
                    ]);
                }

                $payment->update([
                    'status' => 'invoiced',
                    'invoice_id' => $invoice->id,
                ]);

                $results['success']++;
            } catch (\Throwable $e) {
                $results['error']++;
                $results['errors'][] = "Pago #{$payment->id}: {$e->getMessage()}";
            }
        }

        $this->selected = [];
        $this->payments = PaymentRecord::where('status', 'pending')->latest()->get();

        session()->flash('bulkResult', $results);
    }

    public function render()
    {
        return view('livewire.payments.bulk-invoice');
    }
}
