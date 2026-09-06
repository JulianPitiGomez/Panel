<div class="space-y-6 w-full max-w-full overflow-x-hidden" x-data="{ confirmarEliminar: null }">

    <!-- Mensaje flash -->
    @if($mensaje)
        <div class="rounded-xl p-4 flex items-center gap-3 {{ $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' }}">
            <i class="fas {{ $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }} text-lg"></i>
            <span class="font-secondary text-sm">{{ $mensaje }}</span>
            <button wire:click="$set('mensaje', '')" class="ml-auto text-lg leading-none">&times;</button>
        </div>
    @endif

    <!-- Tabs -->
    <div class="flex gap-1 bg-white rounded-xl shadow-sm border border-gray-200 p-1">
        <button wire:click="$set('tabActivo', 'configuracion')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'configuracion' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-cog"></i>
            <span>Mi Tienda</span>
        </button>
        <button wire:click="$set('tabActivo', 'productos')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'productos' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-camera"></i>
            <span>Fotos Productos</span>
        </button>
        <button wire:click="$set('tabActivo', 'envios')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'envios' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-truck"></i>
            <span>Envíos</span>
        </button>
        <button wire:click="$set('tabActivo', 'agenda')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'agenda' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-calendar-check"></i>
            <span>Agenda</span>
        </button>
        <button wire:click="$set('tabActivo', 'pagos')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'pagos' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-wallet"></i>
            <span>Pagos</span>
        </button>
        <button wire:click="$set('tabActivo', 'pedidos')"
                class="flex-1 flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-semibold transition-all
                {{ $tabActivo === 'pedidos' ? 'bg-[#1E1B35] text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-clipboard-list"></i>
            <span>Pedidos web</span>
        </button>
    </div>

    {{-- ==================== TAB ENVÍOS ==================== --}}
    @if($tabActivo === 'envios')
        @livewire('panel.configurar-envios', [], key('configurar-envios'))
    @endif

    {{-- ==================== TAB AGENDA ==================== --}}
    @if($tabActivo === 'agenda')
        @livewire('panel.configurar-agenda', [], key('configurar-agenda'))
    @endif

    {{-- ==================== TAB PAGOS ==================== --}}
    @if($tabActivo === 'pagos')
        @livewire('panel.configurar-pagos', [], key('configurar-pagos'))
    @endif

    {{-- ==================== TAB PEDIDOS WEB ==================== --}}
    @if($tabActivo === 'pedidos')
        @livewire('panel.pedidos-web', [], key('pedidos-web'))
    @endif

    {{-- ==================== TAB CONFIGURACIÓN ==================== --}}
    @if($tabActivo === 'configuracion')
        <div class="space-y-6">

            <!-- Asistente de alta: que falta para salir a vender -->
            @php $listos = collect($asistente)->where('ok', true)->count(); $totalPasos = count($asistente); @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-rocket text-[#FFAF22] mr-2"></i> Tu tienda, paso a paso
                    </h3>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $listos === $totalPasos ? 'bg-green-100 text-green-700' : 'bg-[#FFAF22]/15 text-[#B8860B]' }}">{{ $listos }} de {{ $totalPasos }} listos</span>
                </div>
                <div class="w-full h-2 bg-gray-100 rounded-full mb-4 overflow-hidden">
                    <div class="h-2 bg-[#FFAF22] rounded-full" style="width: {{ $totalPasos ? (int) ($listos * 100 / $totalPasos) : 0 }}%"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($asistente as $paso)
                        <div class="flex items-start gap-3 p-3 rounded-lg {{ $paso['ok'] ? 'bg-green-50' : 'bg-gray-50' }}">
                            <i class="fas {{ $paso['ok'] ? 'fa-check-circle text-green-500' : (!empty($paso['opcional']) ? 'fa-circle text-gray-300' : 'fa-exclamation-circle text-[#FFAF22]') }} mt-0.5"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800">{{ $paso['titulo'] }} @if(!empty($paso['opcional']))<span class="text-[10px] text-gray-400 font-normal">opcional</span>@endif</p>
                                <p class="text-xs text-gray-500 truncate">{{ $paso['detalle'] }}</p>
                            </div>
                            @if($paso['tab'] && $paso['tab'] !== 'configuracion')
                                <button wire:click="$set('tabActivo', '{{ $paso['tab'] }}')" class="text-xs text-[#B8860B] hover:underline whitespace-nowrap">Ir <i class="fas fa-arrow-right"></i></button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Chequeo del catalogo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-800 mb-1 flex items-center">
                    <i class="fas fa-clipboard-check text-[#FFAF22] mr-2"></i> Chequeo del catálogo
                </h3>
                <p class="text-xs text-gray-400 mb-4">Un catálogo presentable vende. Tocá cada número para ver esos artículos y resolverlos.</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach([['sin_foto', 'Sin foto', 'fa-image'], ['sin_precio', 'Sin precio', 'fa-dollar-sign'], ['sin_descripcion', 'Sin descripción', 'fa-align-left'], ['sin_peso', 'Sin peso', 'fa-weight-hanging']] as [$clave, $titulo, $icono])
                        @if(($chequeo[$clave] ?? null) !== null)
                            <button wire:click="irAChequeo('{{ $clave }}')" class="text-left p-3 rounded-xl border {{ ($chequeo[$clave] ?? 0) > 0 ? 'border-amber-200 bg-amber-50 hover:bg-amber-100' : 'border-green-200 bg-green-50' }}">
                                <p class="text-2xl font-bold {{ ($chequeo[$clave] ?? 0) > 0 ? 'text-amber-700' : 'text-green-700' }}">{{ number_format($chequeo[$clave] ?? 0, 0, ',', '.') }}</p>
                                <p class="text-xs text-gray-600"><i class="fas {{ $icono }} mr-1"></i>{{ $titulo }}</p>
                            </button>
                        @endif
                    @endforeach
                </div>
                @if(($chequeo['sin_precio'] ?? 0) > 0)
                    <p class="text-[11px] text-gray-400 mt-2">Los artículos sin precio se muestran en la tienda a $0: cargales el precio en gestión.</p>
                @endif
            </div>

            <!-- URL, Nombre de Tienda y Modo de Acceso -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-link text-[#FFAF22] mr-2"></i> Enlace y acceso al catálogo
                </h3>

                {{-- URL del catálogo --}}
                @if(!empty($tiendaSlug))
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL de tu catálogo</label>
                        <div class="flex items-center gap-2 min-w-0" x-data="{ copied: false }">
                            <div class="flex-1 min-w-0 flex items-center bg-gray-50 border border-gray-300 rounded-lg overflow-hidden">
                                <span class="px-3 py-2 text-sm text-gray-500 bg-gray-100 border-r border-gray-300 whitespace-nowrap">
                                    <i class="fas fa-globe mr-1"></i>
                                </span>
                                <input type="text" readonly
                                       value="{{ url('/web/' . $tiendaSlug) }}"
                                       id="urlCatalogo"
                                       class="flex-1 min-w-0 px-3 py-2 text-xs sm:text-sm text-gray-800 bg-gray-50 border-0 focus:ring-0 cursor-text">
                            </div>
                            <button @click="navigator.clipboard.writeText(document.getElementById('urlCatalogo').value); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap flex-shrink-0"
                                    :class="copied ? 'bg-green-500 text-white' : 'bg-[#1E1B35] text-white hover:bg-[#2d2952]'">
                                <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                                <span x-text="copied ? 'Copiado' : 'Copiar'" class="hidden sm:inline ml-1"></span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mb-5 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Configurá un nombre de tienda para obtener la URL de tu catálogo.
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Nombre de tienda (slug) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la tienda (URL)</label>
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="flex-1 min-w-0 flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                <span class="px-2 sm:px-3 py-2 text-xs text-gray-400 bg-gray-50 border-r border-gray-300 whitespace-nowrap">/web/</span>
                                <input type="text" wire:model="tiendaSlug"
                                       placeholder="mi-tienda"
                                       class="flex-1 min-w-0 px-3 py-2 text-sm border-0 focus:ring-2 focus:ring-[#FFAF22]">
                            </div>
                            <button wire:click="guardarTiendaSlug" wire:loading.attr="disabled" wire:target="guardarTiendaSlug"
                                    class="px-3 sm:px-4 py-2 bg-[#FFAF22] text-white rounded-lg hover:bg-[#E89B0A] transition font-semibold text-sm whitespace-nowrap flex-shrink-0">
                                <span wire:loading.remove wire:target="guardarTiendaSlug"><i class="fas fa-save"></i><span class="hidden sm:inline ml-1">Guardar</span></span>
                                <span wire:loading wire:target="guardarTiendaSlug"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Solo letras, números y guiones. Ej: mi-tienda, comercio123</p>
                    </div>

                    {{-- Modo de acceso --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modo de acceso</label>
                        <div class="flex items-center gap-2 min-w-0">
                            <select wire:model.live="tiendaModo"
                                    class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                                @foreach(\App\Services\TiendaService::modos() as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <button wire:click="guardarTiendaModo"
                                    class="px-3 sm:px-4 py-2 bg-[#FFAF22] text-white rounded-lg hover:bg-[#E89B0A] transition font-semibold text-sm whitespace-nowrap flex-shrink-0">
                                <i class="fas fa-save"></i><span class="hidden sm:inline ml-1">Guardar</span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            @if($tiendaModo === \App\Services\TiendaService::MODO_PUBLICA)
                                <i class="fas fa-unlock text-green-500 mr-1"></i> Cualquier persona con el enlace puede ver tu catálogo.
                            @else
                                <i class="fas fa-lock text-orange-500 mr-1"></i> Tus clientes deben iniciar sesión para ver el catálogo.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Logo y Portada -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Logo -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-image text-[#FFAF22] mr-2"></i> Logo de la tienda
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">Se mostrara en el encabezado del catalogo. Formato recomendado: cuadrado, max 2MB.</p>

                    @if($logoUrl)
                        <div class="mb-4 flex justify-center">
                            <img src="{{ $logoUrl }}" alt="Logo" class="w-32 h-32 object-contain rounded-lg border border-gray-200 bg-gray-50">
                        </div>
                    @else
                        <div class="mb-4 flex justify-center">
                            <div class="w-32 h-32 bg-gray-100 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300">
                                <i class="fas fa-image text-gray-300 text-3xl"></i>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <input type="file" wire:model="fotoLogo" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#FFAF22] file:text-white hover:file:bg-[#E89B0A] cursor-pointer">
                        @error('fotoLogo') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                        <div wire:loading wire:target="fotoLogo" class="flex items-center gap-2 text-sm text-gray-500">
                            <i class="fas fa-spinner fa-spin text-[#FFAF22]"></i> Cargando...
                        </div>

                        @if($fotoLogo)
                            <div class="flex justify-center">
                                <img src="{{ $fotoLogo->temporaryUrl() }}" alt="Preview" class="w-24 h-24 object-contain rounded-lg border">
                            </div>
                        @endif

                        <button wire:click="subirLogo" wire:loading.attr="disabled" wire:target="subirLogo"
                                {{ !$fotoLogo ? 'disabled' : '' }}
                                class="w-full py-2.5 bg-[#FFAF22] text-white rounded-lg hover:bg-[#E89B0A] transition font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="subirLogo"><i class="fas fa-upload mr-1"></i> Guardar logo</span>
                            <span wire:loading wire:target="subirLogo"><i class="fas fa-spinner fa-spin mr-1"></i> Subiendo...</span>
                        </button>
                    </div>
                </div>

                <!-- Portada -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-panorama text-[#FFAF22] mr-2"></i> Imagen de portada
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">Se mostrara como banner principal. Formato recomendado: 1200x400px, max 5MB.</p>

                    @if($portadaUrl)
                        <div class="mb-4">
                            <img src="{{ $portadaUrl }}" alt="Portada" class="w-full h-40 object-cover rounded-lg border border-gray-200">
                        </div>
                    @else
                        <div class="mb-4">
                            <div class="w-full h-40 bg-gray-100 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300">
                                <i class="fas fa-panorama text-gray-300 text-3xl"></i>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <input type="file" wire:model="fotoPortada" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#FFAF22] file:text-white hover:file:bg-[#E89B0A] cursor-pointer">
                        @error('fotoPortada') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                        <div wire:loading wire:target="fotoPortada" class="flex items-center gap-2 text-sm text-gray-500">
                            <i class="fas fa-spinner fa-spin text-[#FFAF22]"></i> Cargando...
                        </div>

                        @if($fotoPortada)
                            <div>
                                <img src="{{ $fotoPortada->temporaryUrl() }}" alt="Preview" class="w-full h-32 object-cover rounded-lg border">
                            </div>
                        @endif

                        <button wire:click="subirPortada" wire:loading.attr="disabled" wire:target="subirPortada"
                                {{ !$fotoPortada ? 'disabled' : '' }}
                                class="w-full py-2.5 bg-[#FFAF22] text-white rounded-lg hover:bg-[#E89B0A] transition font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="subirPortada"><i class="fas fa-upload mr-1"></i> Guardar portada</span>
                            <span wire:loading wire:target="subirPortada"><i class="fas fa-spinner fa-spin mr-1"></i> Subiendo...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Datos del comercio -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-[#FFAF22] mr-2"></i> Datos del comercio
                </h3>
                <p class="text-xs text-gray-500 mb-6">Esta informacion se mostrara en tu catalogo online.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Direccion</label>
                        <input type="text" wire:model="datosComercio.direccion" placeholder="Av. Ejemplo 1234"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Localidad</label>
                        <input type="text" wire:model="datosComercio.localidad" placeholder="Ciudad, Provincia"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                        <input type="text" wire:model="datosComercio.telefono" placeholder="+54 11 1234-5678"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Horario</label>
                        <input type="text" wire:model="datosComercio.horario" placeholder="Lun a Vie 9 a 18hs"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fab fa-instagram"></i></span>
                            <input type="text" wire:model="datosComercio.instagram" placeholder="@mitienda"
                                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fab fa-facebook"></i></span>
                            <input type="text" wire:model="datosComercio.facebook" placeholder="facebook.com/mitienda"
                                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slogan</label>
                        <input type="text" wire:model="datosComercio.slogan" placeholder="Tu frase comercial..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope text-[#FFAF22] mr-1"></i> Email para notificaciones de pedidos
                        </label>
                        <input type="email" wire:model="datosComercio.email_comercio" placeholder="ventas@mitienda.com"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent text-sm">
                        <p class="text-xs text-gray-400 mt-1">Recibiras un mail con el detalle de cada pedido nuevo.</p>
                    </div>
                </div>
            </div>

            <!-- Colores y Filtros -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Colores -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-palette text-[#FFAF22] mr-2"></i> Colores del catalogo
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fondo</label>
                            <div class="flex items-center gap-2 min-w-0">
                                <input type="color" wire:model="datosComercio.color_fondo"
                                       class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 flex-shrink-0">
                                <input type="text" wire:model="datosComercio.color_fondo"
                                       class="flex-1 min-w-0 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Textos</label>
                            <div class="flex items-center gap-2 min-w-0">
                                <input type="color" wire:model="datosComercio.color_texto"
                                       class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 flex-shrink-0">
                                <input type="text" wire:model="datosComercio.color_texto"
                                       class="flex-1 min-w-0 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precios</label>
                            <div class="flex items-center gap-2 min-w-0">
                                <input type="color" wire:model="datosComercio.color_precio"
                                       class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 flex-shrink-0">
                                <input type="text" wire:model="datosComercio.color_precio"
                                       class="flex-1 min-w-0 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Botones</label>
                            <div class="flex items-center gap-2 min-w-0">
                                <input type="color" wire:model="datosComercio.color_boton"
                                       class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 flex-shrink-0">
                                <input type="text" wire:model="datosComercio.color_boton"
                                       class="flex-1 min-w-0 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-mono">
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="mt-4 p-4 rounded-lg border border-gray-200" style="background-color: {{ $datosComercio['color_fondo'] ?? '#f9fafb' }}">
                        <p class="text-sm font-semibold mb-1" style="color: {{ $datosComercio['color_texto'] ?? '#1f2937' }}">Producto de ejemplo</p>
                        <p class="text-lg font-bold mb-2" style="color: {{ $datosComercio['color_precio'] ?? '#1E1B35' }}">$1.500,00</p>
                        <span class="inline-block px-3 py-1 text-white text-xs font-semibold rounded-lg" style="background-color: {{ $datosComercio['color_boton'] ?? '#FFAF22' }}">Agregar</span>
                    </div>
                </div>

                <!-- Filtros visibles -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                    <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-filter text-[#FFAF22] mr-2"></i> Filtros del catalogo
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">Selecciona que filtros veran tus clientes. La busqueda por nombre siempre esta disponible.</p>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg opacity-60">
                            <input type="checkbox" checked disabled class="w-4 h-4 rounded text-[#FFAF22]">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Busqueda por nombre/codigo</span>
                                <p class="text-xs text-gray-400">Siempre visible</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
                            <input type="checkbox" wire:model="datosComercio.filtro_depto" class="w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Departamento</span>
                                <p class="text-xs text-gray-400">Filtrar por departamento de productos</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
                            <input type="checkbox" wire:model="datosComercio.filtro_rubro" class="w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Rubro</span>
                                <p class="text-xs text-gray-400">Filtrar por rubro de productos</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
                            <input type="checkbox" wire:model="datosComercio.filtro_marca" class="w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Marca</span>
                                <p class="text-xs text-gray-400">Filtrar por marca de productos</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Apariencia del catálogo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-wand-magic-sparkles text-[#FFAF22] mr-2"></i> Apariencia del catálogo
                </h3>
                <p class="text-xs text-gray-500 mb-6">Personalizá cómo se ve tu catálogo para los clientes.</p>

                {{-- Layout del catálogo --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Layout del catálogo</label>
                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        @foreach([
                            'top' => ['fa-grip-lines', 'Superior', 'Filtros arriba del catálogo'],
                            'sidebar' => ['fa-columns', 'Lateral', 'Filtros en barra lateral'],
                            'minimal' => ['fa-search', 'Minimal', 'Solo barra de búsqueda']
                        ] as $valor => $info)
                            <button wire:click="setConfigCatalogo('layout_catalogo', '{{ $valor }}')" type="button"
                                    class="p-2 sm:p-4 rounded-xl text-center transition-all {{ ($datosComercio['layout_catalogo'] ?? 'top') == $valor ? 'border-2 border-[#FFAF22] bg-[#FFAF22]/5 shadow-sm' : 'border border-gray-200 hover:border-gray-300' }}">
                                <div class="w-9 h-9 sm:w-12 sm:h-12 mx-auto mb-1.5 sm:mb-2 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <i class="fas {{ $info[0] }} text-base sm:text-xl {{ ($datosComercio['layout_catalogo'] ?? 'top') == $valor ? 'text-[#FFAF22]' : 'text-gray-400' }}"></i>
                                </div>
                                <span class="text-xs sm:text-sm font-semibold block {{ ($datosComercio['layout_catalogo'] ?? 'top') == $valor ? 'text-[#FFAF22]' : 'text-gray-700' }}">{{ $info[1] }}</span>
                                <span class="hidden sm:block text-xs text-gray-400">{{ $info[2] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Estilo de tarjeta --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Estilo de tarjeta</label>
                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        @foreach([
                            'compacta' => ['fa-th-large', 'Compacta', 'Imagen pequeña, info densa'],
                            'estandar' => ['fa-square', 'Estándar', 'Imagen cuadrada, info básica'],
                            'detallada' => ['fa-rectangle-list', 'Detallada', 'Imagen grande, más info']
                        ] as $valor => $info)
                            <button wire:click="setConfigCatalogo('estilo_tarjeta', '{{ $valor }}')" type="button"
                                    class="p-2 sm:p-4 rounded-xl text-center transition-all {{ ($datosComercio['estilo_tarjeta'] ?? 'estandar') == $valor ? 'border-2 border-[#FFAF22] bg-[#FFAF22]/5 shadow-sm' : 'border border-gray-200 hover:border-gray-300' }}">
                                <div class="w-9 h-9 sm:w-12 sm:h-12 mx-auto mb-1.5 sm:mb-2 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <i class="fas {{ $info[0] }} text-base sm:text-xl {{ ($datosComercio['estilo_tarjeta'] ?? 'estandar') == $valor ? 'text-[#FFAF22]' : 'text-gray-400' }}"></i>
                                </div>
                                <span class="text-xs sm:text-sm font-semibold block {{ ($datosComercio['estilo_tarjeta'] ?? 'estandar') == $valor ? 'text-[#FFAF22]' : 'text-gray-700' }}">{{ $info[1] }}</span>
                                <span class="hidden sm:block text-xs text-gray-400">{{ $info[2] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Columnas de grilla --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Columnas en escritorio</label>
                    <div class="grid grid-cols-4 gap-2 sm:gap-3">
                        @foreach([2, 3, 4, 5] as $cols)
                            <button wire:click="setConfigCatalogo('columnas_desktop', {{ $cols }})" type="button"
                                    class="p-2 sm:p-4 rounded-xl text-center transition-all {{ ($datosComercio['columnas_desktop'] ?? 4) == $cols ? 'border-2 border-[#FFAF22] bg-[#FFAF22]/5 shadow-sm' : 'border border-gray-200 hover:border-gray-300' }}">
                                <span class="text-xl sm:text-2xl font-bold block {{ ($datosComercio['columnas_desktop'] ?? 4) == $cols ? 'text-[#FFAF22]' : 'text-gray-600' }}">{{ $cols }}</span>
                                <span class="text-[10px] sm:text-xs text-gray-400">columnas</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Orden por defecto --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Orden por defecto</label>
                        <select wire:model="datosComercio.orden_defecto"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                            <option value="nombre_asc">Nombre A-Z</option>
                            <option value="nombre_desc">Nombre Z-A</option>
                            <option value="precio_asc">Precio menor a mayor</option>
                            <option value="precio_desc">Precio mayor a menor</option>
                        </select>
                    </div>

                    {{-- Estilo de header --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estilo de encabezado</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach([
                                'completo' => ['fa-window-maximize', 'Completo'],
                                'simple' => ['fa-minus', 'Simple'],
                                'banner' => ['fa-image', 'Banner']
                            ] as $valor => $info)
                                <button wire:click="setConfigCatalogo('estilo_header', '{{ $valor }}')" type="button"
                                        class="p-3 rounded-lg text-center transition-all {{ ($datosComercio['estilo_header'] ?? 'completo') == $valor ? 'border-2 border-[#FFAF22] bg-[#FFAF22]/5' : 'border border-gray-200 hover:border-gray-300' }}">
                                    <i class="fas {{ $info[0] }} {{ ($datosComercio['estilo_header'] ?? 'completo') == $valor ? 'text-[#FFAF22]' : 'text-gray-400' }}"></i>
                                    <span class="text-xs block mt-1 {{ ($datosComercio['estilo_header'] ?? 'completo') == $valor ? 'text-[#FFAF22] font-semibold' : 'text-gray-600' }}">{{ $info[1] }}</span>
                                </button>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            Completo: barra superior + imagen de portada. Simple: solo barra, sin portada. Banner: portada a pantalla completa con navegación superpuesta.
                            @if(!$portadaUrl)
                                <br><span class="text-orange-500"><i class="fas fa-info-circle mr-0.5"></i> Subí una imagen de portada para ver la diferencia entre los estilos.</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Carrito de compras -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-shopping-cart text-[#FFAF22] mr-2"></i> Carrito de compras
                </h3>
                <p class="text-xs text-gray-500 mb-6">Configurá cómo se agregan productos al carrito.</p>

                {{-- Modo cantidad --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Al hacer click en "Agregar"</label>
                    <div class="grid grid-cols-2 gap-2 sm:gap-3">
                        @foreach([
                            'rapido' => ['fa-bolt', 'Rápido', 'Agrega 1 unidad directamente'],
                            'modal' => ['fa-keyboard', 'Elegir cantidad', 'Pide cantidad antes de agregar']
                        ] as $valor => $info)
                            <button wire:click="setConfigCatalogo('modo_cantidad', '{{ $valor }}')" type="button"
                                    class="p-2.5 sm:p-4 rounded-xl text-center transition-all {{ ($datosComercio['modo_cantidad'] ?? 'rapido') == $valor ? 'border-2 border-[#FFAF22] bg-[#FFAF22]/5 shadow-sm' : 'border border-gray-200 hover:border-gray-300' }}">
                                <div class="w-9 h-9 sm:w-12 sm:h-12 mx-auto mb-1.5 sm:mb-2 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <i class="fas {{ $info[0] }} text-base sm:text-xl {{ ($datosComercio['modo_cantidad'] ?? 'rapido') == $valor ? 'text-[#FFAF22]' : 'text-gray-400' }}"></i>
                                </div>
                                <span class="text-xs sm:text-sm font-semibold block {{ ($datosComercio['modo_cantidad'] ?? 'rapido') == $valor ? 'text-[#FFAF22]' : 'text-gray-700' }}">{{ $info[1] }}</span>
                                <span class="text-[10px] sm:text-xs text-gray-400 leading-tight block mt-0.5">{{ $info[2] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Permite decimal --}}
                <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
                    <input type="checkbox" wire:model.live="datosComercio.permite_decimal" class="w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Permitir cantidades decimales</span>
                        <p class="text-xs text-gray-400">Útil para productos pesables (ej: 0.5 kg, 1.25 kg). Si está desactivado, solo se permiten cantidades enteras.</p>
                    </div>
                </label>

                {{-- Vender sin stock --}}
                <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition mt-3">
                    <input type="checkbox" wire:model.live="datosComercio.tienda_vende_sin_stock" class="w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Permitir vender sin stock</span>
                        <p class="text-xs text-gray-400">Activado: la tienda muestra la disponibilidad pero no frena la compra. Desactivado: la cantidad se limita a lo disponible (stock menos lo comprometido en pedidos pendientes) y los artículos en cero no se pueden agregar.</p>
                    </div>
                </label>
            </div>

            <!-- Destacados de la portada -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <h3 class="text-lg font-primary font-semibold text-gray-800 mb-1 flex items-center">
                    <i class="fas fa-star text-[#FFAF22] mr-2"></i> Destacados de la portada
                </h3>
                <p class="text-xs text-gray-400 mb-4">Los elegís vos con la estrella en Fotos Productos. Acá los ordenás. Las promociones no se cargan acá: salen solas de las que tenés en gestión.</p>
                @if(count($destacados) === 0)
                    <p class="text-sm text-gray-400">Todavía no destacaste ningún artículo.</p>
                @else
                    <div class="space-y-2">
                        @foreach($destacados as $i => $d)
                            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg" wire:key="dest-{{ $d['codigo'] }}">
                                <span class="w-6 text-center text-xs text-gray-400">{{ $i + 1 }}</span>
                                @if($d['foto_url'])
                                    <img src="{{ $d['foto_url'] }}" class="w-10 h-10 object-cover rounded-lg border border-gray-200">
                                @else
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center"><i class="fas fa-image text-gray-300"></i></div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 truncate">{{ $d['nombre'] }}</p>
                                    <p class="text-xs text-gray-400">Cod: {{ $d['codigo'] }}</p>
                                </div>
                                <button wire:click="moverDestacado('{{ $d['codigo'] }}', 'arriba')" @disabled($i === 0) class="p-1.5 rounded-lg hover:bg-gray-200 text-gray-500 disabled:opacity-30"><i class="fas fa-arrow-up"></i></button>
                                <button wire:click="moverDestacado('{{ $d['codigo'] }}', 'abajo')" @disabled($i === count($destacados) - 1) class="p-1.5 rounded-lg hover:bg-gray-200 text-gray-500 disabled:opacity-30"><i class="fas fa-arrow-down"></i></button>
                                <button wire:click="toggleDestacado('{{ $d['codigo'] }}')" class="p-1.5 rounded-lg hover:bg-red-50 text-red-400" title="Quitar"><i class="fas fa-times"></i></button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Botón guardar todo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                <div class="flex justify-end">
                    <button wire:click="guardarDatosComercio" wire:loading.attr="disabled" wire:target="guardarDatosComercio"
                            class="px-6 py-2.5 bg-[#1E1B35] text-white rounded-lg hover:bg-[#2d2952] transition font-semibold text-sm">
                        <span wire:loading.remove wire:target="guardarDatosComercio"><i class="fas fa-save mr-1"></i> Guardar datos</span>
                        <span wire:loading wire:target="guardarDatosComercio"><i class="fas fa-spinner fa-spin mr-1"></i> Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== TAB PRODUCTOS ==================== --}}
    @if($tabActivo === 'productos')

        <!-- Contadores -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-gray-500">Total Productos</p>
                        <p class="text-2xl font-primary font-bold text-gray-800">{{ number_format($totalProductos) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-gray-500">Con Foto</p>
                        <p class="text-2xl font-primary font-bold text-green-600">{{ number_format($totalConFoto) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-secondary text-gray-500">Sin Foto</p>
                        <p class="text-2xl font-primary font-bold text-orange-600">{{ number_format($totalSinFoto) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-orange-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-filter text-[#FFAF22] mr-2"></i>
                    Filtros
                </h2>
                <button wire:click="limpiarFiltros"
                        class="px-3 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200 text-sm">
                    <i class="fas fa-eraser mr-1"></i><span class="hidden sm:inline">Limpiar</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Buscar por Codigo o Nombre</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="busqueda"
                           placeholder="Ingresa codigo o nombre..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent font-secondary">
                </div>
                <div>
                    <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Estado de Foto</label>
                    <select wire:model.live="filtroFoto"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent font-secondary">
                        <option value="">Todos los productos</option>
                        <option value="con">Con foto</option>
                        <option value="sin">Sin foto</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Chequeo</label>
                    <select wire:model.live="filtroChequeo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent font-secondary text-sm">
                        <option value="">Todos</option>
                        <option value="sin_precio">Sin precio</option>
                        <option value="sin_descripcion">Sin descripción</option>
                        <option value="sin_peso">Sin peso</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Descripciones con IA -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="flex-1">
                    <h3 class="text-sm font-primary font-semibold text-gray-800"><i class="fas fa-magic text-[#FFAF22] mr-2"></i>Descripciones con IA</h3>
                    <p class="text-xs text-gray-400">Para los artículos sin descripción, la IA propone un texto, un título y una categoría a partir del nombre, rubro y marca. Nada se publica hasta que lo revisás: podés retocar, aceptar o descartar cada uno.</p>
                </div>
                @if(\App\Services\TiendaIaService::disponible())
                    <button wire:click="iaGenerarLote" wire:loading.attr="disabled" wire:target="iaGenerarLote" @disabled($iaCandidatos === 0)
                            class="px-4 py-2 rounded-lg text-xs font-semibold bg-[#1E1B35] text-white hover:bg-[#2d2952] whitespace-nowrap disabled:opacity-40">
                        <span wire:loading.remove wire:target="iaGenerarLote"><i class="fas fa-magic mr-1"></i> Proponer para {{ min($iaCandidatos, \App\Services\TiendaIaService::LOTE_MAX) }} artículos</span>
                        <span wire:loading wire:target="iaGenerarLote"><i class="fas fa-spinner fa-spin mr-1"></i> Pensando…</span>
                    </button>
                    <span class="text-[11px] text-gray-400">{{ $iaCandidatos }} sin descripción</span>
                @else
                    <span class="text-xs text-gray-400 bg-gray-50 px-3 py-2 rounded-lg">Función no habilitada en este servidor.</span>
                @endif
            </div>
            @if($iaMensaje)
                <p class="text-xs mt-3 px-3 py-2 rounded-lg {{ str_starts_with($iaMensaje, 'No se pudo') ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-700' }}">{{ $iaMensaje }} <button wire:click="$set('iaMensaje', '')" class="float-right">&times;</button></p>
            @endif

            @if(count($iaPendientes))
                <div class="mt-4 flex items-center justify-between">
                    <p class="text-xs font-semibold text-gray-700">{{ count($iaPendientes) }} propuestas para revisar</p>
                    <button wire:click="iaAceptarTodas" wire:confirm="¿Aceptar todas las propuestas tal como están (o como las editaste)?" class="text-xs px-3 py-1.5 rounded-lg bg-green-500 text-white hover:bg-green-600"><i class="fas fa-check-double mr-1"></i>Aceptar todas</button>
                </div>
                <div class="mt-2 space-y-2 max-h-[520px] overflow-y-auto pr-1">
                    @foreach($iaPendientes as $p)
                        <div class="rounded-lg border border-gray-200 p-3" wire:key="ia-{{ $p['codigo'] }}">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $p['nombre'] }} <span class="text-xs text-gray-400">({{ $p['codigo'] }})</span></p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 whitespace-nowrap">{{ $p['sugerencia']['categoria'] ?? '' }}</span>
                            </div>
                            <textarea wire:model="iaEdicion.{{ $p['codigo'] }}.descripcion" rows="3" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-700">{{ $p['sugerencia']['descripcion'] ?? '' }}</textarea>
                            <p class="text-[10px] text-gray-400 mt-1"><span class="font-semibold">Título:</span> {{ $p['sugerencia']['meta_titulo'] ?? '' }} · <span class="font-semibold">Google:</span> {{ $p['sugerencia']['meta_descripcion'] ?? '' }}</p>
                            <div class="flex gap-2 mt-2">
                                <button wire:click="iaAceptar('{{ $p['codigo'] }}')" class="px-3 py-1 rounded-lg bg-green-500 text-white text-xs hover:bg-green-600"><i class="fas fa-check mr-1"></i>Aceptar</button>
                                <button wire:click="iaDescartar('{{ $p['codigo'] }}')" class="px-3 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs hover:bg-gray-200"><i class="fas fa-times mr-1"></i>Descartar</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Peso y medidas por planilla (para cotizar el correo) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="flex-1">
                    <h3 class="text-sm font-primary font-semibold text-gray-800"><i class="fas fa-weight-hanging text-[#FFAF22] mr-2"></i>Peso y medidas de los artículos</h3>
                    <p class="text-xs text-gray-400">Sirven para cotizar el envío por correo. Cargá el peso en la tabla o completá la planilla en Excel y subila (columnas: codigo, peso_kg, alto_cm, ancho_cm, largo_cm, bultos).</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 items-start sm:items-center">
                    <button wire:click="descargarPlanillaPesos" class="px-3 py-2 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 whitespace-nowrap">
                        <i class="fas fa-download mr-1"></i> Descargar planilla
                    </button>
                    <div class="flex items-center gap-2">
                        <input type="file" wire:model="planillaPesos" accept=".csv,text/csv" class="text-xs w-48">
                        <button wire:click="importarPlanillaPesos" wire:loading.attr="disabled" class="px-3 py-2 rounded-lg text-xs font-semibold bg-[#1E1B35] text-white hover:bg-[#2d2952] whitespace-nowrap">
                            <i class="fas fa-upload mr-1"></i> Importar
                        </button>
                    </div>
                </div>
            </div>
            @error('planillaPesos') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            @if($mensajePesos)
                <p class="text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2 mt-3">{{ $mensajePesos }} <button wire:click="$set('mensajePesos', '')" class="float-right">&times;</button></p>
            @endif
        </div>

        <!-- Lista de productos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-primary font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-camera text-[#FFAF22] mr-2"></i>
                    Productos
                    <span class="ml-2 text-sm font-normal text-gray-500">({{ $productos->total() }} resultados)</span>
                </h2>
            </div>

            <!-- Desktop: Tabla -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Codigo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" title="Se muestra en la portada de la tienda">Dest.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" title="Para cotizar el correo">Peso (kg)</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($productos as $producto)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $producto->codigo }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $producto->nombre }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-green-600 font-semibold">${{ number_format($producto->precioven, 2) }}</td>
                                <td class="px-6 py-3 text-center">
                                    <button wire:click="toggleDestacado('{{ $producto->codigo }}')" title="{{ $producto->destacado ? 'Quitar de destacados' : 'Destacar en la portada' }}"
                                            class="text-lg {{ $producto->destacado ? 'text-[#FFAF22]' : 'text-gray-300 hover:text-[#FFAF22]' }}">
                                        <i class="{{ $producto->destacado ? 'fas' : 'far' }} fa-star"></i>
                                    </button>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <input type="number" step="0.001" min="0" value="{{ $producto->peso !== null ? rtrim(rtrim(number_format((float) $producto->peso, 3, '.', ''), '0'), '.') : '' }}"
                                           wire:change="guardarPeso('{{ $producto->codigo }}', $event.target.value)" placeholder="—"
                                           class="w-24 px-2 py-1 border border-gray-300 rounded-lg text-sm text-right">
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @if($producto->foto_url)
                                        <img src="{{ $producto->foto_url }}" alt="{{ $producto->nombre }}" class="w-12 h-12 object-cover rounded-lg mx-auto border border-gray-200">
                                    @else
                                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mx-auto">
                                            <i class="fas fa-image text-gray-300 text-lg"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="seleccionarProducto('{{ $producto->codigo }}')"
                                                class="px-3 py-1.5 bg-[#FFAF22] text-white rounded-lg hover:bg-[#E89B0A] transition text-xs font-semibold">
                                            <i class="fas fa-images mr-1"></i>{{ $producto->foto_url ? 'Fotos' : 'Subir' }}
                                        </button>
                                        @if($producto->foto_url)
                                            <button @click="confirmarEliminar = '{{ $producto->codigo }}'"
                                                    class="px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-xs font-semibold">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fas fa-search text-3xl mb-2"></i>
                                    <p class="text-sm">No se encontraron productos</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile: Cards -->
            <div class="md:hidden divide-y divide-gray-200">
                @forelse($productos as $producto)
                    <div class="p-4 flex items-center gap-3">
                        @if($producto->foto_url)
                            <img src="{{ $producto->foto_url }}" alt="{{ $producto->nombre }}" class="w-14 h-14 object-cover rounded-lg border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-14 h-14 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-image text-gray-300 text-xl"></i>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $producto->nombre }}</p>
                            <p class="text-xs text-gray-500">Cod: {{ $producto->codigo }}</p>
                            <p class="text-xs text-green-600 font-semibold">${{ number_format($producto->precioven, 2) }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button wire:click="seleccionarProducto('{{ $producto->codigo }}')"
                                    class="p-2 bg-[#FFAF22] text-white rounded-lg hover:bg-[#E89B0A] transition">
                                <i class="fas fa-upload text-sm"></i>
                            </button>
                            @if($producto->foto_url)
                                <button @click="confirmarEliminar = '{{ $producto->codigo }}'"
                                        class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">
                        <i class="fas fa-search text-3xl mb-2"></i>
                        <p class="text-sm">No se encontraron productos</p>
                    </div>
                @endforelse
            </div>

            @if($productos->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $productos->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Modal de subida de foto de producto -->
    @if($productoSeleccionado)
        <div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             x-transition.opacity>
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col" @click.away="$wire.cerrarModal()">
                <div class="bg-[#FFAF22] px-6 py-4 rounded-t-xl flex items-center justify-between">
                    <h3 class="text-lg font-primary font-semibold text-white">
                        <i class="fas fa-images mr-2"></i>Fotos del producto
                    </h3>
                    <button wire:click="cerrarModal" class="text-white hover:text-gray-200 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4 overflow-y-auto">
                    <p class="text-sm text-gray-600 font-secondary">
                        Producto: <span class="font-semibold text-gray-800">{{ $productoSeleccionado }}</span>
                        <span class="text-xs text-gray-400 ml-2">La principal se ve en la grilla; las demás, en la ficha.</span>
                    </p>

                    @if($mensajeGaleria)
                        <p class="text-xs px-3 py-2 rounded-lg {{ str_contains($mensajeGaleria, 'Error') ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-700' }}">{{ $mensajeGaleria }}</p>
                    @endif

                    {{-- Galeria actual --}}
                    @if(count($galeria))
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach($galeria as $i => $g)
                                <div class="relative rounded-lg border {{ $g['principal'] ? 'border-[#FFAF22] ring-2 ring-[#FFAF22]/40' : 'border-gray-200' }} bg-gray-50 p-1" wire:key="gal-{{ $g['id'] }}">
                                    <img src="{{ $g['url_thumb'] }}" class="w-full aspect-square object-contain rounded">
                                    @if($g['principal'])
                                        <span class="absolute top-1 left-1 text-[9px] font-semibold px-1.5 py-0.5 rounded-full bg-[#FFAF22] text-white">Principal</span>
                                    @endif
                                    <div class="flex items-center justify-between mt-1">
                                        <div class="flex gap-0.5">
                                            <button wire:click="moverGaleria({{ $g['id'] }}, 'arriba')" @disabled($i === 0) class="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-30" title="Antes"><i class="fas fa-arrow-left text-xs"></i></button>
                                            <button wire:click="moverGaleria({{ $g['id'] }}, 'abajo')" @disabled($i === count($galeria) - 1) class="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-30" title="Después"><i class="fas fa-arrow-right text-xs"></i></button>
                                        </div>
                                        <div class="flex gap-0.5">
                                            @if(!$g['principal'])
                                                <button wire:click="principalGaleria({{ $g['id'] }})" class="p-1 text-gray-400 hover:text-[#FFAF22]" title="Hacer principal"><i class="far fa-star text-xs"></i></button>
                                            @endif
                                            <button wire:click="eliminarFotoGaleria({{ $g['id'] }})" wire:confirm="¿Eliminar esta foto?" class="p-1 text-gray-400 hover:text-red-500" title="Eliminar"><i class="fas fa-trash text-xs"></i></button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 text-center py-4"><i class="fas fa-image text-2xl block mb-1 text-gray-300"></i>Este producto todavía no tiene fotos.</p>
                    @endif

                    {{-- Subir nuevas --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-sm font-secondary font-medium text-gray-700 mb-2">Agregar fotos (podés elegir varias)</label>
                        <input type="file" wire:model="fotosNuevas" accept="image/*" multiple
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#FFAF22] file:text-white hover:file:bg-[#E89B0A] file:cursor-pointer">
                        @error('fotosNuevas') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        @error('fotosNuevas.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        @if(count($fotosNuevas))
                            <div class="flex gap-2 mt-3 flex-wrap">
                                @foreach($fotosNuevas as $fn)
                                    <img src="{{ $fn->temporaryUrl() }}" class="w-16 h-16 object-contain rounded-lg border border-gray-200 bg-gray-50">
                                @endforeach
                            </div>
                        @endif
                        <div wire:loading wire:target="fotosNuevas" class="flex items-center gap-2 text-sm text-gray-500 mt-2">
                            <i class="fas fa-spinner fa-spin text-[#FFAF22]"></i> Cargando imágenes...
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex gap-3">
                    <button wire:click="cerrarModal"
                            class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold text-sm">
                        Cerrar
                    </button>
                    <button wire:click="subirFoto" wire:loading.attr="disabled" wire:target="subirFoto,fotosNuevas"
                            {{ !count($fotosNuevas) ? 'disabled' : '' }}
                            class="flex-1 px-4 py-2.5 bg-[#FFAF22] text-white rounded-lg hover:bg-[#E89B0A] transition font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="subirFoto"><i class="fas fa-cloud-upload-alt mr-1"></i>Subir {{ count($fotosNuevas) > 1 ? count($fotosNuevas) . ' fotos' : '' }}</span>
                        <span wire:loading wire:target="subirFoto"><i class="fas fa-spinner fa-spin mr-1"></i> Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal confirmación eliminar -->
    <div x-show="confirmarEliminar"
         x-transition.opacity
         class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="confirmarEliminar = null"
             x-transition
             class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6">
            <div class="flex items-center justify-center w-14 h-14 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fas fa-trash text-red-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-primary font-semibold text-gray-900 text-center mb-2">Eliminar foto</h3>
            <p class="text-sm text-gray-600 text-center mb-6 font-secondary">Esta accion eliminara la foto del producto. No se puede deshacer.</p>
            <div class="flex gap-3">
                <button @click="confirmarEliminar = null"
                        class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold text-sm">
                    Cancelar
                </button>
                <button @click="$wire.eliminarFoto(confirmarEliminar); confirmarEliminar = null"
                        class="flex-1 px-4 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-semibold text-sm">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
