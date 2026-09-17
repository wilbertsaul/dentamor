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

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $acceptedMonth = Invoice::whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->where('sunat_status', 'accepted')
            ->selectRaw('COALESCE(SUM(total), 0) as total_sum')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal_sum')
            ->first();

        $previousMonth = now()->subMonthNoOverflow();
        $previousMonthSum = (float) Invoice::whereMonth('issue_date', $previousMonth->month)
            ->whereYear('issue_date', $previousMonth->year)
            ->where('sunat_status', 'accepted')
            ->sum('total');

        $monthSum = (float) $acceptedMonth->total_sum;
        $monthVariation = $previousMonthSum > 0
            ? (($monthSum - $previousMonthSum) / $previousMonthSum) * 100
            : 0;

        $salesByDay = Invoice::whereBetween('issue_date', [
                now()->subDays(29)->startOfDay()->toDateString(),
                now()->endOfDay()->toDateString(),
            ])
            ->selectRaw('issue_date, COALESCE(SUM(total), 0) as total')
            ->groupBy('issue_date')
            ->pluck('total', 'issue_date');

        $chartDays = collect(range(29, 0))->map(function (int $daysAgo) use ($salesByDay) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('d'),
                'full' => $date->format('d/m'),
                'total' => (float) ($salesByDay[$date->toDateString()] ?? 0),
                'is_today' => $date->isToday(),
            ];
        });

        $chartMax = max(1, (float) $chartDays->max('total'));

        $totalDocs = Invoice::whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->count();

        return view('livewire.dashboard', [
            'totalInvoicesToday' => Invoice::whereDate('issue_date', $today)->count(),
            'totalAmountToday' => Invoice::whereDate('issue_date', $today)->sum('total'),
            'recentInvoices' => Invoice::with('client')->latest()->take(6)->get(),
            'reservasPendientes' => Invoice::where('is_reservation', true)->count(),
            'reservasPendientesMonto' => Invoice::where('is_reservation', true)->sum('total'),
            'sunatAccepted' => Invoice::where('sunat_status', 'accepted')->count(),
            'sunatPending' => Invoice::where('sunat_status', 'pending')->count(),
            'sunatRejected' => Invoice::whereIn('sunat_status', ['rejected', 'error'])->count(),
            'acceptedMonth' => $acceptedMonth,
            'monthSum' => $monthSum,
            'monthVariation' => $monthVariation,
            'previousMonthSum' => $previousMonthSum,
            'previousMonthLabel' => ucfirst($previousMonth->translatedFormat('F')),
            'totalInvoicesMonth' => $totalDocs,
            'chartDays' => $chartDays,
            'chartMax' => $chartMax,
        ]);
    }
}
