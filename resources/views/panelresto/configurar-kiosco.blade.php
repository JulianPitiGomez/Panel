<div>
    <!-- ===== Personalización ===== -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-white">
                <i class="fas fa-pen-fancy mr-2"></i>Personalización
            </h2>
            <p class="text-sm text-white/80 mt-1">Textos personalizados del kiosco</p>
        </div>

        <div class="p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título de Productos Destacados</label>
                <input type="text"
                       wire:model="kioscoTituloDestacados"
                       class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-lg focus:ring-[#FFAF22] focus:border-[#FFAF22] text-sm"
                       placeholder="Ej: Productos Destacados, Nuestras Promos, Combos">
                <p class="text-xs text-gray-400 mt-1">Se muestra como título de la sección de productos destacados en el kiosco</p>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Opciones del checkout</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="kioscoPideNombre"
                               class="w-4 h-4 text-[#FFAF22] rounded focus:ring-[#FFAF22]">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Pedir nombre del cliente</span>
                            <p class="text-xs text-gray-400">Se muestra un campo para que el cliente ingrese su nombre</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="kioscoPideTelefono"
                               class="w-4 h-4 text-[#FFAF22] rounded focus:ring-[#FFAF22]">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Pedir teléfono del cliente</span>
                            <p class="text-xs text-gray-400">Se muestra un campo para que el cliente ingrese su teléfono</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="kioscoImprimeTicket"
                               class="w-4 h-4 text-[#FFAF22] rounded focus:ring-[#FFAF22]">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Imprimir ticket automáticamente</span>
                            <p class="text-xs text-gray-400">Se imprime un ticket al confirmar el pedido</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Colores del Kiosco ===== -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-white">
                <i class="fas fa-palette mr-2"></i>Colores del Kiosco
            </h2>
            <p class="text-sm text-white/80 mt-1">Personalizá los colores de la interfaz del kiosco</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Selectores de color -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="kioscoColorFondo"
                               class="w-10 h-10 rounded cursor-pointer border border-gray-300 p-0.5">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Color de Fondo</label>
                            <span class="text-xs text-gray-400">{{ $kioscoColorFondo }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="kioscoColorLetra"
                               class="w-10 h-10 rounded cursor-pointer border border-gray-300 p-0.5">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Color de Letra</label>
                            <span class="text-xs text-gray-400">{{ $kioscoColorLetra }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="kioscoColorBoton"
                               class="w-10 h-10 rounded cursor-pointer border border-gray-300 p-0.5">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Color de Botón</label>
                            <span class="text-xs text-gray-400">{{ $kioscoColorBoton }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="kioscoColorTextoBoton"
                               class="w-10 h-10 rounded cursor-pointer border border-gray-300 p-0.5">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Color de Texto del Botón</label>
                            <span class="text-xs text-gray-400">{{ $kioscoColorTextoBoton }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="kioscoColorPrecio"
                               class="w-10 h-10 rounded cursor-pointer border border-gray-300 p-0.5">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Color de Precio</label>
                            <span class="text-xs text-gray-400">{{ $kioscoColorPrecio }}</span>
                        </div>
                    </div>

                    <button type="button" wire:click="restaurarColores"
                            class="text-sm text-gray-500 hover:text-gray-700 underline mt-2">
                        <i class="fas fa-undo mr-1"></i> Restaurar colores por defecto
                    </button>
                </div>

                <!-- Preview -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-3 py-2 bg-gray-100 border-b border-gray-200">
                        <span class="text-xs font-medium text-gray-500">Vista previa</span>
                    </div>
                    <div class="p-4" style="background-color: {{ $kioscoColorFondo }};">
                        <!-- Mini nav -->
                        <div class="flex gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium"
                                  style="background-color: {{ $kioscoColorBoton }}; color: {{ $kioscoColorTextoBoton }};">
                                Hamburguesas
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium"
                                  style="background-color: {{ $kioscoColorBoton }}; color: {{ $kioscoColorTextoBoton }};">
                                Bebidas
                            </span>
                        </div>

                        <!-- Mini product card -->
                        <div class="bg-white rounded-lg shadow-sm p-3 mb-3">
                            <div class="flex gap-3">
                                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold" style="color: {{ $kioscoColorLetra }};">Producto ejemplo</h4>
                                    <p class="text-xs" style="color: {{ $kioscoColorLetra }}; opacity: 0.7;">Descripción del producto</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm font-bold" style="color: {{ $kioscoColorPrecio }};">$1.500</span>
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold"
                                              style="background-color: {{ $kioscoColorBoton }}; color: {{ $kioscoColorTextoBoton }};">
                                            Agregar
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mini footer -->
                        <div class="flex gap-2">
                            <span class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium text-center bg-gray-400 text-white">
                                Seguir Comprando
                            </span>
                            <span class="flex-[2] px-3 py-1.5 rounded-lg text-xs font-medium text-center"
                                  style="background-color: {{ $kioscoColorBoton }}; color: {{ $kioscoColorTextoBoton }};">
                                Ver Pedido
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Mercado Pago ===== -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6" x-data="{ mostrarGuia: @entangle('mostrarGuiaMP') }">
        <div class="px-6 py-4 bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">
                        <i class="fas fa-money-bill-wave mr-2"></i>Mercado Pago
                    </h2>
                    <p class="text-sm text-white/80 mt-1">Configurá la integración de cobros con Mercado Pago</p>
                </div>
                <button type="button" @click="mostrarGuia = !mostrarGuia"
                        class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-medium rounded-lg transition">
                    <i class="fas fa-question-circle mr-1"></i> Guía de configuración
                </button>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Resumen de configuración actual --}}
            @if($mpTokenValido && ($mpPosQrImage || $mpPointDeviceId || $mpTipoIntegracion === 'checkout_pro'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-green-800 mb-2"><i class="fas fa-check-circle mr-1"></i> Configuración activa</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                    <div><span class="text-gray-500">Cuenta:</span> <span class="font-medium text-gray-800">{{ $mpUserNombre }}</span></div>
                    <div><span class="text-gray-500">Tipo:</span>
                        <span class="font-medium text-gray-800">
                            @if($mpTipoIntegracion === 'qr_presencial') QR Presencial
                            @elseif($mpTipoIntegracion === 'point') Point / Lector
                            @else Checkout Pro
                            @endif
                        </span>
                    </div>
                    @if($mpTipoIntegracion === 'qr_presencial' && $mpPosExternalId)
                        <div><span class="text-gray-500">Sucursal:</span> <span class="font-medium text-gray-800">{{ $mpStoreExternalId }}</span></div>
                        <div><span class="text-gray-500">Caja:</span> <span class="font-medium text-gray-800">{{ $mpPosExternalId }}</span></div>
                    @elseif($mpTipoIntegracion === 'point' && $mpPointDeviceId)
                        <div class="sm:col-span-2"><span class="text-gray-500">Dispositivo:</span> <span class="font-medium text-gray-800">{{ $mpPointDeviceId }}</span></div>
                    @endif
                </div>
                @if($mpTipoIntegracion === 'qr_presencial' && $mpPosQrImage)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $mpPosQrImage }}" alt="QR" class="w-20 h-20 object-contain bg-white rounded border border-gray-200 p-1">
                        <span class="text-xs text-green-600">QR listo para usar en el kiosco</span>
                    </div>
                @endif
            </div>
            @elseif($mpTokenValido)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-amber-800"><i class="fas fa-exclamation-triangle mr-1"></i> Configuración incompleta</h4>
                <p class="text-xs text-amber-600 mt-1">Token validado ({{ $mpUserNombre }}), pero falta configurar
                    @if($mpTipoIntegracion === 'qr_presencial') la sucursal y caja
                    @elseif($mpTipoIntegracion === 'point') el dispositivo Point
                    @else la integración
                    @endif
                </p>
            </div>
            @endif

            {{-- Guía de configuración (modal colapsable) --}}
            <div x-show="mostrarGuia" x-transition x-cloak
                 class="bg-gray-50 border border-gray-200 rounded-lg p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800"><i class="fas fa-book mr-2 text-[#FFAF22]"></i>Guía paso a paso</h3>
                    <button type="button" @click="mostrarGuia = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>

                {{-- QR Presencial --}}
                <details class="group">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-[#FFAF22] transition">
                        <i class="fas fa-qrcode mr-1"></i> QR Presencial (Recomendado)
                    </summary>
                    <div class="mt-2 ml-5 text-xs text-gray-600 space-y-1">
                        <p><b>1.</b> Ingresá a <a href="https://www.mercadopago.com.ar/developers/panel/app" target="_blank" class="text-[#FFAF22] underline">mercadopago.com.ar/developers</a> con tu cuenta de MP.</p>
                        <p><b>2.</b> Creá una nueva aplicación (o usá una existente). Seleccioná "Pagos presenciales" como producto.</p>
                        <p><b>3.</b> En "Credenciales de producción", copiá el <b>Access Token</b>.</p>
                        <p><b>4.</b> Pegalo en el campo "Access Token" de abajo y hacé click en <b>"Validar Token"</b>.</p>
                        <p><b>5.</b> Seleccioná <b>"QR Presencial"</b> como tipo de integración.</p>
                        <p><b>6.</b> Hacé click en <b>"Crear Sucursal y Caja"</b>. Se generará un QR automáticamente.</p>
                        <p><b>7.</b> Ese QR se mostrará en el kiosco cuando el cliente elija pagar con MP. El cliente lo escanea con la app de MP y paga.</p>
                        <p class="mt-2 text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i> Esta integración no tiene comisión (funciona como transferencia).</p>
                    </div>
                </details>

                {{-- Point --}}
                <details class="group">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-[#FFAF22] transition">
                        <i class="fas fa-tablet-alt mr-1"></i> Point / Lector
                    </summary>
                    <div class="mt-2 ml-5 text-xs text-gray-600 space-y-1">
                        <p><b>1.</b> Necesitás un dispositivo Point (Smart, Plus, Mini, etc.) vinculado a tu cuenta de MP.</p>
                        <p><b>2.</b> Ingresá a <a href="https://www.mercadopago.com.ar/developers/panel/app" target="_blank" class="text-[#FFAF22] underline">mercadopago.com.ar/developers</a> y copiá el <b>Access Token</b> de producción.</p>
                        <p><b>3.</b> Pegalo en el campo "Access Token" y hacé click en <b>"Validar Token"</b>.</p>
                        <p><b>4.</b> Seleccioná <b>"Point / Lector"</b> como tipo de integración.</p>
                        <p><b>5.</b> Hacé click en <b>"Buscar dispositivos"</b>. Aparecerá tu lector en la lista.</p>
                        <p><b>6.</b> Seleccioná el dispositivo y opcionalmente probá la conexión.</p>
                        <p><b>7.</b> Cuando el cliente pague, el monto aparecerá en el lector y el cliente acerca su tarjeta o celular.</p>
                    </div>
                </details>

                {{-- Checkout Pro --}}
                <details class="group">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-[#FFAF22] transition">
                        <i class="fas fa-shopping-cart mr-1"></i> Checkout Pro
                    </summary>
                    <div class="mt-2 ml-5 text-xs text-gray-600 space-y-1">
                        <p><b>1.</b> Ingresá a <a href="https://www.mercadopago.com.ar/developers/panel/app" target="_blank" class="text-[#FFAF22] underline">mercadopago.com.ar/developers</a> y copiá el <b>Access Token</b>.</p>
                        <p><b>2.</b> Pegalo en el campo "Access Token" y hacé click en <b>"Validar Token"</b>.</p>
                        <p><b>3.</b> Seleccioná <b>"Checkout Pro"</b> como tipo de integración.</p>
                        <p><b>4.</b> No requiere configuración adicional. Se genera un QR de pago online para cada pedido.</p>
                        <p class="mt-2 text-amber-600 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Esta opción cobra comisión por transacción.</p>
                    </div>
                </details>

                {{-- Sandbox --}}
                <details class="group">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-[#FFAF22] transition">
                        <i class="fas fa-flask mr-1"></i> Modo de prueba (Sandbox)
                    </summary>
                    <div class="mt-2 ml-5 text-xs text-gray-600 space-y-1">
                        <p><b>1.</b> En <a href="https://www.mercadopago.com.ar/developers/panel/app" target="_blank" class="text-[#FFAF22] underline">mercadopago.com.ar/developers</a>, entrá a tu aplicación.</p>
                        <p><b>2.</b> Andá a <b>"Credenciales de prueba"</b> (no las de producción) y copiá el Access Token de test.</p>
                        <p><b>3.</b> Usá ese token en este panel para configurar todo en modo prueba.</p>
                        <p><b>4.</b> Para simular pagos, creá <b>usuarios de prueba</b> desde el <a href="https://www.mercadopago.com.ar/developers/panel/test-users" target="_blank" class="text-[#FFAF22] underline">panel de test users</a>.</p>
                        <p><b>5.</b> Instalá la app de Mercado Pago en un celular e ingresá con el usuario de prueba comprador.</p>
                        <p><b>6.</b> Escaneá el QR de prueba con ese usuario para simular un pago completo.</p>
                    </div>
                </details>
            </div>

            {{-- Access Token --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                <div class="flex gap-2">
                    <div x-data="{ mostrarToken: false }" class="relative flex-1">
                        <input :type="mostrarToken ? 'text' : 'password'"
                               wire:model="mpAccessToken"
                               placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm font-mono">
                        <button type="button" @click="mostrarToken = !mostrarToken"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <i :class="mostrarToken ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                    <button type="button" wire:click="validarToken"
                            wire:loading.attr="disabled" wire:target="validarToken"
                            class="px-4 py-2 bg-[#FFAF22] text-white text-sm font-medium rounded-lg hover:bg-[#FFAF22]/90 transition disabled:opacity-50 whitespace-nowrap">
                        <span wire:loading.remove wire:target="validarToken"><i class="fas fa-check-circle mr-1"></i> Validar Token</span>
                        <span wire:loading wire:target="validarToken"><i class="fas fa-spinner fa-spin mr-1"></i> Validando...</span>
                    </button>
                </div>
                @if($mpTokenValido === true)
                    <p class="text-xs text-green-600 mt-1"><i class="fas fa-check-circle mr-1"></i> Cuenta: {{ $mpUserNombre }} (ID: {{ $mpUserId }})</p>
                @elseif($mpTokenValido === false)
                    <p class="text-xs text-red-500 mt-1"><i class="fas fa-times-circle mr-1"></i> Token inválido</p>
                @endif
            </div>

            {{-- Mensaje de estado --}}
            @if($mpMensaje)
                <div class="px-3 py-2 rounded-lg text-sm {{ $mpMensajeTipo === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                    <i class="fas {{ $mpMensajeTipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-1"></i>
                    {{ $mpMensaje }}
                </div>
            @endif

            {{-- Tipo de integración --}}
            @if($mpTokenValido)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de integración</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- QR Presencial --}}
                    <label class="relative flex flex-col p-4 border-2 rounded-xl cursor-pointer transition
                        {{ $mpTipoIntegracion === 'qr_presencial' ? 'border-[#FFAF22] bg-[#FFAF22]/5' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" wire:model.live="mpTipoIntegracion" value="qr_presencial" class="sr-only">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $mpTipoIntegracion === 'qr_presencial' ? 'bg-[#FFAF22] text-white' : 'bg-gray-100 text-gray-500' }}">
                                <i class="fas fa-qrcode text-sm"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-800">QR Presencial</span>
                        </div>
                        <p class="text-xs text-gray-500">El cliente escanea un QR fijo con la app de MP</p>
                        <span class="mt-2 inline-block text-xs font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded-full w-fit">Sin comisión</span>
                    </label>

                    {{-- Point --}}
                    <label class="relative flex flex-col p-4 border-2 rounded-xl cursor-pointer transition
                        {{ $mpTipoIntegracion === 'point' ? 'border-[#FFAF22] bg-[#FFAF22]/5' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" wire:model.live="mpTipoIntegracion" value="point" class="sr-only">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $mpTipoIntegracion === 'point' ? 'bg-[#FFAF22] text-white' : 'bg-gray-100 text-gray-500' }}">
                                <i class="fas fa-tablet-alt text-sm"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-800">Point / Lector</span>
                        </div>
                        <p class="text-xs text-gray-500">Cobra con lector Point (tarjeta o NFC)</p>
                        <span class="mt-2 inline-block text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full w-fit">Requiere dispositivo</span>
                    </label>

                    {{-- Checkout Pro --}}
                    <label class="relative flex flex-col p-4 border-2 rounded-xl cursor-pointer transition
                        {{ $mpTipoIntegracion === 'checkout_pro' ? 'border-[#FFAF22] bg-[#FFAF22]/5' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" wire:model.live="mpTipoIntegracion" value="checkout_pro" class="sr-only">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $mpTipoIntegracion === 'checkout_pro' ? 'bg-[#FFAF22] text-white' : 'bg-gray-100 text-gray-500' }}">
                                <i class="fas fa-shopping-cart text-sm"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-800">Checkout Pro</span>
                        </div>
                        <p class="text-xs text-gray-500">QR online que redirige al checkout de MP</p>
                        <span class="mt-2 inline-block text-xs font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full w-fit">Con comisión</span>
                    </label>
                </div>
            </div>

            {{-- ===== Panel QR Presencial ===== --}}
            @if($mpTipoIntegracion === 'qr_presencial')
            <div class="border border-gray-200 rounded-lg p-5 space-y-4 bg-gray-50/50">
                <h4 class="text-sm font-semibold text-gray-700"><i class="fas fa-qrcode mr-2 text-[#FFAF22]"></i>Configuración QR Presencial</h4>

                @if($mpPosQrImage)
                    {{-- Estado: Configurado --}}
                    <div class="flex flex-col sm:flex-row items-start gap-4">
                        <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <img src="{{ $mpPosQrImage }}" alt="QR de la caja" class="w-40 h-40 object-contain">
                        </div>
                        <div class="space-y-2 text-sm">
                            <p class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i> QR configurado correctamente</p>
                            <p class="text-gray-600"><b>Sucursal:</b> {{ $mpStoreExternalId }}</p>
                            <p class="text-gray-600"><b>Caja:</b> {{ $mpPosExternalId }}</p>
                            <p class="text-xs text-gray-400 mt-2">Este QR se mostrará en el kiosco cuando el cliente elija pagar con Mercado Pago.</p>
                            <button type="button" wire:click="crearSucursalYCaja"
                                    wire:loading.attr="disabled" wire:target="crearSucursalYCaja"
                                    class="text-xs text-gray-500 hover:text-gray-700 underline">
                                <i class="fas fa-redo mr-1"></i> Regenerar sucursal y caja
                            </button>
                            <button type="button" wire:click="buscarExistentes"
                                    wire:loading.attr="disabled" wire:target="buscarExistentes"
                                    class="text-xs text-gray-500 hover:text-gray-700 underline ml-3">
                                <span wire:loading.remove wire:target="buscarExistentes"><i class="fas fa-search mr-1"></i> Buscar existentes en MP</span>
                                <span wire:loading wire:target="buscarExistentes"><i class="fas fa-spinner fa-spin mr-1"></i> Buscando...</span>
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Estado: No configurado - Formulario datos del local --}}
                    <div class="space-y-3">
                        <p class="text-sm text-gray-600">Completá los datos de tu local para crear la sucursal y caja en Mercado Pago.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre del local *</label>
                                <input type="text" wire:model="mpStoreName"
                                       placeholder="Ej: Mi Restaurante"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Calle</label>
                                <input type="text" wire:model="mpStoreStreetName"
                                       placeholder="Ej: Av. Corrientes"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Número</label>
                                <input type="text" wire:model="mpStoreStreetNumber"
                                       placeholder="Ej: 1234"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Ciudad</label>
                                <input type="text" wire:model="mpStoreCityName"
                                       placeholder="Ej: Buenos Aires"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Provincia</label>
                                <input type="text" wire:model="mpStoreStateName"
                                       placeholder="Ej: Buenos Aires"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Latitud *</label>
                                <input type="text" wire:model="mpStoreLatitude"
                                       placeholder="Ej: -34.6037"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Longitud *</label>
                                <input type="text" wire:model="mpStoreLongitude"
                                       placeholder="Ej: -58.3816"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i> La latitud y longitud son requeridas por Mercado Pago para cálculos de impuestos. Podés obtenerlas desde <a href="https://www.google.com/maps" target="_blank" class="text-[#FFAF22] underline">Google Maps</a> haciendo click derecho en tu ubicación.</p>

                        <div class="pt-2 flex flex-wrap gap-2">
                            <button type="button" wire:click="crearSucursalYCaja"
                                    wire:loading.attr="disabled" wire:target="crearSucursalYCaja"
                                    class="px-5 py-2.5 bg-[#FFAF22] text-white font-medium rounded-lg hover:bg-[#FFAF22]/90 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="crearSucursalYCaja"><i class="fas fa-plus-circle mr-2"></i> Crear Sucursal y Caja</span>
                                <span wire:loading wire:target="crearSucursalYCaja"><i class="fas fa-spinner fa-spin mr-2"></i> Creando...</span>
                            </button>
                            <button type="button" wire:click="buscarExistentes"
                                    wire:loading.attr="disabled" wire:target="buscarExistentes"
                                    class="px-5 py-2.5 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="buscarExistentes"><i class="fas fa-search mr-2"></i> Buscar existentes</span>
                                <span wire:loading wire:target="buscarExistentes"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</span>
                            </button>
                        </div>

                        {{-- Lista de existentes encontrados --}}
                        @if(count($mpSucursalesExistentes) > 0 || count($mpCajasExistentes) > 0)
                        <div class="mt-4 space-y-3 border border-gray-200 rounded-lg p-4 bg-white">
                            <h5 class="text-sm font-semibold text-gray-700"><i class="fas fa-list mr-1"></i> Encontrados en tu cuenta de MP</h5>

                            @if(count($mpSucursalesExistentes) > 0)
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-2">Sucursales:</p>
                                <div class="space-y-1">
                                    @foreach($mpSucursalesExistentes as $idx => $store)
                                        <button type="button" wire:click="seleccionarSucursal({{ $idx }})"
                                                class="w-full text-left p-2 border-2 rounded-lg text-sm transition
                                                {{ $mpStoreId == ($store['id'] ?? '') ? 'border-[#FFAF22] bg-[#FFAF22]/5' : 'border-gray-200 hover:border-gray-300' }}">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="font-medium text-gray-800">{{ $store['name'] ?? 'Sin nombre' }}</span>
                                                    <span class="text-xs text-gray-400 ml-2">ID: {{ $store['id'] ?? '-' }}</span>
                                                </div>
                                                @if($mpStoreId == ($store['id'] ?? ''))
                                                    <i class="fas fa-check-circle text-[#FFAF22]"></i>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500">External: {{ $store['external_id'] ?? '-' }}
                                                @if(!empty($store['location']['street_name']))
                                                    | {{ $store['location']['street_name'] }} {{ $store['location']['street_number'] ?? '' }}, {{ $store['location']['city_name'] ?? '' }}
                                                @endif
                                            </p>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if(count($mpCajasExistentes) > 0)
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-2">Cajas / POS:</p>
                                <div class="space-y-1">
                                    @foreach($mpCajasExistentes as $idx => $caja)
                                        <button type="button" wire:click="seleccionarCaja({{ $idx }})"
                                                class="w-full text-left p-2 border-2 rounded-lg text-sm transition
                                                {{ $mpPosId == ($caja['id'] ?? '') ? 'border-[#FFAF22] bg-[#FFAF22]/5' : 'border-gray-200 hover:border-gray-300' }}">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="font-medium text-gray-800">{{ $caja['name'] ?? 'Sin nombre' }}</span>
                                                    <span class="text-xs text-gray-400 ml-2">ID: {{ $caja['id'] ?? '-' }}</span>
                                                </div>
                                                @if($mpPosId == ($caja['id'] ?? ''))
                                                    <i class="fas fa-check-circle text-[#FFAF22]"></i>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500">External: {{ $caja['external_id'] ?? '-' }} | Store: {{ $caja['store_id'] ?? ($caja['external_store_id'] ?? '-') }}</p>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                @endif

                {{-- Config avanzada --}}
                <details class="mt-3">
                    <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                        <i class="fas fa-cog mr-1"></i> Configuración avanzada (IDs manuales)
                    </summary>
                    <div class="mt-3 space-y-3 pl-4 border-l-2 border-gray-200">
                        <p class="text-xs text-gray-400">Si ya tenés una sucursal y caja creadas en MP, podés ingresar los IDs externos manualmente.</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">External Store ID (Sucursal)</label>
                            <input type="text" wire:model="mpManualStoreId"
                                   placeholder="Ej: SUCURSAL_001"
                                   class="w-full max-w-sm px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">External POS ID (Caja)</label>
                            <input type="text" wire:model="mpManualPosId"
                                   placeholder="Ej: CAJA_001"
                                   class="w-full max-w-sm px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                        </div>
                        <button type="button" wire:click="aplicarConfigAvanzada"
                                class="px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-check mr-1"></i> Aplicar IDs
                        </button>
                    </div>
                </details>
            </div>
            @endif

            {{-- ===== Panel Point ===== --}}
            @if($mpTipoIntegracion === 'point')
            <div class="border border-gray-200 rounded-lg p-5 space-y-4 bg-gray-50/50">
                <h4 class="text-sm font-semibold text-gray-700"><i class="fas fa-tablet-alt mr-2 text-[#FFAF22]"></i>Configuración Point / Lector</h4>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="cargarDispositivos"
                            wire:loading.attr="disabled" wire:target="cargarDispositivos"
                            class="px-4 py-2 bg-[#FFAF22] text-white text-sm font-medium rounded-lg hover:bg-[#FFAF22]/90 transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="cargarDispositivos"><i class="fas fa-search mr-1"></i> Buscar dispositivos</span>
                        <span wire:loading wire:target="cargarDispositivos"><i class="fas fa-spinner fa-spin mr-1"></i> Buscando...</span>
                    </button>

                    @if($mpPointDeviceId)
                        <button type="button" wire:click="testConexionPoint"
                                wire:loading.attr="disabled" wire:target="testConexionPoint"
                                class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="testConexionPoint"><i class="fas fa-wifi mr-1"></i> Probar conexión</span>
                            <span wire:loading wire:target="testConexionPoint"><i class="fas fa-spinner fa-spin mr-1"></i> Probando...</span>
                        </button>
                    @endif
                </div>

                @if(count($mpPointDevices) > 0)
                    <div class="space-y-2">
                        @foreach($mpPointDevices as $device)
                            <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition
                                {{ $mpPointDeviceId === $device['id'] ? 'border-[#FFAF22] bg-[#FFAF22]/5' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="mpPointDeviceId" value="{{ $device['id'] }}" class="sr-only">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $mpPointDeviceId === $device['id'] ? 'bg-[#FFAF22] text-white' : 'bg-gray-100 text-gray-500' }}">
                                    <i class="fas fa-tablet-alt"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">{{ $device['id'] }}</p>
                                    <p class="text-xs text-gray-500">
                                        Modo: {{ $device['operating_mode'] ?? 'N/A' }}
                                    </p>
                                </div>
                                @if($mpPointDeviceId === $device['id'])
                                    <i class="fas fa-check-circle text-[#FFAF22] ml-auto"></i>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @elseif($mpPointDeviceId)
                    <div class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <div>
                            <p class="text-sm font-medium text-green-700">Dispositivo configurado</p>
                            <p class="text-xs text-green-600">{{ $mpPointDeviceId }}</p>
                        </div>
                    </div>
                @endif
            </div>
            @endif

            {{-- ===== Panel Checkout Pro ===== --}}
            @if($mpTipoIntegracion === 'checkout_pro')
            <div class="border border-amber-200 rounded-lg p-4 bg-amber-50/50">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-amber-800">Checkout Pro cobra comisión por transacción</p>
                        <p class="text-xs text-amber-600 mt-1">Se genera un QR de pago online para cada pedido. El cliente es redirigido al checkout de Mercado Pago. Considerá usar "QR Presencial" para evitar comisiones.</p>
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>

    <!-- ===== Acceso y Opciones ===== -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-white">
                <i class="fas fa-cog mr-2"></i>Acceso y Opciones
            </h2>
            <p class="text-sm text-white/80 mt-1">Contraseña del kiosco e impresora</p>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña de acceso al Kiosco</label>
                <div x-data="{ mostrarPass: false }" class="relative max-w-md">
                    <input :type="mostrarPass ? 'text' : 'password'"
                           wire:model="kioscoPassword"
                           placeholder="Contraseña para ingresar al kiosco"
                           class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                    <button type="button" @click="mostrarPass = !mostrarPass"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                        <i :class="mostrarPass ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    Contraseña para acceder al kiosco desde <code class="bg-gray-100 px-1 rounded">/kiosco</code> y para cerrar la sesión.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de impresora (para ticket automático)</label>
                <input type="text"
                       wire:model="kioscoImpresora"
                       placeholder="Ej: POS-80 o dejar vacío para impresora predeterminada"
                       class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                <p class="text-xs text-gray-400 mt-1">
                    Si dejás este campo vacío, se usará la impresora predeterminada de Windows.
                </p>
            </div>
        </div>
    </div>

    <!-- ===== Formas de Pago ===== -->
    @if(count($formasPagoDisponibles) > 0)
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-white">
                <i class="fas fa-credit-card mr-2"></i>Formas de Pago
            </h2>
            <p class="text-sm text-white/80 mt-1">Elegí qué formas de pago se muestran en el kiosco</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($formasPagoDisponibles as $fp)
                    <label class="flex items-center p-2 border rounded-lg cursor-pointer transition
                        {{ in_array($fp->CODIGO, $kioscoFormasPago) ? 'border-[#FFAF22] bg-[#FFAF22]/10' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="checkbox"
                               value="{{ $fp->CODIGO }}"
                               wire:model="kioscoFormasPago"
                               class="w-4 h-4 text-[#FFAF22] rounded focus:ring-[#FFAF22] mr-2">
                        <span class="text-sm text-gray-800">{{ $fp->NOMBRE }}</span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-2">
                Si no seleccionás ninguna, se mostrarán todas las formas de pago táctiles.
            </p>
        </div>
    </div>
    @endif

    <!-- ===== Sugerencias ===== -->
    @if(count($deptosDisponibles) > 0)
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-white">
                <i class="fas fa-lightbulb mr-2"></i>Sugerencias
            </h2>
            <p class="text-sm text-white/80 mt-1">Configurá qué productos sugerir al agregar al carrito</p>
        </div>

        <div class="p-6">
            <p class="text-xs text-gray-400 mb-3">
                Para cada departamento, elegí de qué otros departamentos sugerir productos al cliente.
            </p>

            <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                @foreach($deptosDisponibles as $depto)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 py-2 border-b border-gray-100 last:border-0">
                        <span class="text-sm font-medium text-gray-800 sm:w-40 flex-shrink-0">{{ $depto->NOMBRE }}</span>
                        <div class="flex flex-wrap gap-1 flex-1">
                            @foreach($deptosDisponibles as $deptoSug)
                                @if($deptoSug->CODIGO != $depto->CODIGO)
                                    <button type="button"
                                            wire:click="toggleSugerencia({{ $depto->CODIGO }}, {{ $deptoSug->CODIGO }})"
                                            class="px-2 py-1 border rounded text-xs cursor-pointer transition
                                            {{ is_array($kioscoSugerencias[$depto->CODIGO] ?? null) && in_array($deptoSug->CODIGO, $kioscoSugerencias[$depto->CODIGO]) ? 'border-[#FFAF22] bg-[#FFAF22]/10 text-[#FFAF22] font-semibold' : 'border-gray-200 text-gray-600 hover:border-gray-300' }}">
                                        {{ $deptoSug->NOMBRE }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2 flex items-center gap-2">
                <label class="text-sm text-gray-600">Cantidad de sugerencias a mostrar:</label>
                <input type="number" wire:model="kioscoSugerenciasCantidad" min="1" max="8"
                       class="w-16 px-2 py-1 border border-gray-300 rounded text-sm text-center focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
            </div>
        </div>
    </div>
    @endif

    <!-- ===== Botón Guardar ===== -->
    <div class="flex items-center gap-3">
        <button wire:click="guardarConfigKiosco"
                wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-[#FFAF22] text-white font-semibold rounded-lg hover:bg-[#FFAF22]/90 transition disabled:opacity-50">
            <span wire:loading.remove wire:target="guardarConfigKiosco">
                <i class="fas fa-save mr-2"></i> Guardar Configuración
            </span>
            <span wire:loading wire:target="guardarConfigKiosco">
                <i class="fas fa-spinner fa-spin mr-2"></i> Guardando...
            </span>
        </button>

        @if($guardado)
            <span class="text-green-600 text-sm font-medium"
                  x-data="{ show: true }"
                  x-init="setTimeout(() => show = false, 3000)"
                  x-show="show"
                  x-transition>
                <i class="fas fa-check mr-1"></i> Guardado correctamente
            </span>
        @endif
    </div>
</div>
