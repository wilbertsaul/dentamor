<div class="space-y-6 max-w-3xl">
    <form wire:submit="save">
        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm mb-6">
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
                    <label class="text-label-md text-on-surface-variant">Código de establecimiento</label>
                    <input type="text" wire:model="sunat_establishment_code" maxlength="4" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_establishment_code') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-on-surface-variant">Debe existir en la ficha RUC. El principal es 0000.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="text-label-md text-on-surface-variant">Dirección (domicilio fiscal)</label>
                    <input type="text" wire:model="address" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('address') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Ubigeo (6 dígitos)</label>
                    <input type="text" wire:model="sunat_ubigeo" maxlength="6" placeholder="150101" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_ubigeo') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Urbanización</label>
                    <input type="text" wire:model="sunat_urbanization" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Departamento</label>
                    <input type="text" wire:model="sunat_department" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_department') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Provincia</label>
                    <input type="text" wire:model="sunat_province" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_province') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Distrito</label>
                    <input type="text" wire:model="sunat_district" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_district') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Correo electrónico</label>
                    <input type="email" wire:model="sunat_email" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_email') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Teléfono / celular</label>
                    <input type="text" wire:model="sunat_phone" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_phone') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="mt-3 text-xs text-on-surface-variant">
                La razón social y el domicilio deben coincidir exactamente con la ficha RUC; SUNAT rechaza el comprobante si no coinciden.
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-5">
                <span class="material-symbols-outlined text-primary text-xl">cloud</span>
                <h3 class="text-body-lg font-semibold text-on-surface">Credenciales SUNAT</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-label-md text-on-surface-variant">Usuario SOL principal</label>
                    <input type="text" wire:model="sunat_username" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('sunat_username') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Clave SOL principal</label>
                    <input type="password" wire:model="sunat_password" autocomplete="new-password"
                           placeholder="{{ $company?->sunat_password ? '•••••••• (guardada)' : '' }}"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    <p class="mt-1 text-xs text-on-surface-variant">Déjalo vacío para mantener la clave guardada.</p>
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Usuario SOL secundario</label>
                    <input type="text" wire:model="sunat_username_secondary" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    <p class="mt-1 text-xs text-on-surface-variant">Necesario para resumen diario y comunicación de baja.</p>
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Clave SOL secundaria</label>
                    <input type="password" wire:model="sunat_password_secondary" autocomplete="new-password"
                           placeholder="{{ $company?->sunat_password_secondary ? '•••••••• (guardada)' : '' }}"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                </div>
            </div>

            <div class="mb-4 px-4 py-3 rounded-xl bg-surface-container-low border border-outline-variant">
                <p class="text-body-sm font-semibold text-on-surface">¿Cómo se crea el usuario secundario?</p>
                <ol class="mt-1 space-y-0.5 text-body-sm text-on-surface-variant list-decimal list-inside">
                    <li>Entra a SUNAT Operaciones en Línea con la clave SOL principal.</li>
                    <li>Acceso Directo → Administración Usuarios → Crear Usuario.</li>
                    <li>Documento de identidad, usuario y clave del usuario secundario → Siguiente.</li>
                    <li>Asignar Perfiles → marca <strong>Comprobantes de pago</strong> → Siguiente.</li>
                    <li>Verifica y presiona Grabar.</li>
                </ol>
                <a href="https://orientacion.sunat.gob.pe/07-creacion-de-usuario-secundarios-de-la-clave-sol"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1 mt-2 text-body-sm text-primary hover:underline">
                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                    Guía oficial de SUNAT
                </a>
                @if($company?->secondarySolDuplicatesPrimary())
                    <p class="mt-2 text-body-sm text-error">El usuario secundario es igual al principal: SUNAT no lo acepta en producción.</p>
                @endif
                @if($company?->fallsBackToPrimarySol())
                    <p class="mt-2 text-body-sm text-on-surface-variant">Sin usuario secundario, en beta el resumen y la baja saldrán con el usuario principal.</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" wire:click="testSunat" wire:loading.attr="disabled"
                        class="px-4 py-2.5 border border-primary text-primary-dark rounded-xl text-body-sm font-semibold flex items-center gap-2 hover:opacity-80 transition-opacity disabled:opacity-50">
                    <span class="material-symbols-outlined text-base">wifi_tethering</span>
                    Probar conexión SUNAT
                </button>
                <span wire:loading wire:target="testSunat" class="text-body-sm text-on-surface-variant">Verificando credenciales con SUNAT...</span>
            </div>

            @if($sunatTest)
                <div class="mt-4 px-4 py-3 rounded-xl border flex items-start gap-2 {{ $sunatTest['ok'] ? 'bg-secondary-fixed/30 border-secondary/20 text-secondary' : 'bg-error-container/30 border-error/20 text-error' }}">
                    <span class="material-symbols-outlined text-lg">{{ $sunatTest['ok'] ? 'verified' : 'error' }}</span>
                    <div class="space-y-1">
                        <p class="text-body-sm font-semibold">{{ $sunatTest['message'] }}</p>
                        @foreach($sunatTest['errors'] as $error)
                            <p class="text-body-sm">• {{ $error }}</p>
                        @endforeach
                        @foreach($sunatTest['warnings'] as $warning)
                            <p class="text-body-sm">• {{ $warning }}</p>
                        @endforeach
                        @foreach($sunatTest['details'] as $detail)
                            <p class="text-body-sm opacity-90">{{ $detail }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="border-t border-outline-variant pt-4 mt-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-body-md font-medium text-on-surface">Modo Producción</div>
                        <div class="text-body-sm text-on-surface-variant mt-0.5">Envío real a SUNAT (beta para pruebas)</div>
                    </div>
                    <button type="button"
                            wire:click="$set('production', {{ $production ? 'false' : 'true' }})"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $production ? 'bg-gradient-to-r from-primary to-secondary' : 'bg-surface-container-highest' }}">
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

        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-1">
                <span class="material-symbols-outlined text-primary text-xl">chat</span>
                <h3 class="text-body-lg font-semibold text-on-surface">WhatsApp (Meta Cloud API)</h3>
            </div>
            <p class="text-body-sm text-on-surface-variant mb-5">
                Envía el comprobante por WhatsApp cuando SUNAT lo acepta. Requiere una plantilla aprobada en Meta.
            </p>

            <div class="border-b border-outline-variant pb-4 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-body-md font-medium text-on-surface">Envío automático</div>
                        <div class="text-body-sm text-on-surface-variant mt-0.5">Al aceptarse una boleta, factura o nota de crédito.</div>
                    </div>
                    <button type="button"
                            wire:click="$set('whatsapp_enabled', {{ $whatsapp_enabled ? 'false' : 'true' }})"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $whatsapp_enabled ? 'bg-gradient-to-r from-primary to-secondary' : 'bg-surface-container-highest' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $whatsapp_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-label-md text-on-surface-variant">Phone Number ID</label>
                    <input type="text" wire:model="whatsapp_phone_number_id"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('whatsapp_phone_number_id') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Business Account ID (opcional)</label>
                    <input type="text" wire:model="whatsapp_business_account_id"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('whatsapp_business_account_id') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-label-md text-on-surface-variant">Token de acceso permanente</label>
                    <input type="password" wire:model="whatsapp_token" autocomplete="new-password"
                           placeholder="{{ $company?->whatsapp_token ? '•••••••• (guardado)' : '' }}"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    <p class="mt-1 text-xs text-on-surface-variant">Se guarda cifrado. Déjalo vacío para conservar el actual.</p>
                    @error('whatsapp_token') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Versión de la API</label>
                    <input type="text" wire:model="whatsapp_api_version" maxlength="10"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1" placeholder="v21.0">
                    @error('whatsapp_api_version') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Idioma de la plantilla</label>
                    <input type="text" wire:model="whatsapp_template_lang" maxlength="10"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1" placeholder="es">
                    @error('whatsapp_template_lang') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-label-md text-on-surface-variant">Nombre de la plantilla</label>
                    <input type="text" wire:model="whatsapp_template_name" maxlength="100"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1" placeholder="comprobante_emitido">
                    <p class="mt-1 text-xs text-on-surface-variant">
                        Variables en orden: 1) nombre del cliente, 2) tipo y número, 3) total, 4) enlace al PDF.
                    </p>
                    @error('whatsapp_template_name') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[220px]">
                    <label class="text-label-md text-on-surface-variant">Teléfono de prueba</label>
                    <input type="text" wire:model="whatsapp_test_phone" placeholder="Ej: 987654321"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    @error('whatsapp_test_phone') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="testWhatsApp" wire:loading.attr="disabled"
                        class="px-4 py-3 border border-primary text-primary-dark rounded-xl text-body-sm font-semibold flex items-center gap-2 hover:opacity-80 transition-opacity disabled:opacity-50">
                    <span class="material-symbols-outlined text-base">send</span>
                    Probar envío
                </button>
            </div>
            <div wire:loading wire:target="testWhatsApp" class="mt-2 text-body-sm text-on-surface-variant">Enviando mensaje de prueba...</div>

            @if($whatsappTest)
                <div class="mt-4 px-4 py-3 rounded-xl border flex items-start gap-2 {{ $whatsappTest['ok'] ? 'bg-secondary-fixed/30 border-secondary/20 text-secondary' : 'bg-error-container/30 border-error/20 text-error' }}">
                    <span class="material-symbols-outlined text-lg">{{ $whatsappTest['ok'] ? 'verified' : 'error' }}</span>
                    <div class="space-y-1">
                        <p class="text-body-sm font-semibold">{{ $whatsappTest['message'] }}</p>
                        @if($whatsappTest['phone'])
                            <p class="text-body-sm opacity-90">Destino: {{ $whatsappTest['phone'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-5">
                <span class="material-symbols-outlined text-primary text-xl">badge</span>
                <h3 class="text-body-lg font-semibold text-on-surface">Certificado Digital</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="file" wire:model="certificate" accept=".pem,.crt,.cer,.pfx,.p12"
                           class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-fixed file:text-primary hover:file:bg-primary-container file:cursor-pointer">
                    @error('certificate') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="certificate" class="mt-2 text-body-sm text-on-surface-variant">Subiendo certificado...</div>
                    <p class="mt-1 text-xs text-on-surface-variant">Formatos: .pem, .crt, .cer, .pfx o .p12. La clave se elimina al convertir el certificado.</p>
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Contraseña del certificado (si tiene)</label>
                    <input type="password" wire:model="certificate_password" autocomplete="new-password"
                           class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1">
                    <p class="mt-1 text-xs text-on-surface-variant">Se usa solo para convertir el archivo; no se guarda.</p>
                </div>
            </div>
            <div class="mt-3 px-4 py-3 rounded-xl bg-surface-container-low border border-outline-variant">
                <p class="text-body-sm font-semibold text-on-surface">¿Cómo obtengo el certificado?</p>
                <ol class="mt-1 space-y-0.5 text-body-sm text-on-surface-variant list-decimal list-inside">
                    <li>SOL → Empresas → Comprobantes de Pago → Certificado Digital Tributario - CDT → Solicitar.</li>
                    <li>Acepta los términos y envía la solicitud.</li>
                    <li>En el Buzón SOL abre el mensaje "Emisión de Certificado Digital Tributario".</li>
                    <li>Crea tu clave privada (mínimo 8 caracteres alfanuméricos) y descarga <strong>certificado.p12</strong>.</li>
                </ol>
                <p class="mt-1 text-body-sm text-on-surface-variant">Si pierdes esa clave privada debes cancelar el CDT y solicitar uno nuevo.</p>
                <a href="https://cpe.sunat.gob.pe/certificado-digital" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1 mt-2 text-body-sm text-primary hover:underline">
                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                    Certificado Digital Tributario de SUNAT
                </a>
            </div>
            @if ($company?->certificate_path)
                <div class="mt-3 flex items-center gap-2 text-body-sm text-secondary">
                    <span class="material-symbols-outlined text-sm">verified</span>
                    Certificado actual: {{ basename($company->certificate_path) }}
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-1">
                <span class="material-symbols-outlined text-primary text-xl">tag</span>
                <h3 class="text-body-lg font-semibold text-on-surface">Series de comprobantes</h3>
            </div>
            <p class="text-body-sm text-on-surface-variant mb-5">
                Series con las que IziPay emite ante SUNAT. Deben estar libres en el otro sistema para evitar duplicados.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-label-md text-on-surface-variant">Boleta de venta</label>
                    <input type="text" wire:model="boleta_serie" maxlength="10" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1" placeholder="B002">
                    <p class="mt-1 text-xs text-on-surface-variant">Formato B + 3 dígitos. Próxima: <strong>{{ $boleta_numero }}</strong></p>
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Factura</label>
                    <input type="text" wire:model="factura_serie" maxlength="10" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1" placeholder="F002">
                    <p class="mt-1 text-xs text-on-surface-variant">Formato F + 3 dígitos. Próxima: <strong>{{ $factura_numero }}</strong></p>
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Nota de crédito de boleta</label>
                    <input type="text" wire:model="nc_boleta_serie" maxlength="10" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1" placeholder="BC02">
                    <p class="mt-1 text-xs text-on-surface-variant">Formato BC + 2 dígitos.</p>
                </div>
                <div>
                    <label class="text-label-md text-on-surface-variant">Nota de crédito de factura</label>
                    <input type="text" wire:model="nc_factura_serie" maxlength="10" class="w-full bg-surface-container-low border-none rounded-xl px-4 py-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none mt-1" placeholder="FC02">
                    <p class="mt-1 text-xs text-on-surface-variant">Formato FC + 2 dígitos.</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-on-surface-variant">
                Si el otro sistema sigue emitiendo boletas o facturas, usa series distintas (por ejemplo B002/F002) para no repetir numeración.
            </p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-primary text-on-primary rounded-xl flex items-center gap-2 hover:opacity-90 transition-opacity font-bold text-body-md shadow-md">
                <span class="material-symbols-outlined text-lg">save</span>
                Guardar Configuración
            </button>
        </div>
    </form>
</div>