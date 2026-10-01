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

    @if (session('importResult'))
        @php $result = session('importResult'); @endphp
        <div class="px-4 py-3 rounded-xl border {{ count($result['errors']) > 0 ? 'bg-error-container/30 border-error/20 text-error' : 'bg-secondary-fixed/30 border-secondary/20 text-secondary' }} flex items-start gap-2">
            <span class="material-symbols-outlined text-lg mt-0.5">{{ count($result['errors']) > 0 ? 'warning' : 'check_circle' }}</span>
            <div>
                <p class="font-semibold">Importación completa.</p>
                <p class="text-body-sm">Importados: {{ $result['imported'] }} | Errores: {{ count($result['errors']) }}</p>
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

    @if (session('error'))
        <div class="px-4 py-3 rounded-xl border bg-error-container/30 border-error/20 text-error flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">warning</span>
            <p class="text-body-sm">{{ session('error') }}</p>
        </div>
    @endif

    <div>
        <h1 class="text-headline-md text-on-surface">Facturar en Bloque</h1>
        <p class="text-body-md text-on-surface-variant mt-0.5">Genera comprobantes desde pagos pendientes o desde el Reporte de Abono de Izipay</p>
    </div>

    <div class="flex gap-1 border-b border-outline-variant">
        <button wire:click="setTab('pendientes')"
                class="px-4 py-2.5 text-body-sm font-semibold -mb-px border-b-2 transition-colors {{ $tab === 'pendientes' ? 'border-primary text-primary-dark' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
            Pagos pendientes ({{ $payments->count() }})
        </button>
        <button wire:click="setTab('excel')"
                class="px-4 py-2.5 text-body-sm font-semibold -mb-px border-b-2 transition-colors {{ $tab === 'excel' ? 'border-primary text-primary-dark' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
            Excel Izipay
            @if(!empty($rows)) <span class="ml-1 text-[10px] bg-primary/10 text-primary-dark px-1.5 py-0.5 rounded-full">{{ count($rows) }}</span> @endif
        </button>
    </div>

    @if($tab === 'excel')
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-outline-variant shadow-sm p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="font-bold text-body-md font-headline-md text-on-surface">Reporte de Abono de Izipay</h2>
                        <p class="text-body-sm text-on-surface-variant mt-0.5">Sube el .xlsx/.xls que exportas desde Izipay. Detectarás el POS desde la columna <span class="font-mono">CODIGO</span> y las filas con <span class="font-mono">IMPORTE > 0</span>.</p>
                    </div>
                    <button wire:click="resetExcel" class="px-4 py-2.5 border border-outline-variant text-on-surface-variant hover:bg-surface-container-low rounded-xl text-body-sm font-medium flex items-center gap-1.5 transition-colors">
                        <span class="material-symbols-outlined text-base">delete_sweep</span> Limpiar
                    </button>
                </div>

                <div class="mt-5 border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center hover:border-primary transition-colors">
                    <span class="material-symbols-outlined text-5xl text-primary mb-3 block">upload_file</span>
                    <p class="text-body-sm text-on-surface-variant mb-4">Formatos: .xlsx (Reporte de Abono)</p>
                    <input type="file" wire:model="file"
                           class="mx-auto text-body-sm file:mr-3 file:cursor-pointer file:bg-primary file:text-on-primary file:border-0 file:rounded-lg file:px-4 file:py-2 file:font-semibold">
                    @error('file')
                        <p class="mt-2 text-body-sm text-error">{{ $message }}</p>
                    @enderror
                    @if($file)
                        <p class="mt-3 text-body-sm font-medium text-on-surface">{{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 0) }} KB)</p>
                    @endif
                </div>

                @if($excelError)
                    <div class="mt-4 px-4 py-3 rounded-xl border bg-error-container/30 border-error/20 text-error flex items-start gap-2">
                        <span class="material-symbols-outlined text-lg">error</span>
                        <p class="text-body-sm">{{ $excelError }}</p>
                    </div>
                @endif
                @if($excelInfo)
                    <div class="mt-4 px-4 py-3 rounded-xl border bg-primary-fixed/20 border-primary/20 text-primary-dark flex items-start gap-2">
                        <span class="material-symbols-outlined text-lg">info</span>
                        <p class="text-body-sm">{{ $excelInfo }}</p>
                    </div>
                @endif

                <button wire:click="processUpload"
                        class="mt-4 px-6 py-3 bg-primary text-on-primary rounded-xl flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-md shadow-md disabled:opacity-50"
                        @if(!$file) disabled @endif>
                    <span class="material-symbols-outlined text-lg">tune</span>
                    Procesar archivo
                </button>
            </div>

            @if(!empty($rows))
                @php
                    $selectedRows = array_values(array_filter($rows, fn ($r) => !empty($r['selected'])));
                    $selectedTotal = array_sum(array_column($selectedRows, 'importe'));
                    $selectedCount = count($selectedRows);
                @endphp
                <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
                    <div class="border-b border-outline-variant p-5 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 text-body-sm text-on-surface-variant cursor-pointer">
                                <input type="checkbox" wire:click="toggleAllRows" class="w-4 h-4 text-primary border-outline rounded focus:ring-primary">
                                Seleccionar todo
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-body-sm text-on-surface-variant">Tipo:</span>
                                <select wire:model="defaultInvoiceType" class="text-body-sm bg-surface-container-low border border-outline-variant rounded-lg px-3 py-1.5 focus:ring-primary">
                                    <option value="B">Boleta</option>
                                    <option value="F">Factura</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-body-sm text-on-surface-variant">{{ $selectedCount }} seleccionadas • <span class="font-bold text-primary-dark">S/ {{ number_format($selectedTotal, 2) }}</span></span>
                            <button wire:click="importSelected"
                                    class="px-5 py-2.5 bg-primary text-on-primary rounded-xl flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-sm shadow-md disabled:opacity-50"
                                    @if($selectedCount === 0) disabled @endif>
                                <span class="material-symbols-outlined text-lg">download_done</span>
                                Importar a pagos ({{ $selectedCount }})
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-surface-container-low border-b border-outline-variant">
                                    <th class="px-4 py-3 text-center w-12"></th>
                                    <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Fecha / Hora</th>
                                    <th class="px-5 py-3 text-right text-label-md text-on-surface-variant uppercase tracking-wider">Importe</th>
                                    <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Voucher</th>
                                    <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Marca</th>
                                    <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Lote</th>
                                    <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Estado</th>
                                    <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Cliente</th>
                                    <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Tipo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/50">
                                @foreach($rows as $index => $row)
                                    <tr class="data-table-row {{ !empty($row['duplicate']) ? 'opacity-50' : '' }}">
                                        <td class="px-4 py-3 text-center">
                                            <input type="checkbox" wire:model="rows.{{ $index }}.selected"
                                                   class="w-4 h-4 text-primary border-outline rounded focus:ring-primary"
                                                   @if(!empty($row['duplicate'])) disabled @endif>
                                        </td>
                                        <td class="px-5 py-3 text-body-sm text-on-surface">{{ $row['fecha'] }} <span class="text-on-surface-variant">{{ $row['hora'] }}</span></td>
                                        <td class="px-5 py-3 text-body-sm font-semibold text-on-surface text-right">S/ {{ number_format($row['importe'], 2) }}</td>
                                        <td class="px-5 py-3 text-body-sm text-on-surface-variant font-mono">{{ $row['voucher'] ?: '-' }}</td>
                                        <td class="px-5 py-3">
                                            <span class="status-badge bg-surface-container text-on-surface-variant">{{ $row['marca'] ?: '-' }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-body-sm text-on-surface-variant font-mono">{{ $row['lote'] ?: '-' }}</td>
                                        <td class="px-5 py-3 text-center">
                                            @if(!empty($row['duplicate']))
                                                <span class="status-badge badge-error">Duplicado</span>
                                            @else
                                                <span class="status-badge {{ $row['estado'] === 'APROBADA' ? 'badge-aceptado' : 'badge-pendiente' }}">{{ $row['estado'] ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="relative min-w-[180px]">
                                                <div class="flex items-center gap-1.5 mb-1">
                                                    @if($row['client_name'])
                                                        <span class="text-body-sm font-medium text-on-surface truncate">{{ $row['client_name'] }}</span>
                                                        <button wire:click="clearClient({{ $index }})" type="button" title="Quitar cliente" class="text-on-surface-variant hover:text-error text-sm leading-none">×</button>
                                                    @else
                                                        <span class="text-body-sm text-on-surface-variant italic">Consumidor Final</span>
                                                    @endif
                                                </div>
                                                <input type="text" wire:model.live.debounce.400ms="rows.{{ $index }}.client_query"
                                                       placeholder="Buscar nombre o DNI/RUC"
                                                       class="w-full text-body-sm bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 placeholder:text-on-surface-variant focus:ring-primary">
                                                @if(!empty($row['client_results']))
                                                    <ul class="absolute z-20 mt-1 w-72 bg-white border border-outline-variant rounded-xl shadow-lg overflow-hidden">
                                                        @foreach($row['client_results'] as $result)
                                                            <li>
                                                                <button type="button" wire:click="selectClient({{ $index }}, {{ $result['id'] }})"
                                                                        class="w-full text-left px-3 py-2 hover:bg-surface-container-low transition-colors">
                                                                    <span class="text-body-sm text-on-surface">{{ $result['name'] }}</span>
                                                                    <span class="block text-xs text-on-surface-variant font-mono">{{ $result['doc_type'] }} {{ $result['doc_number'] }}</span>
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <select wire:model="rows.{{ $index }}.invoice_type" class="text-body-sm bg-surface-container-low border border-outline-variant rounded-lg px-2.5 py-1.5 focus:ring-primary">
                                                <option value="B">Boleta</option>
                                                <option value="F">Factura</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <p class="text-body-md text-on-surface-variant">Selecciona los pagos pendientes para generar comprobantes</p>
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
    @endif
</div>