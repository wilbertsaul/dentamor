<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Models\PaymentRecord;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $showForm = false;
    public $client_name;
    public $client_doc;
    public $amount;
    public $payment_method;
    public $reference;
    public $payment_date;
    public $notes;
    public $invoice_type = 'B';
    public $statusFilter = 'pending';

    protected function rules()
    {
        return [
            'client_name' => 'required',
            'client_doc' => 'nullable|max:15',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required',
            'reference' => 'nullable',
            'payment_date' => 'required|date',
            'notes' => 'nullable',
            'invoice_type' => 'required|in:F,B',
        ];
    }

    public function render()
    {
        $query = PaymentRecord::query();

        if ($this->statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->statusFilter === 'invoiced') {
            $query->where('status', 'invoiced');
        } elseif ($this->statusFilter === 'skipped') {
            $query->where('status', 'skipped');
        }

        $payments = $query->latest()->paginate(15);

        return view('livewire.payments.index', compact('payments'));
    }

    public function create()
    {
        $this->reset(['client_name', 'client_doc', 'amount', 'payment_method', 'reference', 'payment_date', 'notes']);
        $this->invoice_type = 'B';
        $this->payment_date = now()->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate();

        PaymentRecord::create([
            'client_name' => $this->client_name,
            'client_doc' => $this->client_doc,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'payment_date' => $this->payment_date,
            'notes' => $this->notes,
            'invoice_type' => $this->invoice_type,
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);

        session()->flash('message', 'Registro de pago creado correctamente.');
        $this->showForm = false;
    }

    public function markSkipped($id)
    {
        PaymentRecord::findOrFail($id)->update(['status' => 'skipped']);
        session()->flash('message', 'Pago marcado como omitido.');
    }

    public function delete($id)
    {
        PaymentRecord::findOrFail($id)->delete();
        session()->flash('message', 'Registro eliminado correctamente.');
    }
}
