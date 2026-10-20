<div class="max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('invoices.index') }}" class="p-2 rounded-xl hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-body-lg font-semibold text-on-surface">Nota de crédito</h2>
            <p class="text-body-sm text-on-surface-variant">
                Comprobante afectado: <span class="font-semibold">{{ $invoice->full_number }}</span>
                @if($invoice->client) · {{ $invoice->client->name }} @endif
            </p>
        </div>
    </div>

    <div class="px-4 py-3 bg-surface-container-low rounded-xl border border-outline-variant text-body-sm text-on-surface-variant">
        Se emitirá la serie <span class="font-semibold">{{ $serie }}</span> con el tipo 07, vinculada a
        <span class="font-semibold">{{ $invoice->full_number }}</span>.
        Reduce el importe pendiente de factura o boleta y no requiere anulación.
    </div>

    <form wire:submit="emitir" class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm space-y-4">
        <div>
            <label class="text-label-md text-on-surface-variant">Motivo (campo_desMotivo)</label>
            <input type="text" wire:model="motivo" maxlength="500"
                   class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
            @error('motivo') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-label-md text-on-surface-variant">WhatsApp (opcional)</label>
                <input type="text" wire:model.blur="customer_phone" placeholder="Ej: 987654321"
                       class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                <p class="text-xs text-outline mt-1">Se envía al aceptarse en SUNAT.</p>
                @error('customer_phone') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-label-md text-on-surface-variant">Correo (opcional)</label>
                <input type="email" wire:model.blur="customer_email" placeholder="cliente@correo.com"
                       class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                @error('customer_email') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-label-md text-on-surface-variant border-b border-outline-variant">
                        <th class="py-2">Descripción</th>
                        <th class="py-2 w-28">Cantidad</th>
                        <th class="py-2 w-32">P. unitario</th>
                        <th class="py-2 w-28 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @foreach($items as $index => $item)
                        <tr>
                            <td class="py-2 pr-2">
                                <input type="text" wire:model="items.{{ $index }}.description"
                                       class="w-full bg-surface-container-low border-none rounded-lg px-3 py-2 text-body-sm focus:ring-2 focus:ring-primary outline-none">
                                @error("items.$index.description") <p class="text-xs text-error">{{ $message }}</p> @enderror
                            </td>
                            <td class="py-2 px-1">
                                <input type="number" step="0.01" min="0.01" wire:model="items.{{ $index }}.quantity"
                                       class="w-full bg-surface-container-low border-none rounded-lg px-3 py-2 text-body-sm text-right focus:ring-2 focus:ring-primary outline-none">
                                @error("items.$index.quantity") <p class="text-xs text-error">{{ $message }}</p> @enderror
                            </td>
                            <td class="py-2 px-1">
                                <input type="number" step="0.01" wire:model="items.{{ $index }}.unit_price"
                                       class="w-full bg-surface-container-low border-none rounded-lg px-3 py-2 text-body-sm text-right focus:ring-2 focus:ring-primary outline-none">
                            </td>
                            <td class="py-2 text-body-md text-right font-semibold">
                                {{ number_format((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end items-center gap-4 pt-2 border-t border-outline-variant">
            <span class="text-body-md text-on-surface-variant">Total nota:</span>
            <span class="text-title-md font-bold text-primary">{{ number_format($this->total(), 2) }}</span>
        </div>

        @if($errorMessage)
            <div class="px-4 py-3 bg-error-container/30 border border-error/20 rounded-xl text-body-sm text-error flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">error</span>{{ $errorMessage }}
            </div>
        @endif

        <div class="flex justify-end gap-3">
            <a href="{{ route('invoices.index') }}"
               class="px-5 py-3 border border-outline-variant rounded-xl text-body-sm font-semibold text-on-surface-variant hover:bg-surface-container-low transition-colors">
                Cancelar
            </a>
            <button type="submit" wire:loading.attr="disabled"
                    class="px-6 py-3 bg-primary text-on-primary rounded-xl flex items-center gap-2 font-bold text-body-md shadow-md hover:opacity-90 transition-opacity disabled:opacity-60">
                <span class="material-symbols-outlined text-lg" wire:loading.remove wire:target="emitir">publish</span>
                <span wire:loading wire:target="emitir">Enviando a SUNAT...</span>
                <span wire:loading.remove wire:target="emitir">Emitir nota de crédito</span>
            </button>
        </div>
    </form>
</div>