<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Services\PdfService;
use App\Services\SunatLookupService;
use App\Services\SunatService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Create extends Component
{
    public $services;
    public $client_id;
    public $invoice_type = 'B';
    public $items = [];
    public $issue_date;
    public $currency = 'PEN';
    public $payment_condition = 'contado';
    public $observations = '';
    public $is_draft = false;

    public $searchDoc = '';
    public $clientName = '';
    public $clientAddress = '';
    public $clientDoc = '';
    public $isLookingUp = false;
    public $clientSource = '';

    public function mount()
    {
        $this->services = Service::orderBy('name')->get();
        $this->items = [['service_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'subtotal' => 0]];
        $this->issue_date = now()->format('Y-m-d');
    }

    public function searchClient()
    {
        $doc = trim($this->searchDoc);
        if (!$doc) return;

        Log::info("searchClient: searching doc='{$doc}'");

        $client = Client::where('doc_number', $doc)->first();
        if ($client) {
            Log::info("searchClient: found in DB id={$client->id} name={$client->name}");
            $this->fillClient($client);
            $this->clientSource = 'db';
            return;
        }

        Log::info("searchClient: NOT in DB, proceeding to API lookup");

        $type = $this->detectDocType($doc);

        if ($type === 'CE') {
            $this->dispatch('showNotification', ['message' => 'Clientes con CE deben registrarse manualmente', 'type' => 'warning']);
            return;
        }

        $lookup = app(SunatLookupService::class);
        if (!$lookup->isConfigured()) {
            $this->dispatch('showNotification', ['message' => 'API no configurada. Ingrese datos manualmente.', 'type' => 'warning']);
            return;
        }

        $this->isLookingUp = true;
        $data = $type === 'RUC' ? $lookup->lookupRuc($doc) : $lookup->lookupDni($doc);
        $this->isLookingUp = false;

        if (!$data) {
            Log::warning("searchClient: API returned no data for {$type}={$doc}");
            $this->dispatch('showNotification', ['message' => 'No se encontraron datos para este ' . $type . '.', 'type' => 'error']);
            return;
        }

        Log::info("searchClient: API success for {$type}={$doc}, name={$data['name']}");

        try {
            $client = Client::create([
                'doc_type' => $type,
                'doc_number' => $doc,
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error("searchClient: Client create failed for {$type}={$doc}: " . $e->getMessage());
            $existing = Client::where('doc_number', $doc)->first();
            if ($existing) {
                $this->fillClient($existing);
                $this->clientSource = 'db';
                return;
            }
            $this->dispatch('showNotification', ['message' => 'Error al guardar cliente: ' . $e->getMessage(), 'type' => 'error']);
            return;
        }

        $this->fillClient($client);
        $this->clientSource = 'api';
        $this->dispatch('showNotification', ['message' => 'Cliente creado automáticamente desde ' . $type, 'type' => 'success']);
    }

    private function detectDocType(string $doc): string
    {
        if (strlen($doc) === 11) return 'RUC';
        if (strlen($doc) === 8) return 'DNI';
        return 'CE';
    }

    private function fillClient(Client $client): void
    {
        $this->client_id = $client->id;
        $this->clientName = $client->name;
        $this->clientAddress = $client->address ?? '';
        $this->clientDoc = $client->doc_number;
    }

    public function updatedInvoiceType()
    {
        $this->client_id = null;
        $this->clientName = '';
        $this->clientAddress = '';
        $this->clientDoc = '';
        $this->clientSource = '';
        $this->searchDoc = '';
    }

    public function addItem()
    {
        $this->items[] = ['service_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'subtotal' => 0];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->items = [['service_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'subtotal' => 0]];
        }
    }

    public function updatedItems($value, $key)
    {
        if (!is_string($key) || $key === '') {
            return;
        }

        if (str_ends_with($key, '.service_id') && $value) {
            $parts = explode('.', $key);
            $index = $parts[0];
            $service = Service::find($value);
            if ($service) {
                $this->items[$index]['description'] = $service->name;
                if (!isset($this->items[$index]['unit_price']) || (float) $this->items[$index]['unit_price'] == 0) {
                    $this->items[$index]['unit_price'] = (float) $service->price;
                }
            }
        }

        $parts = explode('.', $key);
        $idx = $parts[0] ?? null;
        if ($idx !== null && isset($this->items[$idx])) {
            $p = (float) ($this->items[$idx]['unit_price'] ?? 0);
            $q = (int) ($this->items[$idx]['quantity'] ?? 0);
            $this->items[$idx]['subtotal'] = round($p * $q / 1.18, 2);
        }
    }

    public function calcularSubtotal()
    {
        return collect($this->items)->sum(fn($item) => (float) ($item['subtotal'] ?? 0));
    }

    public function calcularIgv()
    {
        $totalConIgv = collect($this->items)->sum(fn($item) => (float)($item['unit_price'] ?? 0) * (int)($item['quantity'] ?? 0));
        return round($totalConIgv - $this->calcularSubtotal(), 2);
    }

    public function calcularTotal()
    {
        return round($this->calcularSubtotal() + $this->calcularIgv(), 2);
    }

    public function save()
    {
        Log::info("Create.save: invoice_type={$this->invoice_type} items=" . count($this->items));
        if ($this->invoice_type === 'R') {
            $this->saveReservation();
            return;
        }

        $this->is_draft = false;
        $this->saveInvoice('issued');
    }

    public function saveDraft()
    {
        if ($this->invoice_type === 'R') {
            $this->saveReservation();
            return;
        }

        $this->is_draft = true;
        $this->saveInvoice('draft');
    }

    private function saveReservation()
    {
        $this->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $company = Company::first();
        if (!$company) {
            session()->flash('error', 'Debe configurar la empresa primero.');
            return redirect()->route('invoices.index');
        }

        $serie = 'RVA';
        $lastReservation = Invoice::withTrashed()->where('serie', $serie)->orderBy('number', 'desc')->first();
        $number = $lastReservation ? $lastReservation->number + 1 : 1;

        $subtotal = $this->calcularSubtotal();
        $igv = $this->calcularIgv();
        $total = $this->calcularTotal();

        $clientId = $this->client_id ?: null;
        $clientName = trim((string) $this->clientName);
        if (!$clientId && $clientName !== '') {
            $existing = Client::where('doc_type', 'N')->where('name', $clientName)->first();
            if ($existing) {
                $clientId = $existing->id;
            } else {
                try {
                    $client = Client::create([
                        'doc_type' => 'N',
                        'doc_number' => '00000000',
                        'name' => $clientName,
                        'address' => $this->clientAddress ?: null,
                    ]);
                    $clientId = $client->id;
                } catch (\Throwable $e) {
                    Log::error("saveReservation create client failed: " . $e->getMessage());
                }
            }
        }

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $clientId,
            'invoice_type' => 'R',
            'serie' => $serie,
            'number' => $number,
            'issue_date' => $this->issue_date,
            'currency' => $this->currency,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'sunat_status' => 'draft',
            'is_reservation' => true,
            'reserved_at' => now(),
        ]);

        Log::info("saveReservation: created RVA-{$number} id={$invoice->id} client_id=" . ($clientId ?? 'null'));

        foreach ($this->items as $item) {
            $itemTotal = (float) $item['unit_price'] * (int) $item['quantity'];
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_id' => $item['service_id'] ?: null,
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => round($itemTotal / 1.18, 2),
            ]);
        }

        session()->flash('message', 'Reserva guardada correctamente. Pendiente de girar boleta o factura.');
        return redirect()->route('invoices.index');
    }

    private function saveInvoice(string $mode)
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_type' => 'required|in:F,B',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $company = Company::first();
        if (!$company) {
            session()->flash('error', 'Debe configurar la empresa primero.');
            return redirect()->route('invoices.index');
        }

        $serie = $this->invoice_type === 'F' ? 'F001' : 'B001';
        $lastInvoice = Invoice::withTrashed()->where('serie', $serie)->orderBy('number', 'desc')->first();
        $number = $lastInvoice ? $lastInvoice->number + 1 : 1;

        $subtotal = $this->calcularSubtotal();
        $igv = $this->calcularIgv();
        $total = $this->calcularTotal();

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $this->client_id,
            'invoice_type' => $this->invoice_type,
            'serie' => $serie,
            'number' => $number,
            'issue_date' => $this->issue_date,
            'currency' => $this->currency,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'sunat_status' => $mode === 'draft' ? 'draft' : 'pending',
        ]);

        foreach ($this->items as $item) {
            $itemTotal = (float) $item['unit_price'] * (int) $item['quantity'];
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_id' => $item['service_id'] ?: null,
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => round($itemTotal / 1.18, 2),
            ]);
        }

        if ($mode === 'draft') {
            session()->flash('message', 'Borrador guardado correctamente.');
            return redirect()->route('invoices.index');
        }

        $invoice->load('items');
        $result = SunatService::sendInvoice($invoice);

        if ($result['success']) {
            $invoice->update(['sunat_status' => $result['accepted'] ? 'accepted' : 'rejected']);
            try {
                $pdfPath = PdfService::generate($invoice);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('PDF generation failed: ' . $e->getMessage());
                $pdfPath = null;
            }
            if ($pdfPath) {
                $this->dispatch('openPdf', [
                    'url' => route('invoices.pdf.view', $invoice),
                    'redirect' => route('invoices.index'),
                ]);
            }
            session()->flash('message', 'Documento emitido correctamente. Estado: ' . $result['description']);

            return redirect()->route('invoices.index');
        } else {
            session()->flash('error', 'Error al enviar a SUNAT: ' . $result['description']);

            return redirect()->route('invoices.index');
        }
    }

    public function render()
    {
        return view('livewire.invoices.create');
    }
}
