<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PdfService;
use App\Services\SunatLookupService;
use App\Services\SunatService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Girar extends Component
{
    public $reservation;
    public $invoice_type = 'B';

    public $searchDoc = '';
    public $client_id;
    public $clientName = '';
    public $clientAddress = '';
    public $clientDoc = '';
    public $isLookingUp = false;
    public $clientSource = '';

    protected $listeners = ['refreshGirar' => '$refresh'];

    public function mount(Invoice $reservation)
    {
        if (!$reservation->is_reservation) {
            abort(404);
        }
        $this->reservation = $reservation->load(['items', 'client']);
        if ($reservation->client) {
            $this->client_id = $reservation->client->id;
            $this->clientName = $reservation->client->name;
            $this->clientAddress = $reservation->client->address ?? '';
            $this->clientDoc = $reservation->client->doc_number;
            $this->searchDoc = $reservation->client->doc_type === 'N' ? '' : $reservation->client->doc_number;
        }
    }

    public function quitarCliente()
    {
        $this->searchDoc = '';
        $this->client_id = null;
        $this->clientName = '';
        $this->clientAddress = '';
        $this->clientDoc = '';
        $this->clientSource = '';
    }

    public function updatedInvoiceType()
    {
        // Mantener el cliente seleccionado (incluso el precargado de la reserva)
        // al cambiar de tipo de comprobante. El usuario puede quitarlo con quitarCliente().
        $this->searchDoc = '';
    }

    public function buscarCliente()
    {
        $doc = trim($this->searchDoc);
        if (!$doc) return;

        $client = Client::where('doc_number', $doc)->first();
        if ($client) {
            $this->fillClient($client);
            $this->clientSource = 'db';
            $this->dispatch('showNotification', ['message' => 'Cliente encontrado en base de datos.', 'type' => 'success']);
            return;
        }

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
            $this->dispatch('showNotification', ['message' => 'No se encontraron datos para este ' . $type . '.', 'type' => 'error']);
            return;
        }

        try {
            $client = Client::create([
                'doc_type' => $type,
                'doc_number' => $doc,
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
            ]);
        } catch (\Throwable $e) {
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
        $this->dispatch('showNotification', ['message' => 'Cliente creado desde ' . $type, 'type' => 'success']);
    }

    public function girar()
    {
        $total = (float) $this->reservation->total;
        $isFactura = $this->invoice_type === 'F';
        $requiresDocument = $isFactura || $total > 700;

        if ($isFactura) {
            $this->validate([
                'client_id' => 'required|exists:clients,id',
                'clientDoc' => 'required',
            ]);
            if (strlen((string) $this->clientDoc) !== 11) {
                $this->addError('clientDoc', 'Para facturar, el cliente debe tener RUC (11 dígitos).');
                return;
            }
            $client = Client::find($this->client_id);
            if ($client && $client->doc_type !== 'RUC') {
                $this->addError('clientDoc', 'Para facturar, el cliente debe tener RUC registrado.');
                return;
            }
        } elseif ($requiresDocument) {
            $this->validate([
                'client_id' => 'required|exists:clients,id',
                'clientDoc' => 'required',
            ]);
            $client = Client::find($this->client_id);
            if ($client && $client->doc_type === 'N') {
                $this->addError('clientDoc', 'Para boletas mayores a S/700 se requiere el DNI/RUC del cliente.');
                return;
            }
        } else {
            $this->resetValidation('client_id');
        }

        $company = Company::first();
        if (!$company) {
            session()->flash('error', 'Debe configurar la empresa primero.');
            return redirect()->route('invoices.index');
        }

        $serie = $this->invoice_type === 'F' ? 'F001' : 'B001';
        $lastInvoice = Invoice::withTrashed()->where('serie', $serie)->orderBy('number', 'desc')->first();
        $number = $lastInvoice ? $lastInvoice->number + 1 : 1;

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $this->client_id ?: null,
            'invoice_type' => $this->invoice_type,
            'serie' => $serie,
            'number' => $number,
            'issue_date' => now()->format('Y-m-d'),
            'currency' => $this->reservation->currency,
            'subtotal' => $this->reservation->subtotal,
            'igv' => $this->reservation->igv,
            'total' => $this->reservation->total,
            'sunat_status' => 'pending',
            'is_reservation' => false,
        ]);

        foreach ($this->reservation->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_id' => $item->service_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ]);
        }

        $invoice->load('items');
        $result = SunatService::sendInvoice($invoice);

        if ($result['success']) {
            $invoice->update(['sunat_status' => $result['accepted'] ? 'accepted' : 'rejected']);
            try {
                $pdfPath = PdfService::generate($invoice);
            } catch (\Throwable $e) {
                Log::error('PDF generation failed: ' . $e->getMessage());
                $pdfPath = null;
            }
            $this->reservation->delete();
            if ($pdfPath) {
                $this->dispatch('openPdf', [
                    'url' => route('invoices.pdf.view', $invoice),
                    'redirect' => route('invoices.index'),
                ]);
            }
            session()->flash('message', 'Documento emitido correctamente. Estado: ' . $result['description']);

            return redirect()->route('invoices.index');
        } else {
            $invoice->delete();
            session()->flash('error', 'Error al enviar a SUNAT: ' . $result['description']);

            return redirect()->route('invoices.index');
        }
    }

    private function fillClient(Client $client): void
    {
        $this->client_id = $client->id;
        $this->clientName = $client->name;
        $this->clientAddress = $client->address ?? '';
        $this->clientDoc = $client->doc_number;
    }

    private function detectDocType(string $doc): string
    {
        if (strlen($doc) === 11) return 'RUC';
        if (strlen($doc) === 8) return 'DNI';
        return 'CE';
    }

    public function render()
    {
        if (!$this->reservation) {
            return redirect()->route('invoices.index');
        }
        return view('livewire.invoices.girar');
    }
}
