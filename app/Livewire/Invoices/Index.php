<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $typeFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingTypeFilter() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }

    public function render()
    {
        $query = Invoice::with('client');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('serie', 'like', '%' . $this->search . '%')
                  ->orWhere('number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function ($c) {
                      $c->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('doc_number', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->statusFilter) {
            $query->where('sunat_status', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('invoice_type', $this->typeFilter);
        }

        if ($this->dateFrom) {
            $query->where('issue_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('issue_date', '<=', $this->dateTo);
        }

        $invoices = $query->latest()->paginate(10);

        $now = now();
        $totalMes = Invoice::whereMonth('issue_date', $now->month)
            ->whereYear('issue_date', $now->year)
            ->sum('total');
        $facturasEmitidas = Invoice::whereMonth('issue_date', $now->month)
            ->whereYear('issue_date', $now->year)
            ->where('invoice_type', 'F')
            ->count();
        $boletasEmitidas = Invoice::whereMonth('issue_date', $now->month)
            ->whereYear('issue_date', $now->year)
            ->where('invoice_type', 'B')
            ->count();
        $porValidar = Invoice::where('sunat_status', 'pending')->count();

        return view('livewire.invoices.index', compact(
            'invoices', 'totalMes', 'facturasEmitidas', 'boletasEmitidas', 'porValidar'
        ));
    }
}
