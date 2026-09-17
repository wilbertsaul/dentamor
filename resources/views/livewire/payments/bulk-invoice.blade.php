<div class="space-y-6">
    @if (session('bulkResult'))
        @php $result = session('bulkResult'); @endphp
        <div class="px-4 py-3 rounded-xl border {{ $result['error'] > 0 ? 'bg-error-container/30 border-error/20 text-error' : 'bg-secondary-fixed/30 border-secondary/20 text-secondary' }} flex items-start gap-2">
            <span class="material-symbols-outlined text-lg mt-0.5">{{ $result['error'] > 0 ? 'warning' : 'check_circle' }}</span>
            <div>
                <p class="font-semibold">Proceso completado.</p>
                <p class="text-body-sm">Facturados: {{ $result['success'] }} | Errores: {{ $result['error'] }}</p>
                @if(!empty($result['errors']))
                    <ul class="mt-2 text-body-sm list-disc list-inside">
                        @foreach($result['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-headline-md text-on-surface">Facturar en Bloque</h1>
            <p class="text-body-md text-on-surface-variant mt-0.5">Selecciona los pagos pendientes para generar comprobantes</p>
        </div>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-body-md text-on-surface-variant cursor-pointer">
                <input type="checkbox" wire:click="toggleAll"
                       {{ count($selected) === $payments->count() && $payments->count() > 0 ? 'checked' : '' }}
                       class="w-4 h-4 text-primary border-outline rounded focus:ring-primary">
                Seleccionar todo
            </label>
            <button wire:click="processSelected" class="px-5 py-3 bg-primary text-on-primary rounded-xl flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-md shadow-md disabled:opacity-50"
                    @if(count($selected) === 0) disabled @endif>
                <span class="material-symbols-outlined text-lg">send</span>
                Facturar ({{ count($selected) }})
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-4 py-3 text-center w-12"></th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Cliente</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Doc.</th>
                        <th class="px-5 py-3 text-right text-label-md text-on-surface-variant uppercase tracking-wider">Monto</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Método</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Fecha</th>
                        <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Tipo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50">
                    @forelse($payments as $payment)
                        <tr class="data-table-row {{ in_array($payment->id, $selected) ? 'bg-primary-fixed/10' : '' }}">
                            <td class="px-4 py-3.5 text-center">
                                <input type="checkbox" wire:model="selected" value="{{ $payment->id }}"
                                       class="w-4 h-4 text-primary border-outline rounded focus:ring-primary">
                            </td>
                            <td class="px-5 py-3.5 text-body-sm font-medium text-on-surface">{{ $payment->client_name }}</td>
                            <td class="px-5 py-3.5 text-body-sm text-on-surface-variant font-mono">{{ $payment->client_doc ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-body-sm font-medium text-on-surface text-right">S/ {{ number_format($payment->amount, 2) }}</td>
                            <td class="px-5 py-3.5">
                                <span class="status-badge bg-surface-container text-on-surface-variant">{{ $payment->payment_method }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-body-sm text-on-surface-variant">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="status-badge {{ $payment->invoice_type === 'F' ? 'badge-aceptado' : 'badge-pendiente' }}">
                                    {{ $payment->invoice_type === 'F' ? 'Factura' : 'Boleta' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <span class="material-symbols-outlined text-outline text-5xl">dynamic_feed</span>
                                <p class="text-body-sm text-on-surface-variant mt-3">No hay pagos pendientes de facturación</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
