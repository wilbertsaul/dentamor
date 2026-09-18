<div class="max-w-5xl mx-auto space-y-8">

    <div class="flex flex-col md:flex-row items-center gap-3">
        <a href="{{ route('invoices.index') }}" class="p-2 hover:bg-surface-container rounded-full text-on-surface-variant transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="text-headline-md text-on-surface">Nuevo Comprobante</h1>
            <p class="text-body-md text-on-surface-variant">Completa los datos para generar una factura o boleta electrónica.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <div class="md:col-span-8 space-y-6">
            <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm custom-shadow">
                <div class="flex items-center gap-3 mb-5">
                    <span class="material-symbols-outlined text-primary">person_search</span>
                    <h2 class="text-headline-sm font-headline-sm text-on-surface">Datos del Cliente</h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-label-md text-on-surface-variant block mb-1">Tipo de Comprobante</label>
                        <div class="flex gap-2">
                            <button type="button" wire:click="$set('invoice_type', 'B')"
                                    class="flex-1 py-3 text-label-md rounded-xl border-2 transition-all
                                           {{ $invoice_type === 'B'
                                              ? 'bg-tertiary text-on-tertiary border-tertiary shadow-sm'
                                              : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                                Boleta (B001)
                            </button>
                            <button type="button" wire:click="$set('invoice_type', 'F')"
                                    class="flex-1 py-3 text-label-md rounded-xl border-2 transition-all
                                           {{ $invoice_type === 'F'
                                              ? 'bg-primary text-on-primary border-primary shadow-sm'
                                              : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                                Factura (F001)
                            </button>
                            <button type="button" wire:click="$set('invoice_type', 'R')"
                                    class="flex-1 py-3 text-label-md rounded-xl border-2 transition-all
                                           {{ $invoice_type === 'R'
                                              ? 'bg-secondary text-on-secondary border-secondary shadow-sm'
                                              : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                                Reserva
                            </button>
                        </div>
                        @if($invoice_type === 'R')
                            <p class="text-xs text-secondary flex items-center gap-1 mt-1">
                                <span class="material-symbols-outlined text-[14px]">event_note</span>
                                Registra la venta sin girar comprobante. Para boletas menores a S/700 el DNI es opcional.
                            </p>
                        @endif
                    </div>

                    <div>
                        <label class="text-label-md text-on-surface-variant block mb-1">
                            RUC / DNI del Cliente
                        </label>
                        <div class="flex gap-2">
                            <input type="text" wire:model.live="searchDoc"
                                   placeholder="Ej: 20546789015 o 46789015"
                                   class="flex-1 px-4 py-3 bg-surface-container-low border-none rounded-xl text-body-md text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none transition-all">
                            <button type="button" wire:click="searchClient" wire:loading.attr="disabled"
                                    class="px-4 py-3 bg-primary text-on-primary rounded-xl flex items-center justify-center hover:opacity-90 transition-opacity disabled:opacity-50">
                                <span class="material-symbols-outlined" wire:loading.remove wire:target="searchClient">search</span>
                                <span wire:loading wire:target="searchClient" class="animate-spin">
                                    <span class="material-symbols-outlined">progress_activity</span>
                                </span>
                            </button>
                        </div>
                        @if(!config('services.jsonpe.key'))
                            <div class="text-xs text-outline flex items-center gap-1 mt-1">
                                <span class="material-symbols-outlined text-[14px]">info</span>
                                API de consulta no configurada. Contacte al administrador.
                            </div>
                        @endif
                        @if($client_id && $clientName)
                            <div class="flex items-center gap-2 text-xs {{ $clientSource === 'db' ? 'text-green-600' : 'text-blue-600' }} mt-1">
                                <span class="material-symbols-outlined text-[14px]">
                                    {{ $clientSource === 'db' ? 'check_circle' : 'cloud' }}
                                </span>
                                {{ $clientSource === 'db' ? 'Cargado desde base de datos (sin consumo)' : 'Cargado desde API (1 crédito)' }}
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md text-on-surface-variant block mb-1">Razón Social / Nombres</label>
                            <input type="text" wire:model.blur="clientName" value="{{ $clientName }}"
                                   placeholder="{{ $clientName ? '' : 'Se autocompleta al buscar' }}"
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
                        <p class="text-sm text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">error</span>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-outline-variant shadow-sm custom-shadow">
                <div class="flex items-center justify-between p-6 pb-0">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">table_rows</span>
                        <h2 class="text-headline-sm font-headline-sm text-on-surface">Detalle de Items</h2>
                    </div>
                    <button type="button" wire:click="addItem"
                            class="px-4 py-2 bg-surface-container-high rounded-xl text-primary text-label-md hover:bg-primary-container transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Agregar
                    </button>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b border-outline-variant text-label-md">
                                    <th class="text-left py-3 px-2 text-on-surface-variant w-20">Cant.</th>
                                    <th class="text-left py-3 px-2 text-on-surface-variant">Descripción</th>
                                    <th class="text-right py-3 px-2 text-on-surface-variant w-32">P. Unit.</th>
                                    <th class="text-right py-3 px-2 text-on-surface-variant w-32">Total</th>
                                    <th class="w-12"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body" class="divide-y divide-outline-variant/50">
                                @foreach($items as $index => $item)
                                    <tr wire:key="item-{{ $index }}-{{ $item['service_id'] ?? '' }}" class="group hover:bg-surface-container-low/50 transition-colors">
                                        <td class="py-3 px-2 align-top">
                                            <div class="flex items-center gap-1 bg-surface-container-low rounded-xl px-2">
                                                <button type="button" wire:click="$set('items.{{ $index }}.quantity', max(1, (int){{ $item['quantity'] }} - 1))"
                                                        class="p-1 text-on-surface-variant hover:text-primary transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">remove</span>
                                                </button>
                                                <input type="text" inputmode="numeric" wire:model.live="items.{{ $index }}.quantity"
                                                       class="w-12 text-center bg-transparent border-none py-2 text-body-md text-on-surface focus:ring-0 outline-none">
                                                <button type="button" wire:click="$set('items.{{ $index }}.quantity', (int){{ $item['quantity'] }} + 1)"
                                                        class="p-1 text-on-surface-variant hover:text-primary transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">add</span>
                                                </button>
                                            </div>
                                            @error("items.{$index}.quantity") <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="py-3 px-2 align-top">
                                            <x-brand-select
                                                :options="$services->map(fn($s) => ['id' => (string) $s->id, 'code' => $s->code, 'label' => $s->name])->values()->toArray()"
                                                :selected="(string) ($item['service_id'] ?? '')"
                                                wire-set-key="items.{{ $index }}.service_id"
                                                placeholder="Seleccionar servicio..."
                                                :searchable="true"
                                                :clearable="true"
                                            />
                                            <input type="text" wire:model="items.{{ $index }}.description" value="{{ $item['description'] }}"
                                                   placeholder="Descripción del servicio..."
                                                   class="w-full bg-transparent border-none px-3 py-1 text-body-sm text-on-surface-variant placeholder:text-outline focus:ring-0 outline-none">
                                            @error("items.{$index}.description") <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="py-3 px-2 align-top">
                                            <div class="flex items-center justify-end">
                                                <span class="text-on-surface-variant mr-1">S/</span>
                                                <input type="text" inputmode="decimal" wire:model.live="items.{{ $index }}.unit_price" value="{{ $item['unit_price'] }}"
                                                       class="w-24 text-right bg-surface-container-low border-none rounded-xl px-3 py-2.5 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none">
                                            </div>
                                            @error("items.{$index}.unit_price") <p class="text-xs text-error mt-1 text-right">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="py-3 px-2 align-top text-right text-body-md font-semibold text-on-surface whitespace-nowrap">
                                            S/ {{ number_format((float)($item['unit_price']??0) * (int)($item['quantity']??0), 2) }}
                                        </td>
                                        <td class="py-3 px-2 align-top text-center">
                                            @if(count($items) > 1)
                                                <button type="button" wire:click="removeItem({{ $index }})"
                                                        class="p-1.5 text-outline hover:text-error hover:bg-error-container/30 rounded-full transition-colors opacity-0 group-hover:opacity-100">
                                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @error('items')
                        <div class="mt-4 px-4 py-3 bg-error-container/30 rounded-xl text-sm text-error flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">error</span>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="md:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm custom-shadow relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary-fixed/40 rounded-full blur-3xl -mr-10 -mt-10"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-secondary-fixed/40 rounded-full blur-3xl -ml-5 -mb-5"></div>

                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="material-symbols-outlined text-on-surface">calculate</span>
                        <h2 class="text-headline-sm font-headline-sm text-on-surface">Resumen</h2>
                    </div>

                    <div class="space-y-3 text-label-md">
                        <div class="flex justify-between text-on-surface-variant">
                            <span>Subtotal:</span>
                            <span id="gen-subtotal" class="font-semibold text-on-surface">S/ {{ number_format($this->calcularSubtotal(), 2) }}</span>
                        </div>
                        <div class="flex justify-between text-on-surface-variant">
                            <span>IGV (18%):</span>
                            <span id="gen-igv" class="font-semibold text-on-surface">S/ {{ number_format($this->calcularIgv(), 2) }}</span>
                        </div>
                        <div class="border-t border-outline-variant pt-3 mt-3 flex justify-between items-center">
                            <span class="font-bold text-on-surface text-body-lg">Total:</span>
                            <span id="gen-total" class="text-headline-sm font-headline-sm text-on-surface">S/ {{ number_format($this->calcularTotal(), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm custom-shadow">
                <div class="flex items-center gap-3 mb-5">
                    <span class="material-symbols-outlined text-on-surface">receipt_long</span>
                    <h2 class="text-headline-sm font-headline-sm text-on-surface">Condiciones</h2>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-surface-container-low rounded-xl p-2">
                            <span class="text-label-sm text-outline font-bold uppercase block mb-1">Serie</span>
                            <span class="text-body-md font-semibold text-on-surface">{{ $invoice_type === 'F' ? 'F001' : ($invoice_type === 'R' ? 'RVA' : 'B001') }}</span>
                        </div>
                        <div class="bg-surface-container-low rounded-xl p-2">
                            <span class="text-label-sm text-outline font-bold uppercase block mb-1">Fecha</span>
                            <input type="date" wire:model="issue_date"
                                   class="bg-transparent border-none p-0 text-body-md font-semibold text-on-surface focus:ring-0 outline-none w-full">
                        </div>
                    </div>

                    <div>
                        <label class="text-label-sm text-outline font-bold uppercase block mb-1">Moneda</label>
                        <x-brand-select
                                :options="[['id' => 'PEN', 'label' => 'Soles (PEN)'], ['id' => 'USD', 'label' => 'Dólares (USD)']]"
                                :selected="$currency"
                                wire-set-key="currency"
                                placeholder="Seleccionar moneda"
                            />
                    </div>

                    <div>
                        <label class="text-label-sm text-outline font-bold uppercase block mb-1">Forma de Pago</label>
                        <div class="flex gap-2">
                            <button type="button" wire:click="$set('payment_condition', 'contado')"
                                    class="flex-1 py-2.5 text-label-md rounded-xl border-2 transition-all
                                           {{ $payment_condition === 'contado'
                                              ? 'bg-secondary text-on-secondary border-secondary'
                                              : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                                Contado
                            </button>
                            <button type="button" wire:click="$set('payment_condition', 'credito')"
                                    class="flex-1 py-2.5 text-label-md rounded-xl border-2 transition-all
                                           {{ $payment_condition === 'credito'
                                              ? 'bg-secondary text-on-secondary border-secondary'
                                              : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                                Crédito
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="text-label-sm text-outline font-bold uppercase block mb-1">Observaciones</label>
                        <textarea wire:model="observations" rows="2"
                                  class="w-full bg-surface-container-low border-none rounded-xl px-3 py-2.5 text-body-md text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none transition-all resize-none"
                                  placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                        x-cloak
                        x-show="$wire.invoice_type === 'R'"
                        class="w-full py-4 bg-secondary text-on-secondary rounded-xl font-bold text-body-lg shadow-md hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined" wire:loading.remove wire:target="save">event_available</span>
                    <span wire:loading wire:target="save" class="animate-spin">
                        <span class="material-symbols-outlined">progress_activity</span>
                    </span>
                    <span wire:loading.remove wire:target="save">Guardar Reserva</span>
                    <span wire:loading wire:target="save">Guardando...</span>
                </button>

                <button type="button" wire:click="save" wire:loading.attr="disabled"
                        x-cloak
                        x-show="$wire.invoice_type !== 'R'"
                        x-on:click="window.__livewirePdfWin = window.open('', '_blank')"
                        class="w-full py-4 bg-primary text-on-primary rounded-xl font-bold text-body-lg shadow-md hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined" wire:loading.remove wire:target="save">send</span>
                    <span wire:loading wire:target="save" class="animate-spin">
                        <span class="material-symbols-outlined">progress_activity</span>
                    </span>
                    <span wire:loading.remove wire:target="save">Emitir {{ $invoice_type === 'F' ? 'Factura' : 'Boleta' }} (SUNAT)</span>
                    <span wire:loading wire:target="save">Enviando a SUNAT...</span>
                </button>

                <button type="button" wire:click="saveDraft" wire:loading.attr="disabled"
                        x-cloak
                        x-show="$wire.invoice_type !== 'R'"
                        class="w-full py-3 bg-surface-container-high text-on-surface rounded-xl font-semibold text-body-md border border-outline-variant hover:bg-surface-container-highest active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined" wire:loading.remove wire:target="saveDraft">save</span>
                    <span wire:loading wire:target="saveDraft" class="animate-spin">
                        <span class="material-symbols-outlined">progress_activity</span>
                    </span>
                    <span wire:loading.remove wire:target="saveDraft">Guardar Borrador</span>
                    <span wire:loading wire:target="saveDraft">Guardando...</span>
                </button>

                <a href="{{ route('invoices.index') }}"
                   class="w-full py-3 bg-transparent text-on-surface-variant rounded-xl font-semibold text-body-md hover:bg-surface-container-low transition-all flex items-center justify-center gap-2">
                    Cancelar Operación
                </a>
            </div>
        </div>
    </div>
</div>
