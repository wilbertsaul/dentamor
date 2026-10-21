<div class="space-y-6">
    <div class="flex flex-col sm:flex-row flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-body-lg font-semibold text-on-surface">Comunicaciones a SUNAT</h2>
            <p class="text-body-sm text-on-surface-variant">Resumen diario (RC) y consulta de tickets</p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <label class="text-label-md text-on-surface-variant shrink-0">Fecha</label>
            <input type="date" wire:model="date"
                   class="w-full min-w-0 bg-surface-container-low border-none rounded-xl px-3 py-2 text-body-sm focus:ring-2 focus:ring-primary outline-none">
        </div>
    </div>

    @if(! $company)
        <div class="px-4 py-3 bg-error-container/30 border border-error/20 rounded-xl text-body-sm text-error">
            Configura la empresa antes de enviar comunicaciones.
        </div>
    @else
        @if(! $company->hasSecondarySol() && $company->production)
            <div class="px-4 py-3 bg-error-container/30 border border-error/20 rounded-xl text-body-sm text-error flex items-start gap-2">
                <span class="material-symbols-outlined text-sm shrink-0">warning</span>
                Falta el usuario o la clave SOL secundarios. SUNAT exige un segundo usuario para el resumen diario y la baja.
            </div>
        @elseif($usesPrimarySol)
            <div class="px-4 py-3 bg-tertiary-container/30 border border-tertiary/20 rounded-xl text-body-sm text-on-surface flex items-start gap-2">
                <span class="material-symbols-outlined text-sm shrink-0">info</span>
                Estás en beta y no hay usuario SOL secundario: el resumen se enviará con el usuario principal. En producción SUNAT lo rechaza.
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-outline-variant p-4 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row flex-wrap items-center justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h3 class="text-body-md font-semibold text-on-surface">Boletas pendientes de resumir</h3>
                    <p class="text-body-sm text-on-surface-variant">
                        {{ $pending->count() }} comprobante(s) del {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                        @if($pending->isNotEmpty())
                            · total {{ $pending->sum('total') }}
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="enviar" wire:loading.attr="disabled"
                        @disabled($pending->isEmpty() || (! $company->hasSecondarySol() && $company->production))
                        class="w-full sm:w-auto px-5 py-3 bg-primary text-on-primary rounded-xl flex items-center justify-center gap-2 font-bold text-body-sm shadow-md hover:opacity-90 transition-opacity disabled:opacity-50">
                    <span class="material-symbols-outlined text-lg">cloud_upload</span>
                    Generar y enviar resumen (RC)
                </button>
            </div>

            @if($pending->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-body-sm">
                        <thead>
                            <tr class="text-label-md text-on-surface-variant border-b border-outline-variant">
                                <th class="py-2">Comprobante</th>
                                <th class="py-2">Cliente</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @foreach($pending->take(50) as $invoice)
                                <tr>
                                    <td class="py-2 font-semibold">{{ $invoice->full_number }}</td>
                                    <td class="py-2 break-words">{{ $invoice->client?->name ?? 'Consumidor final' }}</td>
                                    <td class="py-2 text-right">{{ number_format((float) $invoice->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($pending->count() > 50)
                        <p class="text-body-sm text-on-surface-variant mt-2">... y {{ $pending->count() - 50 }} más.</p>
                    @endif
                </div>
            @else
                <p class="text-body-sm text-on-surface-variant">No hay boletas aceptadas sin resumir para esa fecha.</p>
            @endif

            @if($errorMessage)
                <div class="mt-4 px-4 py-3 bg-error-container/30 border border-error/20 rounded-xl text-body-sm text-error flex items-start gap-2">
                    <span class="material-symbols-outlined text-sm shrink-0">error</span>{{ $errorMessage }}
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-outline-variant p-4 sm:p-6 shadow-sm">
            <h3 class="text-body-md font-semibold text-on-surface mb-3">Últimas comunicaciones</h3>
            @if($operations->isEmpty())
                <p class="text-body-sm text-on-surface-variant">Aún no se han enviado resúmenes ni bajas.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-body-sm">
                        <thead>
                            <tr class="text-label-md text-on-surface-variant border-b border-outline-variant">
                                <th class="py-2">Referencia</th>
                                <th class="py-2">Tipo</th>
                                <th class="py-2">Docs</th>
                                <th class="py-2">Estado</th>
                                <th class="py-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @foreach($operations as $operation)
                                <tr>
                                    <td class="py-2 font-mono text-xs">{{ $operation->reference }}</td>
                                    <td class="py-2">{{ $operation->typeLabel() }}</td>
                                    <td class="py-2">{{ $operation->documents_count }}</td>
                                    <td class="py-2">
                                        {{ \App\Services\Sunat\ResponseMapper::label($operation->status) }}
                                        @if($operation->code)
                                            <span class="text-on-surface-variant">({{ $operation->code }})</span>
                                        @endif
                                        @if($operation->ticket)
                                            <span class="text-outline text-xs">ticket {{ $operation->ticket }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right">
                                        @if($operation->ticket && in_array($operation->status, ['sent', 'processing'], true))
                                            <button type="button" wire:click="consultar('{{ $operation->reference }}')"
                                                    class="px-3 py-1.5 border border-primary text-primary rounded-lg text-label-md font-bold hover:opacity-80 transition-opacity">
                                                Consultar CDR
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>