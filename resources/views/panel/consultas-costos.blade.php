@php
    $pesos = fn ($v) => '$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<div class="space-y-4 w-full max-w-3xl mx-auto"
     x-data="escanerCodigos()"
     x-on:consultas-enfocar.window="enfocar()">

    <!-- Mensaje -->
    @if($mensaje)
        <div class="rounded-xl p-4 flex items-start gap-3 {{ $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' }}">
            <i class="fas {{ $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }} text-lg mt-0.5"></i>
            <span class="font-secondary text-sm">{{ $mensaje }}</span>
            <button wire:click="$set('mensaje', '')" class="ml-auto text-lg leading-none">&times;</button>
        </div>
    @endif

    <!-- Buscador -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex gap-2">
            <button type="button" x-on:click="abrir()"
                    class="flex-shrink-0 w-14 h-12 rounded-lg bg-[#FFAF22] text-white text-xl flex items-center justify-center shadow hover:bg-[#FFAF22]/90"
                    title="Escanear con la cámara">
                <i class="fas fa-camera"></i>
            </button>
            <input type="search"
                   x-ref="busqueda"
                   wire:model="busqueda"
                   wire:keydown.enter="buscar"
                   enterkeyhint="search"
                   autocomplete="off"
                   placeholder="Código de barras o nombre"
                   class="flex-1 min-w-0 h-12 px-3 text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
            <button type="button" wire:click="buscar"
                    class="flex-shrink-0 w-12 h-12 rounded-lg bg-[#1E1B35] text-white flex items-center justify-center">
                <i class="fas fa-search" wire:loading.remove wire:target="buscar"></i>
                <i class="fas fa-spinner fa-spin" wire:loading wire:target="buscar"></i>
            </button>
        </div>
        <p class="text-xs text-gray-500 mt-2">Escaneá con la cámara, usá el lector o escribí el código o parte del nombre.</p>

        <!-- Resultados por nombre -->
        @if(!empty($resultados))
            <ul class="mt-3 divide-y divide-gray-100 border border-gray-200 rounded-lg max-h-96 overflow-y-auto">
                @foreach($resultados as $r)
                    <li wire:key="res-{{ $r['codigo'] }}">
                        <button type="button" wire:click="seleccionar('{{ $r['codigo'] }}')"
                                class="w-full text-left px-3 py-3 hover:bg-gray-50 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $r['nombre'] }}</p>
                                <p class="text-xs text-gray-500">{{ $r['codigo'] }} · Stock {{ rtrim(rtrim(number_format((float) $r['stockact'], 3, ',', '.'), '0'), ',') }}</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">{{ $pesos($r['precioven']) }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @elseif($sinResultados)
            <p class="mt-3 text-sm text-gray-500">No encontramos artículos con "{{ $busqueda }}".</p>
        @endif
    </div>

    <!-- Ficha del artículo -->
    @if($articulo)
        @php
            $stock = (float) $articulo['stockact'];
            $stockBajo = (float) $articulo['stockmin'] > 0 && $stock < (float) $articulo['stockmin'];
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 leading-tight">{{ $articulo['nombre'] }}</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Código {{ $articulo['codigo'] }}
                        @if($articulo['fecmod']) · Último cambio {{ \Carbon\Carbon::parse($articulo['fecmod'])->format('d/m/Y') }} @endif
                    </p>
                </div>
                <button wire:click="cerrarArticulo" class="text-gray-400 hover:text-gray-600 text-xl leading-none px-1">&times;</button>
            </div>

            <!-- Stock y precios actuales -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-gray-100">
                <div class="bg-white p-3">
                    <p class="text-xs text-gray-500">Stock</p>
                    <p class="text-lg font-bold {{ $stock <= 0 || $stockBajo ? 'text-red-600' : 'text-gray-900' }}">
                        {{ rtrim(rtrim(number_format($stock, 3, ',', '.'), '0'), ',') }}
                        <span class="text-xs font-normal text-gray-500">{{ $articulo['unimed'] }}</span>
                    </p>
                    @if($stockBajo)
                        <p class="text-[11px] text-red-600">Debajo del mínimo ({{ rtrim(rtrim(number_format((float) $articulo['stockmin'], 3, ',', '.'), '0'), ',') }})</p>
                    @endif
                </div>
                <div class="bg-white p-3">
                    <p class="text-xs text-gray-500">Costo actual</p>
                    <p class="text-lg font-bold text-gray-900">{{ $pesos($articulo['preciocos']) }}</p>
                </div>
                <div class="bg-white p-3">
                    <p class="text-xs text-gray-500">Venta actual</p>
                    <p class="text-lg font-bold text-gray-900">{{ $pesos($articulo['precioven']) }}</p>
                </div>
                <div class="bg-white p-3">
                    <p class="text-xs text-gray-500">Reventa actual</p>
                    <p class="text-lg font-bold text-gray-900">{{ $pesos($articulo['reventa']) }}</p>
                </div>
            </div>

            <!-- Avisos -->
            @if($pendiente)
                <div class="mx-4 mt-4 rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800 flex items-start gap-2">
                    <i class="fas fa-clock mt-0.5"></i>
                    <div class="flex-1">
                        Ya tiene un cambio pendiente ({{ $pendiente['usuario'] === \App\Services\PreciosPosdatadosService::USUARIO ? 'cargado desde el panel' : 'cargado en gestión' }}
                        el {{ \Carbon\Carbon::parse($pendiente['fecalta'])->format('d/m') }}): venta {{ $pesos($pendiente['precioven']) }}.
                        Si guardás, lo reemplazás.
                    </div>
                    <button wire:click="quitarPendiente({{ $pendiente['id'] }})"
                            wire:confirm="¿Quitar el cambio pendiente de este artículo?"
                            class="text-amber-700 hover:text-amber-900 text-xs font-semibold whitespace-nowrap">
                        Quitar
                    </button>
                </div>
            @endif
            @if($grupo)
                <div class="mx-4 mt-4 rounded-lg bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800" x-data="{ ver: false }">
                    <i class="fas fa-link mr-1"></i>
                    Está en un grupo de precios: al aplicarlo también cambian
                    <button type="button" class="underline font-semibold" x-on:click="ver = !ver">
                        {{ count($grupo['articulos']) }} {{ count($grupo['articulos']) === 1 ? 'artículo más' : 'artículos más' }}
                    </button>.
                    <ul x-show="ver" x-collapse class="mt-2 list-disc list-inside text-xs space-y-0.5">
                        @foreach($grupo['articulos'] as $nombreGrupo)
                            <li>{{ $nombreGrupo }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Costo nuevo -->
            <div class="p-4 space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <h4 class="text-sm font-semibold text-gray-800">Costo nuevo</h4>
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" wire:model.live="usarBulto" class="rounded border-gray-300 text-[#FFAF22] focus:ring-[#FFAF22]">
                        Tengo el precio del bulto
                    </label>
                </div>

                @if($usarBulto)
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Precio del bulto</label>
                            <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="costoBulto"
                                   class="w-full h-12 px-3 text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Unidades por bulto</label>
                            <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="uxBulto"
                                   class="w-full h-12 px-3 text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">
                            Costo por unidad {{ $calculo && $calculo['base']['siniva'] ? 'sin IVA' : 'con IVA' }}
                        </label>
                        <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="costo"
                               @if($usarBulto) readonly @endif
                               class="w-full h-12 px-3 text-lg font-semibold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22] {{ $usarBulto ? 'bg-gray-50' : '' }}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">% utilidad venta</label>
                        <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="porcentaje"
                               class="w-full h-12 px-3 text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">% utilidad reventa</label>
                        <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="porcentajeRev"
                               class="w-full h-12 px-3 text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                    </div>
                </div>

                <!-- Cómo quedan los precios -->
                @if($calculo)
                    @php
                        $dif = fn ($nuevo, $actual) => (float) $actual > 0 ? (($nuevo - (float) $actual) / (float) $actual) * 100 : null;
                        $filas = [
                            ['Costo final', $calculo['preciocos'], $articulo['preciocos']],
                            ['Venta', $calculo['precioven'], $articulo['precioven']],
                            ['Reventa', $calculo['reventa'], $articulo['reventa']],
                        ];
                    @endphp
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="grid grid-cols-3 bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500 px-3 py-2">
                            <span></span><span class="text-right">Actual</span><span class="text-right">Nuevo</span>
                        </div>
                        @foreach($filas as [$titulo, $nuevo, $actual])
                            @php $d = $dif($nuevo, $actual); @endphp
                            <div class="grid grid-cols-3 items-center px-3 py-2 border-t border-gray-100 text-sm">
                                <span class="text-gray-600">{{ $titulo }}</span>
                                <span class="text-right text-gray-500">{{ $pesos($actual) }}</span>
                                <span class="text-right font-semibold text-gray-900">
                                    {{ $pesos($nuevo) }}
                                    @if($d !== null && abs($d) >= 0.01)
                                        <span class="block text-[11px] font-normal {{ $d > 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $d > 0 ? '+' : '' }}{{ number_format($d, 1, ',', '.') }}%
                                        </span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-500">
                        Se calcula con los descuentos, flete, impuesto interno e IVA ({{ rtrim(rtrim(number_format($calculo['base']['tasa'], 2, ',', ''), '0'), ',') }}%) que tiene cargados el artículo, igual que en gestión.
                    </p>
                @endif

                <div>
                    <label class="block text-xs text-gray-600 mb-1">Observación (opcional)</label>
                    <input type="text" wire:model="observa" maxlength="255" placeholder="Ej.: Mayorista Vital, 19/10"
                           class="w-full h-11 px-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                </div>

                <button wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar"
                        class="w-full h-12 rounded-lg bg-[#FFAF22] text-white font-semibold shadow hover:bg-[#FFAF22]/90 disabled:opacity-60">
                    <span wire:loading.remove wire:target="guardar"><i class="fas fa-clock mr-2"></i>Guardar como precio posdatado</span>
                    <span wire:loading wire:target="guardar"><i class="fas fa-spinner fa-spin mr-2"></i>Guardando...</span>
                </button>
                <p class="text-[11px] text-gray-500 text-center">Los precios no cambian todavía: se aplican cuando proceses los precios posdatados en gestión.</p>
            </div>
        </div>
    @endif

    <!-- Pendientes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <button type="button" wire:click="$toggle('verPendientes')" class="w-full flex items-center justify-between p-4">
            <span class="text-sm font-semibold text-gray-800">
                <i class="fas fa-clock text-[#FFAF22] mr-2"></i>Cambios pendientes de aplicar
                <span class="ml-1 inline-flex items-center justify-center min-w-[1.5rem] px-1.5 h-6 rounded-full bg-gray-100 text-gray-700 text-xs">{{ count($pendientes) }}</span>
            </span>
            <i class="fas {{ $verPendientes ? 'fa-chevron-up' : 'fa-chevron-down' }} text-gray-400"></i>
        </button>
        @if($verPendientes)
            @if(empty($pendientes))
                <p class="px-4 pb-4 text-sm text-gray-500">No hay cambios pendientes.</p>
            @else
                <ul class="divide-y divide-gray-100 border-t border-gray-100">
                    @foreach($pendientes as $pd)
                        <li wire:key="pend-{{ $pd['id'] }}" class="flex items-center gap-2 px-4 py-3">
                            <button type="button" wire:click="seleccionar('{{ $pd['codigo'] }}')" class="flex-1 min-w-0 text-left">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $pd['nombre'] }}</p>
                                <p class="text-xs text-gray-500">
                                    Venta {{ $pesos($pd['ven_actual']) }} → <span class="font-semibold text-gray-700">{{ $pesos($pd['precioven']) }}</span>
                                    · {{ $pd['usuario'] === \App\Services\PreciosPosdatadosService::USUARIO ? 'Panel' : 'Gestión' }}
                                    @if($pd['fecalta']) {{ \Carbon\Carbon::parse($pd['fecalta'])->format('d/m') }} @endif
                                </p>
                            </button>
                            <button type="button" wire:click="quitarPendiente({{ $pd['id'] }})"
                                    wire:confirm="¿Quitar el cambio pendiente de {{ $pd['nombre'] }}?"
                                    class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>

    <!-- Lector con la cámara -->
    <div wire:ignore x-show="abierto" x-cloak class="fixed inset-0 z-[60] bg-black/90 flex flex-col">
        <div class="flex items-center justify-between p-4 text-white">
            <span class="text-sm font-semibold">Apuntá al código de barras</span>
            <button type="button" x-on:click="cerrar()" class="text-2xl leading-none px-2">&times;</button>
        </div>
        <div class="flex-1 flex items-center justify-center px-4">
            <div id="lector-codigos" class="w-full max-w-md rounded-xl overflow-hidden bg-black"></div>
        </div>
        <p x-show="error" x-text="error" class="mx-4 mb-3 text-sm text-red-300 text-center"></p>
        <div class="p-4">
            <button type="button" x-on:click="cerrar()" class="w-full h-12 rounded-lg bg-white/10 text-white font-semibold">Cancelar</button>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('escanerCodigos', () => ({
        abierto: false,
        error: '',
        lector: null,
        leyendo: false,

        cargarLibreria() {
            if (window.Html5Qrcode) return Promise.resolve();
            return new Promise((ok, mal) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';
                s.onload = ok;
                s.onerror = mal;
                document.head.appendChild(s);
            });
        },

        async abrir() {
            this.error = '';
            this.abierto = true;
            if (!window.isSecureContext) {
                this.error = 'La cámara solo funciona si el panel se abre con https.';
                return;
            }
            try {
                await this.cargarLibreria();
            } catch (e) {
                this.error = 'No se pudo cargar el lector. Revisá la conexión a internet.';
                return;
            }
            await this.$nextTick();

            const F = window.Html5QrcodeSupportedFormats;
            this.lector = new window.Html5Qrcode('lector-codigos', {
                verbose: false,
                formatsToSupport: [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.CODE_39, F.ITF],
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            });
            try {
                await this.lector.start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        qrbox: (w, h) => ({ width: Math.floor(w * 0.85), height: Math.max(80, Math.floor(Math.min(w, h) * 0.4)) }),
                    },
                    (texto) => this.leido(texto),
                    () => {}
                );
            } catch (e) {
                this.error = 'No pudimos usar la cámara. Revisá que el navegador tenga permiso para usarla.';
            }
        },

        async cerrar() {
            try {
                if (this.lector && this.lector.isScanning) await this.lector.stop();
                if (this.lector) this.lector.clear();
            } catch (e) {}
            this.lector = null;
            this.abierto = false;
        },

        async leido(texto) {
            if (this.leyendo) return;
            this.leyendo = true;
            if (navigator.vibrate) navigator.vibrate(80);
            await this.cerrar();
            this.leyendo = false;
            $wire.escaneado(texto);
        },

        enfocar() {
            // En el celular no se enfoca para que no salte el teclado
            if (window.innerWidth >= 640 && this.$refs.busqueda) this.$refs.busqueda.focus();
        },

        destroy() {
            this.cerrar();
        },
    }));
</script>
@endscript
