<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $today = now()->toDateString();

        return view('livewire.dashboard', [
            'totalInvoicesToday' => Invoice::whereDate('issue_date', $today)->count(),
            'totalAmountToday' => Invoice::whereDate('issue_date', $today)->sum('total'),
            'recentInvoices' => Invoice::with('client')->latest()->take(10)->get(),
            'reservasPendientes' => Invoice::where('is_reservation', true)->count(),
            'sunatAccepted' => Invoice::where('sunat_status', 'accepted')->count(),
            'sunatPending' => Invoice::where('sunat_status', 'pending')->count(),
            'sunatRejected' => Invoice::whereIn('sunat_status', ['rejected', 'error'])->count(),

            'acceptedMonth' => Invoice::whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->where('sunat_status', 'accepted')
                ->selectRaw('COALESCE(SUM(total), 0) as total_sum')
                ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal_sum')
                ->first(),
            'totalInvoicesMonth' => Invoice::whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->count(),
        ]);
    }
}
