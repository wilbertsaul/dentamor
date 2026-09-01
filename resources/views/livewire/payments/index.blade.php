<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('statusFilter', '')"
                    class="px-4 py-2 rounded-xl text-label-md border-2 transition-all
                           {{ $statusFilter === '' ? 'bg-primary text-on-primary border-primary' : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                Todos
            </button>
            <button wire:click="$set('statusFilter', 'pending')"
                    class="px-4 py-2 rounded-xl text-label-md border-2 transition-all
                           {{ $statusFilter === 'pending' ? 'bg-tertiary text-on-tertiary border-tertiary' : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                Pendientes
            </button>
            <button wire:click="$set('statusFilter', 'invoiced')"
                    class="px-4 py-2 rounded-xl text-label-md border-2 transition-all
                           {{ $statusFilter === 'invoiced' ? 'bg-secondary text-on-secondary border-secondary' : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                Facturados
            </button>
            <button wire:click="$set('statusFilter', 'skipped')"
                    class="px-4 py-2 rounded-xl text-label-md border-2 transition-all
                           {{ $statusFilter === 'skipped' ? 'bg-surface-container-highest text-on-surface border-surface-container-highest' : 'bg-transparent text-on-surface-variant border-outline-variant hover:bg-surface-container-low' }}">
                Omitidos
            </button>
        </div>
        <button wire:click="create" class="shrink-0 px-5 py-3 bg-primary text-on-primary rounded-xl flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-md shadow-md">
            <span class="material-symbols-outlined text-lg">add</span>
            Nuevo Pago
        </button>
    </div>

    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Cliente</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Doc.</th>
                        <th class="px-5 py-3 text-right text-label-md text-on-surface-variant uppercase tracking-wider">Monto</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Método</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Fecha</th>
                        <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50">
                    @forelse($payments as $payment)
                        <tr class="data-table-row">
                            <td class="px-5 py-3.5 text-body-sm font-medium text-on-surface">{{ $payment->client_name }}</td>
                            <td class="px-5 py-3.5 text-body-sm text-on-surface-variant font-mono">{{ $payment->client_doc ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-body-sm font-medium text-on-surface text-right">S/ {{ number_format($payment->amount, 2) }}</td>
                            <td class="px-5 py-3.5">
                                <span class="status-badge bg-surface-container text-on-surface-variant">{{ $payment->payment_method }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-body-sm text-on-surface-variant">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3.5 text-center">
                                @if($payment->status === 'pending')
                                    <span class="status-badge badge-pendiente">Pendiente</span>
                                @elseif($payment->status === 'invoiced')
                                    <span class="status-badge badge-aceptado">Facturado</span>
                                @else
                                    <span class="status-badge bg-surface-container text-on-surface-variant">Omitido</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-center gap-1">
                                    @if($payment->status === 'pending')
                                        <button wire:click="markSkipped({{ $payment->id }})"
                                                class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-tertiary-fixed/30 hover:text-tertiary transition-colors"
                                                title="Omitir">
                                            <span class="material-symbols-outlined text-[18px]">skip_next</span>
                                        </button>
                                    @endif
                                    <button wire:click="delete({{ $payment->id }})" wire:confirm="¿Eliminar este registro?"
                                            class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-error-container/30 hover:text-error transition-colors"
                                            title="Eliminar">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <span class="material-symbols-outlined text-outline text-5xl">payments</span>
                                <p class="text-body-sm text-on-surface-variant mt-3">No hay registros de pago</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
            <div class="fixed inset-0 bg-on-surface/40" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-surface rounded-xl shadow-lg w-full max-w-lg mx-4 p-6 z-10 border border-outline-variant">
                <div class="flex items-center gap-2 mb-5">
                    <span class="material-symbols-outlined text-primary text-xl">payments</span>
                    <h3 class="text-body-lg font-semibold text-on-surface">Nuevo Registro de Pago</h3>
                </div>
                <form wire:submit="save">
                    <div class="space-y-4">
                        <div>
                            <label class="text-label-md text-on-surface-variant">Nombre del Cliente</label>
                            <input type="text" wire:model="client_name" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                            @error('client_name') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-label-md text-on-surface-variant">N° Documento</label>
                                <input type="text" wire:model="client_doc" maxlength="15" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                            </div>
                            <div>
                                <label class="text-label-md text-on-surface-variant">Monto (S/)</label>
                                <input type="number" step="0.01" min="0" wire:model="amount" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                                @error('amount') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-label-md text-on-surface-variant">Método de Pago</label>
                                <select wire:model="payment_method" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                                    <option value="">Seleccionar</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Tarjeta">Tarjeta</option>
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Yape">Yape</option>
                                    <option value="Plin">Plin</option>
                                </select>
                                @error('payment_method') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-label-md text-on-surface-variant">Tipo Comprobante</label>
                                <select wire:model="invoice_type" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                                    <option value="B">Boleta</option>
                                    <option value="F">Factura</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-label-md text-on-surface-variant">Referencia</label>
                                <input type="text" wire:model="reference" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                            </div>
                            <div>
                                <label class="text-label-md text-on-surface-variant">Fecha de Pago</label>
                                <input type="datetime-local" wire:model="payment_date" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                            </div>
                        </div>
                        <div>
                            <label class="text-label-md text-on-surface-variant">Notas</label>
                            <textarea wire:model="notes" rows="2" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1 resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" wire:click="$set('showForm', false)"
                                class="px-5 py-2.5 bg-surface-container-high text-on-surface rounded-xl border border-outline-variant hover:bg-surface-container-highest transition-colors font-semibold text-body-md">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-bold text-body-md shadow-md">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
