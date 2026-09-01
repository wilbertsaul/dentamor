<div class="space-y-6 max-w-3xl">
    <form wire:submit="save">
        <div class="bg-surface rounded-xl border border-outline-variant p-6 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-5">
                <span class="material-symbols-outlined text-primary text-xl">business</span>
                <h3 class="text-body-lg font-semibold text-on-surface">Datos de la Empresa</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-label-md text-on-surface-variant">RUC</label>
                    <input type="text" wire:model="ruc" maxlength="11" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('ruc') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Razón Social</label>
                    <input type="text" wire:model="business_name" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('business_name') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Nombre Comercial</label>
                    <input type="text" wire:model="commercial_name" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Dirección</label>
                    <input type="text" wire:model="address" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                </div>
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-outline-variant p-6 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-5">
                <span class="material-symbols-outlined text-primary text-xl">cloud</span>
                <h3 class="text-body-lg font-semibold text-on-surface">Credenciales SUNAT</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-label-md text-on-surface-variant">Usuario SOL</label>
                    <input type="text" wire:model="sunat_username" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Clave SOL</label>
                    <input type="password" wire:model="sunat_password" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                </div>
            </div>

            <div class="border-t border-outline-variant pt-4 mt-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-body-md font-medium text-on-surface">Modo Producción</div>
                        <div class="text-body-sm text-on-surface-variant mt-0.5">Envío real a SUNAT (beta para pruebas)</div>
                    </div>
                    <button type="button"
                            wire:click="$set('production', {{ $production ? 'false' : 'true' }})"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $production ? 'bg-primary' : 'bg-surface-container-highest' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $production ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                @if($production)
                    <div class="mt-3 flex items-center gap-2 px-3 py-2 bg-error-container/30 border border-error/20 rounded-xl">
                        <span class="material-symbols-outlined text-error text-sm">warning</span>
                        <span class="text-body-sm text-error">Modo producción activo. Los comprobantes se enviarán a SUNAT real.</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-outline-variant p-6 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-5">
                <span class="material-symbols-outlined text-primary text-xl">badge</span>
                <h3 class="text-body-lg font-semibold text-on-surface">Certificado Digital</h3>
            </div>
            <div>
                <input type="file" wire:model="certificate" accept=".pem,.crt,.cer"
                       class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-fixed file:text-primary hover:file:bg-primary-container file:cursor-pointer">
                @error('certificate') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                <div wire:loading wire:target="certificate" class="mt-2 text-body-sm text-on-surface-variant">Subiendo certificado...</div>
                @if ($company?->certificate_path)
                    <div class="mt-3 flex items-center gap-2 text-body-sm text-secondary">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        Certificado actual: {{ basename($company->certificate_path) }}
                    </div>
                @endif
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-primary text-on-primary rounded-xl flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-md shadow-md">
                <span class="material-symbols-outlined text-lg">save</span>
                Guardar Configuración
            </button>
        </div>
    </form>
</div>
