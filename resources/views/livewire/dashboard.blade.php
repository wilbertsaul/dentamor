<div class="space-y-6 max-w-7xl mx-auto">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white p-4 lg:p-5 rounded-2xl border border-outline-variant shadow-sm">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-extrabold font-headline-md text-on-surface tracking-tight">Dashboard Ejecutivo</h1>
                <span class="inline-flex items-center gap-1 bg-primary/10 text-primary-dark font-bold text-[11px] px-2.5 py-0.5 rounded-full border border-primary/20">
                    <span class="material-symbols-outlined text-[13px]">history</span> Histórico Habilitado
                </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-0.5">Resumen integral de facturación electrónica y servicios odontológicos Dentamor</p>
        </div>

        <div class="flex items-center gap-2.5 bg-surface-container-low border border-outline-variant rounded-xl px-3.5 py-2">
            <span class="material-symbols-outlined text-primary text-xl">calendar_month</span>
            <div class="text-left pr-2">
                <p class="text-[9px] uppercase font-bold text-on-surface-variant tracking-wider leading-none">Periodo actual</p>
                <p class="text-xs font-bold text-on-surface leading-tight mt-0.5">{{ now()->translatedFormat('F Y') }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-outline-variant p-5 rounded-2xl shadow-sm hover:shadow-md transition-all hover:border-primary/50 relative overflow-hidden group">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary to-secondary opacity-80"></div>
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Facturación Periodo</span>
                        <span class="text-[9px] bg-primary/10 text-primary-dark font-bold px-1.5 py-0.5 rounded">{{ now()->translatedFormat('M Y') }}</span>
                    </div>
                    <h2 class="text-2xl font-extrabold font-headline-md text-primary-dark">S/ {{ number_format($monthSum, 2) }}</h2>
                    <div class="flex flex-col gap-0.5 pt-1">
                        <div class="flex items-center gap-1 text-xs {{ $monthVariation >= 0 ? 'text-emerald-600' : 'text-error' }} font-semibold">
                            <span class="material-symbols-outlined text-[15px]">{{ $monthVariation >= 0 ? 'trending_up' : 'trending_down' }}</span>
                            <span>{{ $monthVariation >= 0 ? '+' : '' }}{{ number_format($monthVariation, 1) }}% vs mes anterior</span>
                        </div>
                        <span class="text-[10px] text-on-surface-variant font-medium">{{ $previousMonthLabel }}: S/ {{ number_format($previousMonthSum ?? 0, 2) }} • Hoy: S/ {{ number_format($totalAmountToday, 2) }}</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary-dark flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">payments</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-outline-variant p-5 rounded-2xl shadow-sm hover:shadow-md transition-all hover:border-primary/50 relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Comprobantes Emitidos</span>
                    <h2 class="text-2xl font-extrabold font-headline-md text-on-surface">{{ $totalInvoicesMonth }} doc.</h2>
                    <div class="flex flex-col gap-0.5 pt-1">
                        <div class="flex items-center gap-1 text-xs text-primary font-semibold">
                            <span class="material-symbols-outlined text-[15px]">verified</span>
                            <span>{{ $sunatAccepted + $sunatPending + $sunatRejected > 0 ? number_format($sunatAccepted / ($sunatAccepted + $sunatPending + $sunatRejected) * 100, 1) : 0 }}% Validados SUNAT</span>
                        </div>
                        <span class="text-[10px] text-on-surface-variant font-medium">{{ $totalInvoicesToday }} emitidos hoy</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-primary-container text-primary-dark flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">receipt_long</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-outline-variant p-5 rounded-2xl shadow-sm hover:shadow-md transition-all hover:border-amber-400/50 relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Pendientes de Girar</span>
                    <h2 class="text-2xl font-extrabold font-headline-md text-tertiary">S/ {{ number_format($reservasPendientesMonto, 2) }}</h2>
                    <div class="flex flex-col gap-0.5 pt-1">
                        <div class="flex items-center gap-1 text-xs text-amber-600 font-semibold">
                            <span class="material-symbols-outlined text-[15px]">hourglass_top</span>
                            <span>{{ $reservasPendientes }} reserva(s) pendiente(s)</span>
                        </div>
                        <span class="text-[10px] text-on-surface-variant font-medium">Conviértelas en boletas o facturas</span>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-tertiary-container text-tertiary flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-outline-variant p-5 rounded-2xl shadow-sm hover:shadow-md transition-all hover:border-primary/50 relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">OSE / SUNAT Estado</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-lg font-extrabold font-headline-md text-emerald-600">En Línea</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                    <p class="text-xs text-on-surface-variant pt-1 font-medium">Última sinc: hace instantes</p>
                    <span class="text-[10px] text-secondary font-semibold">CDR recuperables vía SUNAT</span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">sync_saved_locally</span>
                </div>
            </div>
        </div>
    </div>

    @if($reservasPendientes > 0)
        <a href="{{ route('invoices.index') }}"
           class="flex items-center gap-3 bg-primary-container rounded-2xl border border-primary/20 px-5 py-4 shadow-sm hover:shadow-md transition-all">
            <span class="material-symbols-outlined text-primary-dark text-2xl">event_available</span>
            <div class="flex-1">
                <p class="text-body-md font-bold text-primary-dark">Tienes {{ $reservasPendientes }} reserva(s) pendiente(s) de girar</p>
                <p class="text-body-sm text-on-surface-variant">Convierte tus ventas registradas en boletas o facturas electrónicas.</p>
            </div>
            <span class="material-symbols-outlined text-primary-dark">arrow_forward</span>
        </a>
    @endif

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 lg:col-span-8 bg-white border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-base font-headline-md text-on-surface">Facturación y Ventas</h3>
                        <span class="text-[10px] bg-primary/10 text-primary-dark font-semibold px-2 py-0.5 rounded-md border border-primary/20">Últimos 30 días</span>
                    </div>
                    <p class="text-xs text-on-surface-variant">Ingresos por servicios odontológicos emitidos</p>
                </div>
                <span class="text-xs font-bold text-primary-dark">Máx: S/ {{ number_format($chartMax, 2) }}</span>
            </div>

            <div class="h-64 bg-surface-container-low rounded-xl border border-outline-variant/60 p-5 flex flex-col justify-between relative overflow-hidden">
                <div class="w-full flex-1 flex items-end justify-between gap-1 pt-6 pb-2 px-1">
                    @foreach($chartDays as $day)
                        @php
                            $height = $day['total'] > 0 ? max(4, round($day['total'] / $chartMax * 100)) : 1.5;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1 group">
                            <div class="w-full flex items-end justify-center h-full">
                                <div class="w-2 {{ $day['is_today'] ? 'bg-gradient-to-t from-secondary to-primary shadow-sm' : 'bg-primary/40 group-hover:bg-primary/70' }} rounded-t-sm transition-colors"
                                     style="height: {{ $height }}%"
                                     title="{{ $day['full'] }}: S/ {{ number_format($day['total'], 2) }}"></div>
                            </div>
                            <span class="text-[9px] font-semibold {{ $day['is_today'] ? 'text-primary-dark font-bold' : 'text-on-surface-variant' }}">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-between pt-3 border-t border-outline-variant/80 text-[11px] text-on-surface-variant gap-2">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary"></span> Periodo actual ({{ now()->translatedFormat('F Y') }})</span>
                    <span class="font-semibold text-primary-dark">Hoy: S/ {{ number_format($totalAmountToday, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 bg-white border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-base font-headline-md text-on-surface">Validación SUNAT</h3>
                    <span class="text-[11px] font-semibold text-secondary bg-primary-container px-2.5 py-0.5 rounded-full">Mes en curso</span>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3.5 bg-emerald-50/70 rounded-xl border border-emerald-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-on-surface">Aceptados</p>
                                <p class="text-[10px] text-on-surface-variant">Con Constancia (CDR)</p>
                            </div>
                        </div>
                        <span class="text-xl font-extrabold font-headline-md text-emerald-700">{{ $sunatAccepted }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-amber-50/70 rounded-xl border border-amber-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">hourglass_empty</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-on-surface">Pendientes</p>
                                <p class="text-[10px] text-on-surface-variant">Por sincronizar OSE</p>
                            </div>
                        </div>
                        <span class="text-xl font-extrabold font-headline-md text-amber-700">{{ $sunatPending }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-red-50/70 rounded-xl border border-red-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">error_outline</span>
                            </div>
                            <div>
                                <p class="font-bold text-xs text-on-surface">Rechazados</p>
                                <p class="text-[10px] text-on-surface-variant">Requieren corrección</p>
                            </div>
                        </div>
                        <span class="text-xl font-extrabold font-headline-md text-red-600">{{ $sunatRejected }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-outline-variant">
                <div class="flex justify-between items-center text-xs mb-1.5">
                    <span class="font-semibold text-on-surface-variant">Tasa de aceptación</span>
                    <span class="font-bold text-primary-dark">{{ $sunatAccepted + $sunatPending + $sunatRejected > 0 ? number_format($sunatAccepted / ($sunatAccepted + $sunatPending + $sunatRejected) * 100, 1) : 0 }}%</span>
                </div>
                <div class="w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full" style="width: {{ $sunatAccepted + $sunatPending + $sunatRejected > 0 ? round($sunatAccepted / ($sunatAccepted + $sunatPending + $sunatRejected) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        <div class="col-span-12 bg-white border border-outline-variant rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-surface-container-low border-b border-outline-variant">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary-dark">receipt</span>
                    <div>
                        <h3 class="font-bold text-base font-headline-md text-on-surface">Comprobantes Recientes</h3>
                        <p class="text-xs text-on-surface-variant">Últimos documentos emitidos</p>
                    </div>
                </div>
                <a href="{{ route('invoices.index') }}" class="text-xs font-bold text-primary-dark hover:text-primary-hover flex items-center gap-1">
                    Ver todos
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-outline-variant text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                            <th class="px-6 py-3.5">Fecha</th>
                            <th class="px-6 py-3.5">Comprobante</th>
                            <th class="px-6 py-3.5">Cliente</th>
                            <th class="px-6 py-3.5">Tipo</th>
                            <th class="px-6 py-3.5 text-right">Monto</th>
                            <th class="px-6 py-3.5 text-center">Estado SUNAT</th>
                            <th class="px-6 py-3.5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-xs">
                        @forelse($recentInvoices as $inv)
                            <tr class="data-table-row transition-colors">
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <p class="font-bold text-on-surface">{{ $inv->issue_date->format('d M Y') }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $inv->issue_date->format('H:i') }}</p>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap font-mono-md font-semibold text-primary-dark">{{ $inv->full_number }}</td>
                                <td class="px-6 py-3.5">
                                    <p class="font-bold text-on-surface">{{ $inv->client?->name ?? 'CONSUMIDOR FINAL' }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $inv->client ? $inv->client->doc_type . ': ' . $inv->client->doc_number : '—' }}</p>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-primary-container text-secondary font-medium">
                                        <span class="material-symbols-outlined text-[14px]">dentistry</span>
                                        {{ $inv->is_reservation ? 'Reserva' : ($inv->invoice_type === 'F' ? 'Factura' : 'Boleta') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right whitespace-nowrap font-extrabold text-sm text-primary-dark font-headline-md">S/ {{ number_format($inv->total, 2) }}</td>
                                <td class="px-6 py-3.5 text-center">
                                    @if($inv->sunat_status === 'accepted')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aceptado
                                        </span>
                                    @elseif($inv->sunat_status === 'rejected' || $inv->sunat_status === 'error')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> {{ $inv->sunat_status === 'error' ? 'Error' : 'Rechazado' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pendiente
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($inv->is_reservation)
                                            <a href="{{ route('invoices.girar', $inv) }}"
                                               class="p-1 hover:bg-primary-container text-primary-dark rounded-md transition-colors" title="Girar">
                                                <span class="material-symbols-outlined text-lg">send</span>
                                            </a>
                                        @else
                                            @if($inv->pdf_path)
                                                <a href="{{ route('invoices.pdf', $inv) }}" target="_blank"
                                                   class="p-1 hover:bg-primary-container text-primary-dark rounded-md transition-colors" title="Ver Comprobante PDF">
                                                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('invoices.pdf.regenerate', $inv) }}"
                                               class="p-1 hover:bg-surface-container text-on-surface-variant rounded-md transition-colors" title="Regenerar PDF">
                                                <span class="material-symbols-outlined text-lg">refresh</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <span class="material-symbols-outlined text-outline text-5xl">receipt_long</span>
                                    <p class="text-body-md text-on-surface-variant mt-3">No hay comprobantes recientes</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3.5 flex items-center justify-between bg-surface-container-low border-t border-outline-variant">
                <p class="text-xs text-on-surface-variant font-medium">Mostrando <span class="font-semibold text-on-surface">{{ $recentInvoices->count() }}</span> comprobantes recientes</p>
                <a href="{{ route('invoices.index') }}" class="text-[11px] font-bold text-primary-dark hover:text-primary-hover">Ver historial completo</a>
            </div>
        </div>
    </div>
</div>
