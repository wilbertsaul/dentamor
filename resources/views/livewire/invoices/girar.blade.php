<div class="max-w-5xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row items-center gap-3">
        <a href="{{ route('invoices.index') }}" class="p-2 hover:bg-surface-container rounded-full text-on-surface-variant transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="text-headline-md text-on-surface">Girar Reserva</h1>
            <p class="text-body-md text-on-surface-variant">Convierte la reserva en una boleta o factura electrónica y envíala a SUNAT.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <div class="md:col-span-8 space-y-6">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 shadow-sm custom-shadow">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-secondary">event_available</span>
                    <h2 class="text-headline-sm font-headline-sm text-on-surface">Reserva</h2>
                </div>

                <div class="flex flex-col gap-1 mb-5">
                    <span class="text-body-lg font-bold text-on-surface">{{ $reservation->full_number }}</span>
                    <span class="text-body-md text-on-surface-variant">Reservada el {{ $reservation->reserved_at?->format('d M Y H:i') ?? $reservation->issue_date->format('d M Y') }}</span>
                    <span class="text-body-md text-on-surface-variant">Cliente: {{ $reservation->client?->name ?? 'Sin cliente (consumidor final)' }}</span>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-outline-variant text-label-md">
                                <th class="text-left py-3 px-2 text-on-surface-variant w-20">Cant.</th>
                                <th class="text-left py-3 px-2 text-on-surface-variant">Descripción</th>
                                <th class="text-right py-3 px-2 text-on-surface-variant w-32">P. Unit.</th>
                                <th class="text-right py-3 px-2 text-on-surface-variant w-32">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/50">
                            @foreach($reservation->items as $item)
                                <tr>
                                    <td class="py-3 px-2 text-body-md text-on-surface">{{ $item->quantity }}</td>
                                    <td class="py-3 px-2 text-body-md text-on-surface">{{ $item->description }}</td>
                                    <td class="py-3 px-2 text-right text-body-md text-on-surface">S/ {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="py-3 px-2 text-right text-body-md font-semibold text-on-surface">S/ {{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-outline-variant mt-4 pt-4 space-y-1 text-label-md">
                    <div class="flex justify-between text-on-surface-variant">
                        <span>Subtotal:</span>
                        <span class="font-semibold text-on-surface">S/ {{ number_format($reservation->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant">
                        <span>IGV (18%):</span>
                        <span class="font-semibold text-on-surface">S/ {{ number_format($reservation->igv, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <span class="font-bold text-on-surface text-body-lg">Total:</span>
                        <span class="text-headline-sm font-headline-sm text-on-surface">S/ {{ number_format($reservation->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:col-span-4 space-y-6">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 shadow-sm custom-shadow">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-primary">receipt_long</span>
                    <h2 class="text-headline-sm font-headline-sm text-on-surface">Girar como</h2>
                </div>

                <div>
                    <label class="text-label-md text-on-surface-variant block mb-1">Tipo de Comprobante</label>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('invoice_type', 'B')"
                                class="flex-1 py-3 text-label-md rounded-xl border-2 transition-all
                                       {{ $invoice_type === 'B'
                                          ? 'bg-tertiary text-on-tertiary border-tertiary shadow-sm'
                                          : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                            Boleta
                        </button>
                        <button type="button" wire:click="$set('invoice_type', 'F')"
                                class="flex-1 py-3 text-label-md rounded-xl border-2 transition-all
                                       {{ $invoice_type === 'F'
                                          ? 'bg-primary text-on-primary border-primary shadow-sm'
                                          : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                            Factura
                        </button>
                    </div>
                    @if($invoice_type === 'B' && (float)$reservation->total <= 700)
                        <p class="text-xs text-outline flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-[14px]">info</span>
                            Menor o igual a S/700: el DNI del cliente es opcional.
                        </p>
                    @elseif($invoice_type === 'B')
                        <p class="text-xs text-outline flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-[14px]">info</span>
                            Mayor a S/700: se requiere DNI/RUC del cliente.
                        </p>
                    @else
                        <p class="text-xs text-outline flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-[14px]">info</span>
                            Las facturas requieren RUC del cliente.
                        </p>
                    @endif
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 shadow-sm custom-shadow">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-primary">person_search</span>
                    <h2 class="text-headline-sm font-headline-sm text-on-surface">Cliente</h2>
                </div>

                <div>
                    <label class="text-label-md text-on-surface-variant block mb-1">RUC / DNI</label>
                    <div class="flex gap-2">
                        <input type="text" wire:model.live="searchDoc"
                               placeholder="{{ $invoice_type === 'F' ? 'RUC (11 dígitos)' : 'DNI o RUC' }}"
                               class="flex-1 px-4 py-3 bg-surface-container-low border-none rounded-xl text-body-md text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none transition-all">
                        <button type="button" wire:click="buscarCliente" wire:loading.attr="disabled"
                                class="px-4 py-3 bg-primary text-on-primary rounded-xl flex items-center justify-center hover:opacity-90 transition-opacity disabled:opacity-50">
                            <span class="material-symbols-outlined" wire:loading.remove wire:target="buscarCliente">search</span>
                            <span wire:loading wire:target="buscarCliente" class="animate-spin">
                                <span class="material-symbols-outlined">progress_activity</span>
                            </span>
                        </button>
                        @if($client_id)
                            <button type="button" wire:click="quitarCliente" title="Cambiar cliente"
                                    class="px-4 py-3 bg-surface-container-high text-on-surface rounded-xl flex items-center justify-center hover:bg-surface-container-highest transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 mt-4">
                    <div>
                        <label class="text-label-md text-on-surface-variant block mb-1">Razón Social / Nombres</label>
                        <input type="text" wire:model.blur="clientName" value="{{ $clientName }}"
                               placeholder="{{ $clientName ? '' : 'Autocompleta al buscar, o ingresa manualmente' }}"
                               {{ $client_id ? 'readonly' : '' }}
                               class="w-full px-4 py-3 bg-surface-container-low border-none rounded-xl text-body-md text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none transition-all {{ $client_id ? 'bg-surface-container-highest text-on-surface-variant' : '' }}">
                    </div>
                    <div>
                        <label class="text-label-md text-on-surface-variant block mb-1">Dirección</label>
                        <input type="text" wire:model.blur="clientAddress" value="{{ $clientAddress }}"
                               placeholder="{{ $clientAddress ? '' : 'Dirección registrada' }}"
                               {{ $client_id ? 'readonly' : '' }}
                               class="w-full px-4 py-3 bg-surface-container-low border-none rounded-xl text-body-md text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none transition-all {{ $client_id ? 'bg-surface-container-highest text-on-surface-variant' : '' }}">
                    </div>
                </div>

                @error('client_id')
                    <p class="text-sm text-error flex items-center gap-1 mt-2">
                        <span class="material-symbols-outlined text-[16px]">error</span>
                        {{ $message }}
                    </p>
                @enderror
                @error('clientDoc')
                    <p class="text-sm text-error flex items-center gap-1 mt-2">
                        <span class="material-symbols-outlined text-[16px]">error</span>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="space-y-3">
                <button type="button" wire:click="girar" wire:loading.attr="disabled"
                        x-on:click="window.__livewirePdfWin = window.open('', '_blank')"
                        class="w-full py-4 bg-primary text-on-primary rounded-xl font-bold text-body-lg shadow-md hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined" wire:loading.remove wire:target="girar">send</span>
                    <span wire:loading wire:target="girar" class="animate-spin">
                        <span class="material-symbols-outlined">progress_activity</span>
                    </span>
                    <span wire:loading.remove wire:target="girar">Girar {{ $invoice_type === 'F' ? 'Factura' : 'Boleta' }} (SUNAT)</span>
                    <span wire:loading wire:target="girar">Enviando a SUNAT...</span>
                </button>

                <a href="{{ route('invoices.index') }}"
                   class="w-full py-3 bg-transparent text-on-surface-variant rounded-xl font-semibold text-body-md hover:bg-surface-container-low transition-all flex items-center justify-center gap-2">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</div>
