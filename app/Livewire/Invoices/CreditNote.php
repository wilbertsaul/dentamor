<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PdfService;
use App\Services\Sunat\ResponseMapper;
use App\Services\Sunat\SeriesService;
use App\Services\SunatService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreditNote extends Component
{
    public Invoice $invoice;

    public $motivo = 'ANULACION DE LA OPERACION';

    /** @var array<int, array{id: int, description: string, quantity: float, unit_price: float}> */
    public $items = [];

    public $errorMessage = '';

    public $customer_phone = '';

    public $customer_email = '';

    public function mount(Invoice $invoice)
    {
        abort_unless($invoice->isAccepted(), 404, 'Solo se pueden emitir notas de crédito sobre comprobantes aceptados.');

        $invoice->loadMissing(['client', 'items']);

        $this->invoice = $invoice;
        $this->items = $invoice->items
            ->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])
            ->all();

        $this->customer_phone = $invoice->customer_phone ?: ($invoice->client?->phone ?? '');
        $this->customer_email = $invoice->customer_email ?: ($invoice->client?->email ?? '');
    }

    public function updatedItems(): void
    {
        $this->errorMessage = '';
    }

    public function total(): float
    {
        return round($this->items
            ->sum(fn ($item) => round((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2)), 2);
    }

    public function emitir()
    {
        $this->resetErrorMessage();

        if ($this->items === []) {
            $this->addError('items', 'La nota de crédito debe tener al menos un ítem.');

            return;
        }

        foreach ($this->items as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                $this->addError("items.$index.quantity", 'La cantidad debe ser mayor que cero.');

                return;
            }

            if (mb_strlen(trim((string) ($item['description'] ?? ''))) === 0) {
                $this->addError("items.$index.description", 'Cada ítem necesita descripción.');

                return;
            }
        }

        $this->validate([
            'motivo' => 'required|max:500',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
        ]);

        $company = Company::first();

        if (! $company) {
            $this->errorMessage = 'Debe configurar la empresa primero.';

            return;
        }

        $serie = SeriesService::creditNoteSeries($company, $this->invoice->invoice_type);
        $number = SeriesService::next($company, $serie, Invoice::TYPE_NOTA_CREDITO);

        // Mismo criterio de redondeo que DocumentBuilder: base por ítem e IGV como diferencia.
        $lines = [];
        $subtotal = 0.0;
        $total = 0.0;

        foreach ($this->items as $item) {
            $quantity = (float) $item['quantity'];
            $lineTotal = round((float) $item['unit_price'] * $quantity, 2);
            $base = round($lineTotal / 1.18, 2);

            $lines[] = ['item' => $item, 'quantity' => $quantity, 'base' => $base];
            $subtotal += $base;
            $total += $lineTotal;
        }

        $subtotal = round($subtotal, 2);
        $total = round($total, 2);
        $igv = round($total - $subtotal, 2);

        $creditNote = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $this->invoice->client_id,
            'invoice_type' => $this->invoice->invoice_type,
            'serie' => $serie,
            'number' => $number,
            'issue_date' => now()->toDateString(),
            'currency' => $this->invoice->currency,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'sunat_document_type' => Invoice::TYPE_NOTA_CREDITO,
            'related_invoice_id' => $this->invoice->id,
            'sunat_status' => 'pending',
            'customer_phone' => filled($this->customer_phone) ? $this->customer_phone : null,
            'customer_email' => filled($this->customer_email) ? trim((string) $this->customer_email) : null,
        ]);

        foreach ($lines as $line) {
            InvoiceItem::create([
                'invoice_id' => $creditNote->id,
                'description' => $line['item']['description'],
                'quantity' => $line['quantity'],
                'unit_price' => (float) $line['item']['unit_price'],
                'subtotal' => $line['base'],
            ]);
        }

        $result = SunatService::sendCreditNote($creditNote);

        if (! $result['accepted']) {
            session()->flash(
                'error',
                trim('Nota de crédito '.$creditNote->full_number.': '.ResponseMapper::label($result['status']).' — '.$result['description'])
            );

            return redirect()->route('invoices.index');
        }

        try {
            PdfService::generate($creditNote);
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar el PDF de la nota de crédito: '.$e->getMessage());
        }

        $this->syncContactAndNotify($creditNote, $company);

        session()->flash(
            'message',
            'Nota de crédito '.$creditNote->full_number.' emitida sobre '.$this->invoice->full_number.'. Estado: '.ResponseMapper::label($result['status']).'.'
        );

        return redirect()->route('invoices.index');
    }

    private function syncContactAndNotify(Invoice $creditNote, Company $company): void
    {
        $email = trim((string) $this->customer_email);

        if ($creditNote->client_id) {
            $updates = [];

            if (filled($this->customer_phone)) {
                $updates['phone'] = $this->customer_phone;
            }

            if ($email !== '') {
                $updates['email'] = $email;
            }

            if ($updates !== []) {
                $creditNote->client()->update($updates);
            }
        }

        if (blank($this->customer_phone) || ! WhatsAppService::isConfigured($company)) {
            return;
        }

        try {
            WhatsAppService::sendInvoice($creditNote->fresh(['client', 'company']), $this->customer_phone);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.invoices.credit-note', [
            'serie' => SeriesService::creditNoteSeries($this->invoice->company ?? Company::first(), $this->invoice->invoice_type),
        ]);
    }
}
