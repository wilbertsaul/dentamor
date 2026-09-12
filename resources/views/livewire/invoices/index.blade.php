<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant flex items-center justify-between shadow-sm">
            <div>
                <p class="text-on-surface-variant text-label-md">Total del Mes</p>
                <h3 class="font-headline-md text-headline-md mt-1">S/ {{ number_format($totalMes, 2) }}</h3>
            </div>
            <div class="p-3 bg-primary-fixed rounded-lg text-primary">
                <span class="material-symbols-outlined">payments</span>
            </div>
        </div>
        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant flex items-center justify-between shadow-sm">
            <div>
                <p class="text-on-surface-variant text-label-md">Facturas Emitidas</p>
                <h3 class="font-headline-md text-headline-md mt-1">{{ $facturasEmitidas }}</h3>
            </div>
            <div class="p-3 bg-secondary-fixed rounded-lg text-secondary">
                <span class="material-symbols-outlined">receipt_long</span>
            </div>
        </div>
        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant flex items-center justify-between shadow-sm">
            <div>
                <p class="text-on-surface-variant text-label-md">Boletas Emitidas</p>
                <h3 class="font-headline-md text-headline-md mt-1">{{ $boletasEmitidas }}</h3>
            </div>
            <div class="p-3 bg-tertiary-fixed rounded-lg text-tertiary">
                <span class="material-symbols-outlined">receipt</span>
            </div>
        </div>
        <div class="bg-surface p-card-padding rounded-xl border border-outline-variant flex items-center justify-between shadow-sm">
            <div>
                <p class="text-on-surface-variant text-label-md">Por Validar</p>
                <h3 class="font-headline-md text-headline-md mt-1 text-tertiary">{{ str_pad($porValidar, 2, '0', STR_PAD_LEFT) }}</h3>
            </div>
            <div class="p-3 bg-error-container rounded-lg text-error">
                <span class="material-symbols-outlined">sync_problem</span>
            </div>
        </div>
    </div>

    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-4 border-b border-outline-variant flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-container-lowest">
            <div class="flex items-center gap-3">
                <div class="relative w-full md:w-80">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Buscar por cliente o número..."
                           class="w-full pl-10 pr-4 py-2 rounded-lg border border-outline-variant bg-surface-container-low focus:ring-2 focus:ring-primary focus:bg-surface outline-none transition-all text-body-md">
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($reservasPendientes > 0)
                    <a href="#reservas" class="px-4 py-2 bg-secondary-fixed text-secondary rounded-lg flex items-center gap-2 font-bold text-body-md shadow-sm hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined">event_available</span>
                        Reservas: {{ $reservasPendientes }}
                    </a>
                @endif
                <a href="{{ route('invoices.create') }}"
                   class="px-4 py-2 bg-primary text-white rounded-lg flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-md shadow-md">
                    <span class="material-symbols-outlined">add_circle</span>
                    Nuevo Comprobante
                </a>
            </div>
        </div>

        <div class="px-4 py-3 bg-surface border-b border-outline-variant flex gap-4 overflow-x-auto custom-scrollbar">
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[10px] font-bold text-outline uppercase">Tipo</label>
                <select wire:model.live="typeFilter"
                        class="text-body-md bg-transparent border-none p-0 focus:ring-0 cursor-pointer text-primary font-semibold">
                    <option value="">Todos los tipos</option>
                    <option value="F">Factura Electrónica</option>
                    <option value="B">Boleta de Venta</option>
                </select>
            </div>
            <div class="w-px h-10 bg-outline-variant"></div>
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[10px] font-bold text-outline uppercase">Estado SUNAT</label>
                <select wire:model.live="statusFilter"
                        class="text-body-md bg-transparent border-none p-0 focus:ring-0 cursor-pointer text-primary font-semibold">
                    <option value="">Cualquier estado</option>
                    <option value="accepted">Aceptado</option>
                    <option value="rejected">Rechazado</option>
                    <option value="error">Error</option>
                    <option value="pending">Pendiente</option>
                </select>
            </div>
            <div class="w-px h-10 bg-outline-variant"></div>
            <div class="flex flex-col gap-1 min-w-[200px]">
                <label class="text-[10px] font-bold text-outline uppercase">Rango de Fecha</label>
                <div class="flex items-center gap-2">
                    <input type="date" wire:model.live="dateFrom"
                           class="text-body-md bg-transparent border-none p-0 focus:ring-0 text-on-surface w-full">
                    <span class="text-outline">-</span>
                    <input type="date" wire:model.live="dateTo"
                           class="text-body-md bg-transparent border-none p-0 focus:ring-0 text-on-surface w-full">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant">
                    <tr>
                        <th class="px-6 py-4 text-label-md font-bold uppercase tracking-wider w-12">
                            <input type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary">
                        </th>
                        <th class="px-6 py-4 text-label-md font-bold uppercase tracking-wider">Tipo / Número</th>
                        <th class="px-6 py-4 text-label-md font-bold uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-4 text-label-md font-bold uppercase tracking-wider">Emisión</th>
                        <th class="px-6 py-4 text-label-md font-bold uppercase tracking-wider text-right">Total</th>
                        <th class="px-6 py-4 text-label-md font-bold uppercase tracking-wider text-center">Estado SUNAT</th>
                        <th class="px-6 py-4 text-label-md font-bold uppercase tracking-wider text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($invoices as $inv)
                        <tr class="data-table-row transition-colors">
                            <td class="px-6 py-4">
                                <input type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-body-md font-bold {{ $inv->is_reservation ? 'text-secondary' : 'text-primary' }}">
                                        {{ $inv->is_reservation ? 'Reserva' : ($inv->invoice_type === 'F' ? 'Factura' : 'Boleta') }} {{ $inv->full_number }}
                                    </span>
                                    <span class="text-[11px] text-outline">
                                        @if($inv->is_reservation) Pendiente de girar @elseif($inv->invoice_type === 'F') Operación Gravada @else Venta Minorista @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-body-md font-semibold text-on-surface">{{ $inv->client?->name ?? '-' }}</span>
                                    <span class="text-[11px] text-outline">
                                        {{ $inv->client ? $inv->client->doc_type . ': ' . $inv->client->doc_number : '' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-body-md text-on-surface-variant">{{ $inv->issue_date->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-body-md font-bold text-on-surface">S/ {{ number_format($inv->total, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($inv->is_reservation)
                                    <span class="status-badge badge-reserva">Reserva</span>
                                @elseif($inv->sunat_status === 'accepted')
                                    <span class="status-badge badge-aceptado">Aceptado</span>
                                @elseif($inv->sunat_status === 'rejected')
                                    <span class="status-badge badge-rechazado">Rechazado</span>
                                @elseif($inv->sunat_status === 'error')
                                    <span class="status-badge badge-rechazado">Error</span>
                                @else
                                    <span class="status-badge badge-pendiente">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    @if($inv->is_reservation)
                                        <a href="{{ route('invoices.girar', $inv) }}"
                                           class="px-3 py-1.5 bg-primary text-white rounded-lg text-label-md font-bold hover:opacity-90 transition-opacity flex items-center gap-1"
                                           title="Girar boleta o factura">
                                            <span class="material-symbols-outlined text-[18px]">send</span>
                                            Girar
                                        </a>
                                    @else
                                    @if($inv->pdf_path)
                                        <a href="{{ route('invoices.pdf', $inv) }}" target="_blank"
                                           class="p-1.5 hover:bg-surface-container-highest rounded text-on-surface-variant transition-colors"
                                           title="Ver PDF">
                                            <span class="material-symbols-outlined text-[20px]">picture_as_pdf</span>
                                        </a>
                                    @endif
                                    @if($inv->xml_path)
                                        <a href="{{ route('invoices.xml', $inv) }}"
                                           class="p-1.5 hover:bg-surface-container-highest rounded text-on-surface-variant transition-colors"
                                           title="Descargar XML">
                                            <span class="material-symbols-outlined text-[20px]">code</span>
                                        </a>
                                    @endif
                                    @if($inv->sunat_status === 'pending')
                                        <a href="{{ route('invoices.pdf.regenerate', $inv) }}"
                                           class="p-1.5 hover:bg-surface-container-highest rounded text-on-surface-variant transition-colors"
                                           title="Enviar a SUNAT">
                                            <span class="material-symbols-outlined text-[20px]">send</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('invoices.pdf.regenerate', $inv) }}"
                                       class="p-1.5 hover:bg-surface-container-highest rounded text-on-surface-variant transition-colors"
                                       title="Regenerar PDF">
                                        <span class="material-symbols-outlined text-[20px]">refresh</span>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline text-5xl">receipt_long</span>
                                <p class="text-body-md text-on-surface-variant mt-3">No se encontraron comprobantes</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-surface-container-lowest border-t border-outline-variant flex flex-col md:flex-row items-center justify-between gap-4">
            <span class="text-body-md text-on-surface-variant">Mostrando {{ $invoices->firstItem() ?? 0 }}-{{ $invoices->lastItem() ?? 0 }} de {{ $invoices->total() }} resultados</span>
            {{ $invoices->links() }}
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-surface p-6 rounded-xl border border-outline-variant shadow-sm h-80 relative flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h4 class="font-headline-sm text-headline-sm">Tendencia de Facturación</h4>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-primary"></span>
                    <span class="text-label-md text-on-surface-variant">Facturas</span>
                    <span class="w-3 h-3 rounded-full bg-secondary ml-2"></span>
                    <span class="text-label-md text-on-surface-variant">Boletas</span>
                </div>
            </div>
            <div class="flex-1 flex items-center justify-center border-2 border-dashed border-outline-variant rounded-xl bg-surface-container-low">
                <div class="text-center">
                    <span class="material-symbols-outlined text-outline text-4xl">bar_chart</span>
                    <p class="text-on-surface-variant italic font-body-md mt-2">Gráfico de facturación mensual</p>
                </div>
            </div>
        </div>

        <div class="bg-surface p-6 rounded-xl border border-outline-variant shadow-sm h-80 flex flex-col">
            <h4 class="font-headline-sm text-headline-sm mb-4">SUNAT Status</h4>
            <div class="flex-1 space-y-4">
                @php
                    $totalSunat = $facturasEmitidas + $boletasEmitidas + $porValidar;
                    $pctAccepted = $totalSunat > 0 ? round(($totalSunat - $porValidar) / $totalSunat * 100, 1) : 0;
                    $pctPending = $totalSunat > 0 ? round($porValidar / $totalSunat * 100, 1) : 0;
                @endphp
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-label-md">
                        <span class="text-on-surface-variant">Aceptados ({{ $pctAccepted }}%)</span>
                        <span class="font-bold text-secondary">{{ $totalSunat - $porValidar }}</span>
                    </div>
                    <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                        <div class="bg-secondary h-full rounded-full" style="width: {{ $pctAccepted }}%"></div>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-label-md">
                        <span class="text-on-surface-variant">Pendientes ({{ $pctPending }}%)</span>
                        <span class="font-bold text-tertiary">{{ $porValidar }}</span>
                    </div>
                    <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                        <div class="bg-tertiary h-full rounded-full" style="width: {{ $pctPending }}%"></div>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-label-md">
                        <span class="text-on-surface-variant">Rechazados</span>
                        <span class="font-bold text-error">0</span>
                    </div>
                    <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                        <div class="bg-error h-full rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <a href="{{ route('dashboard') }}"
               class="mt-4 w-full py-2 bg-surface-container-high rounded-lg text-primary font-bold text-body-md hover:bg-primary hover:text-white transition-all text-center">
                Ver Reporte Detallado
            </a>
        </div>
    </div>
</div>
