<div class="max-w-2xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('invoices.index') }}" class="p-2 rounded-xl hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-body-lg font-semibold text-on-surface">Comunicación de baja</h2>
            <p class="text-body-sm text-on-surface-variant">Comprobante: <span class="font-semibold">{{ $invoice->full_number }}</span></p>
        </div>
    </div>

    <div class="px-4 py-3 bg-surface-container-low rounded-xl border border-outline-variant text-body-sm text-on-surface-variant">
        @if($esBoleta)
            Esta boleta se da de baja mediante un <span class="font-semibold">resumen diario (RC)</span> con la
            línea en estado <span class="font-semibold">anulado</span>, usando el <span class="font-semibold">SOL secundario</span>.
        @else
            La baja genera una comunicación <span class="font-semibold">RA</span> que requiere el
            <span class="font-semibold">SOL secundario</span>.
        @endif
        El comprobante queda anulado: ya no se puede reenviar.
        Si solo quieres reducir el importe, usa una nota de crédito.
    </div>

    <form wire:submit="anular" class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm space-y-4">
        <div>
            <label class="text-label-md text-on-surface-variant">
                Motivo de la baja{{ $esBoleta ? ' (opcional)' : '' }}
                @if(! $esBoleta) <span class="text-error">*</span> @endif
                (máx. 100 caracteres)
            </label>
            <textarea wire:model="motivo" rows="3" maxlength="100"
                      placeholder="Ej.: Error en la emisión del comprobante"
                      class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1"></textarea>
            @error('motivo') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
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
                    class="px-6 py-3 bg-error text-on-error rounded-xl flex items-center gap-2 font-bold text-body-md shadow-md hover:opacity-90 transition-opacity disabled:opacity-60">
                <span class="material-symbols-outlined text-lg">cancel</span>
                Anular comprobante
            </button>
        </div>
    </form>
</div>