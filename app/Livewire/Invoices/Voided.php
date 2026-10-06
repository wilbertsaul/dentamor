<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use App\Services\Sunat\ResponseMapper;
use App\Services\SunatService;
use Livewire\Component;

class Voided extends Component
{
    public Invoice $invoice;

    public $motivo = '';

    public $errorMessage = '';

    public function mount(Invoice $invoice)
    {
        $invoice->loadMissing(['company', 'client']);

        abort_unless($invoice->canBeVoided(), 404, 'Este comprobante no admite comunicación de baja.');

        $this->invoice = $invoice;
    }

    public function anular()
    {
        $this->reset('errorMessage');

        $esBoleta = $this->invoice->documentType() === Invoice::TYPE_BOLETA;

        $this->validate([
            'motivo' => $esBoleta ? 'nullable|string|max:100' : 'required|string|min:5|max:100',
        ], [
            'motivo.required' => 'Indica el motivo de la baja (mínimo 5 caracteres).',
            'motivo.max' => 'El motivo admite máximo 100 caracteres.',
        ]);

        try {
            $operation = SunatService::voidInvoice($this->invoice, $this->motivo);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        if ($operation->status === 'processing') {
            session()->flash(
                'message',
                ($esBoleta ? 'Resumen diario (baja) ' : 'Comunicación de baja ')
                .$operation->reference.' enviada. SUNAT devolvió el ticket '.$operation->ticket
                .': el estado se confirmará al consultar el CDR.'
            );

            return redirect()->route('sunat.resumen');
        }

        if (! ResponseMapper::isValid($operation->status)) {
            $this->errorMessage = trim(
                'SUNAT rechazó la comunicación ('.ResponseMapper::label($operation->status).'): '
                .($operation->description ?: 'sin descripción')
            );

            return;
        }

        session()->flash(
            'message',
            ($esBoleta ? 'Resumen diario (baja) ' : 'Comunicación de baja ')
            .$operation->reference.' aceptada. El comprobante '.$this->invoice->full_number.' quedó anulado.'
        );

        return redirect()->route('invoices.index');
    }

    public function render()
    {
        return view('livewire.invoices.voided', [
            'esBoleta' => $this->invoice->documentType() === Invoice::TYPE_BOLETA,
        ]);
    }
}
