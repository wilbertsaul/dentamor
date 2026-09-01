<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="relative w-full sm:w-80">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar cliente..."
                   class="w-full pl-10 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-body-md text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary outline-none transition-all">
        </div>
        <button wire:click="create" class="shrink-0 px-5 py-3 bg-primary text-on-primary rounded-xl flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-md shadow-md">
            <span class="material-symbols-outlined text-lg">person_add</span>
            Nuevo Cliente
        </button>
    </div>

    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Doc.</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">N° Documento</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Nombre</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Email</th>
                        <th class="px-5 py-3 text-left text-label-md text-on-surface-variant uppercase tracking-wider">Teléfono</th>
                        <th class="px-5 py-3 text-center text-label-md text-on-surface-variant uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50">
                    @forelse($clients as $client)
                        <tr class="data-table-row">
                            <td class="px-5 py-3.5">
                                <span class="status-badge bg-surface-container text-on-surface-variant">{{ $client->doc_type }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-body-sm text-on-surface-variant font-mono">{{ $client->doc_number }}</td>
                            <td class="px-5 py-3.5 text-body-sm font-medium text-on-surface">{{ $client->name }}</td>
                            <td class="px-5 py-3.5 text-body-sm text-on-surface-variant">{{ $client->email ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-body-sm text-on-surface-variant">{{ $client->phone ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="edit({{ $client->id }})"
                                            class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-primary-fixed/30 hover:text-primary transition-colors"
                                            title="Editar">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button wire:click="delete({{ $client->id }})" wire:confirm="¿Eliminar este cliente?"
                                            class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-error-container/30 hover:text-error transition-colors"
                                            title="Eliminar">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <span class="material-symbols-outlined text-outline text-5xl">people</span>
                                <p class="text-body-sm text-on-surface-variant mt-3">No se encontraron clientes</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $clients->links() }}</div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
            <div class="fixed inset-0 bg-on-surface/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-xl shadow-lg w-full max-w-lg mx-4 p-6 z-10 border border-outline-variant">
                <div class="flex items-center gap-2 mb-5">
                    <span class="material-symbols-outlined text-primary text-xl">{{ $editMode ? 'edit' : 'person_add' }}</span>
                    <h3 class="text-body-lg font-semibold text-on-surface">{{ $editMode ? 'Editar Cliente' : 'Nuevo Cliente' }}</h3>
                </div>
                <form wire:submit="save">
                    <div class="space-y-4">
                        <div>
                            <label class="text-label-md text-on-surface-variant">Tipo Documento</label>
                            <select wire:model="doc_type" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">CE</option>
                            </select>
                            @error('doc_type') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-label-md text-on-surface-variant">N° Documento</label>
                            <input type="text" wire:model="doc_number" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                            @error('doc_number') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-label-md text-on-surface-variant">Nombre</label>
                            <input type="text" wire:model="name" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                            @error('name') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-label-md text-on-surface-variant">Email</label>
                                <input type="email" wire:model="email" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                                @error('email') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-label-md text-on-surface-variant">Teléfono</label>
                                <input type="text" wire:model="phone" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                                @error('phone') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="text-label-md text-on-surface-variant">Dirección</label>
                            <textarea wire:model="address" rows="2" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1 resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="px-5 py-2.5 bg-surface-container-high text-on-surface rounded-xl border border-outline-variant hover:bg-surface-container-highest transition-colors font-semibold text-body-md">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary text-on-primary rounded-xl hover:opacity-90 transition-opacity font-bold text-body-md shadow-md">
                            {{ $editMode ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
