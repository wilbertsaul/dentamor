<div class="space-y-6">
    <div>
        <h1 class="text-headline-md text-on-surface">Dashboard</h1>
        <p class="text-body-md text-on-surface-variant mt-1">Resumen de tu actividad de facturación electrónica</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-primary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">receipt_long</span>
                </div>
                <span class="text-label-sm text-outline">Hoy</span>
            </div>
            <div class="text-headline-sm text-on-surface">{{ $totalInvoicesToday }}</div>
            <div class="text-body-sm text-on-surface-variant mt-1">Comprobantes emitidos</div>
        </div>

        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-secondary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-secondary">paid</span>
                </div>
                <span class="text-label-sm text-outline">Hoy</span>
            </div>
            <div class="text-headline-sm text-on-surface">S/ {{ number_format($totalAmountToday, 2) }}</div>
            <div class="text-body-sm text-on-surface-variant mt-1">Monto total (hoy)</div>
        </div>

        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-primary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">calendar_month</span>
                </div>
                <span class="text-label-sm text-outline">Mes</span>
            </div>
            <div class="text-headline-sm text-on-surface">S/ {{ number_format($acceptedMonth->total_sum, 2) }}</div>
            <div class="text-body-sm text-on-surface-variant mt-1">{{ $totalInvoicesMonth }} comprobantes este mes</div>
        </div>

        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-tertiary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-tertiary">percent</span>
                </div>
                <span class="text-label-sm text-outline">RMT</span>
            </div>
            <div class="text-headline-sm text-on-surface">S/ {{ number_format($acceptedMonth->subtotal_sum * 0.01, 2) }}</div>
            <div class="text-body-sm text-on-surface-variant mt-1">Impuesto a la Renta (1%)</div>
        </div>
    </div>

    @if($reservasPendientes > 0)
        <a href="{{ route('invoices.index') }}"
           class="flex items-center gap-3 bg-secondary-fixed rounded-xl border border-secondary-fixed px-5 py-4 shadow-sm hover:opacity-95 transition-opacity">
            <span class="material-symbols-outlined text-secondary text-2xl">event_available</span>
            <div class="flex-1">
                <p class="text-body-md font-bold text-secondary">Tienes {{ $reservasPendientes }} reserva(s) pendiente(s) de girar</p>
                <p class="text-body-sm text-on-surface-variant">Convierte tus ventas registradas en boletas o facturas electrónicas.</p>
            </div>
            <span class="material-symbols-outlined text-secondary">arrow_forward</span>
        </a>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-secondary text-lg">check_circle</span>
                <h3 class="text-body-md font-semibold text-on-surface">Aceptados por SUNAT</h3>
            </div>
            <div class="text-headline-md text-secondary">{{ $sunatAccepted }}</div>
            <div class="mt-2 w-full bg-surface-container rounded-full h-2">
                <div class="bg-secondary h-2 rounded-full" style="width: {{ $sunatAccepted + $sunatPending + $sunatRejected > 0 ? round($sunatAccepted / ($sunatAccepted + $sunatPending + $sunatRejected) * 100) : 0 }}%"></div>
            </div>
        </div>

        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-tertiary text-lg">pending</span>
                <h3 class="text-body-md font-semibold text-on-surface">Pendientes</h3>
            </div>
            <div class="text-headline-md text-tertiary">{{ $sunatPending }}</div>
            <div class="mt-2 w-full bg-surface-container rounded-full h-2">
                <div class="bg-tertiary h-2 rounded-full" style="width: {{ $sunatAccepted + $sunatPending + $sunatRejected > 0 ? round($sunatPending / ($sunatAccepted + $sunatPending + $sunatRejected) * 100) : 0 }}%"></div>
            </div>
        </div>

        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-error text-lg">error</span>
                <h3 class="text-body-md font-semibold text-on-surface">Rechazados / Errores</h3>
            </div>
            <div class="text-headline-md text-error">{{ $sunatRejected }}</div>
            <div class="mt-2 w-full bg-surface-container rounded-full h-2">
                <div class="bg-error h-2 rounded-full" style="width: {{ $sunatAccepted + $sunatPending + $sunatRejected > 0 ? round($sunatRejected / ($sunatAccepted + $sunatPending + $sunatRejected) * 100) : 0 }}%"></div>
            </div>
        </div>
    </div>

    <div class="bg-surface p-card-padding rounded-xl border border-outline-variant shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-body-md font-semibold text-on-surface">Ventas Mensuales</h3>
            <span class="text-label-sm text-outline">{{ now()->format('F Y') }}</span>
        </div>
        <div class="h-48 flex items-center justify-center bg-surface-container-low rounded-xl border border-dashed border-outline-variant">
            <div class="text-center">
                <span class="material-symbols-outlined text-outline text-4xl">bar_chart</span>
                <p class="text-body-sm text-on-surface-variant mt-2">Gráfico de facturación mensual</p>
            </div>
        </div>
    </div>

    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg">description</span>
                <h3 class="text-body-md font-semibold text-on-surface">Comprobantes Recientes</h3>
            </div>
            <a href="{{ route('invoices.index') }}" class="text-body-sm text-primary hover:text-primary-700 font-medium">Ver todos</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">#</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Comprobante</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Cliente</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Tipo</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Fecha</th>
                        <th class="px-5 py-3 text-right text-label-md text-on-surface-variant uppercase tracking-wider">Total</th>
                        <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50">
                    @forelse($recentInvoices as $inv)
                        <tr class="data-table-row">
                            <td class="px-5 py-3 text-body-sm text-on-surface-variant">{{ $inv->number }}</td>
                            <td class="px-5 py-3">
                                <span class="text-body-sm font-medium text-on-surface">{{ $inv->full_number }}</span>
                            </td>
                            <td class="px-5 py-3 text-body-sm text-on-surface-variant">{{ $inv->client?->name ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="status-badge {{ $inv->is_reservation ? 'badge-reserva' : ($inv->invoice_type === 'F' ? 'badge-aceptado' : 'badge-pendiente') }}">
                                    {{ $inv->is_reservation ? 'Reserva' : ($inv->invoice_type === 'F' ? 'Factura' : 'Boleta') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-body-sm text-on-surface-variant">{{ $inv->issue_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-body-sm font-medium text-on-surface text-right">S/ {{ number_format($inv->total, 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($inv->sunat_status === 'accepted')
                                    <span class="status-badge badge-aceptado">Aceptado</span>
                                @elseif($inv->sunat_status === 'rejected')
                                    <span class="status-badge badge-rechazado">Rechazado</span>
                                @elseif($inv->sunat_status === 'error')
                                    <span class="status-badge badge-rechazado">Error</span>
                                @else
                                    <span class="status-badge badge-pendiente">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    @if($inv->pdf_path)
                                        <a href="{{ route('invoices.pdf', $inv) }}" target="_blank"
                                           class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-primary-fixed/30 hover:text-primary transition-colors"
                                           title="Descargar PDF">
                                            <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('invoices.pdf.regenerate', $inv) }}"
                                       class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-high transition-colors"
                                       title="Regenerar PDF">
                                        <span class="material-symbols-outlined text-[18px]">refresh</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <span class="material-symbols-outlined text-outline text-4xl">receipt_long</span>
                                <p class="text-body-sm text-on-surface-variant mt-2">No hay comprobantes recientes</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
