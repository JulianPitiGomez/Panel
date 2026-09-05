<div x-data="enviosConfig()" x-init="init()" class="space-y-6 font-secondary">

    <p class="text-sm text-gray-500">Cómo entregás los pedidos de la tienda web y cuánto cobrás por el reparto.</p>

    @if($mensaje)
        <div class="px-4 py-3 rounded-xl text-sm {{ $tipoMensaje === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $mensaje }}
        </div>
    @endif

    {{-- Modalidades --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h3 class="font-primary font-semibold text-gray-800 mb-4"><i class="fas fa-sliders-h text-[#FFAF22] mr-2"></i>Formas de entrega</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
                <input type="checkbox" wire:model.live="config.retiro_local" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                <div>
                    <span class="text-sm font-medium text-gray-700">Retiro en el local</span>
                    <p class="text-xs text-gray-400">Sin costo. El comprador pasa a buscarlo.</p>
                </div>
            </label>
            <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
                <input type="checkbox" wire:model.live="config.flota_propia" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                <div>
                    <span class="text-sm font-medium text-gray-700">Reparto propio</span>
                    <p class="text-xs text-gray-400">Moto o camioneta, con zonas y tarifas que definís abajo.</p>
                </div>
            </label>
            <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
                <input type="checkbox" wire:model.live="config.a_convenir" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
                <div>
                    <span class="text-sm font-medium text-gray-700">Envío a convenir</span>
                    <p class="text-xs text-gray-400">El pedido entra sin costo de envío y lo arreglás con el cliente.</p>
                </div>
            </label>
        </div>
        <div class="mt-4" x-show="$wire.config.retiro_local">
            <label class="block text-sm font-medium text-gray-700 mb-1">Indicaciones para el retiro</label>
            <input type="text" wire:model="config.retiro_texto" maxlength="200" placeholder="Ej: Lunes a viernes de 9 a 18 hs, por 25 de Mayo 123"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
        </div>
    </div>

    {{-- Ubicacion del local (siempre) y zonas de reparto (solo con reparto propio) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h3 class="font-primary font-semibold text-gray-800 mb-1"><i class="fas fa-map-marker-alt text-[#FFAF22] mr-2"></i>Ubicación del local</h3>
        <p class="text-xs text-gray-400 mb-4">Buscá tu dirección o arrastrá el marcador hasta tu local. Es el punto de retiro y desde donde se miden las zonas de reparto.</p>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div class="lg:col-span-3" wire:ignore>
                <div id="buscador_origen" class="mb-2"></div>
                <div id="mapaEnvios" class="w-full rounded-xl border border-gray-200 bg-gray-100" style="height: 420px; min-height: 420px;"></div>
                <p class="text-[11px] text-gray-400 mt-1" x-show="dibujando">
                    <i class="fas fa-draw-polygon mr-1"></i> Hacé clic en el mapa para marcar cada vértice. Doble clic o clic en el primer punto para cerrar la zona.
                    <button type="button" @click="cancelarDibujo()" class="ml-2 text-red-500 hover:underline">Cancelar</button>
                </p>
            </div>

            <div class="lg:col-span-2 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección del local</label>
                    <input type="text" wire:model="config.origen_direccion" maxlength="160"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFAF22] focus:border-transparent">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código postal</label>
                        <input type="text" wire:model="config.origen_cp" maxlength="10"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coordenadas</label>
                        <input type="text" readonly value="{{ $config['origen_lat'] ?? '' }}{{ isset($config['origen_lat']) && $config['origen_lat'] !== null ? ', ' : '' }}{{ $config['origen_lng'] ?? '' }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs bg-gray-50 text-gray-500">
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100" x-show="$wire.config.flota_propia" x-transition>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-gray-700">Zonas de reparto</h4>
                        <div class="flex gap-1">
                            <button type="button" @click="agregarZona('radio')" class="px-2 py-1 text-xs rounded-lg bg-[#FFAF22]/15 text-[#B8860B] hover:bg-[#FFAF22]/30"><i class="fas fa-circle-notch mr-1"></i>Radio</button>
                            <button type="button" @click="agregarZona('cp')" class="px-2 py-1 text-xs rounded-lg bg-[#FFAF22]/15 text-[#B8860B] hover:bg-[#FFAF22]/30"><i class="fas fa-hashtag mr-1"></i>Cód. postal</button>
                            <button type="button" @click="agregarZona('poligono')" class="px-2 py-1 text-xs rounded-lg bg-[#FFAF22]/15 text-[#B8860B] hover:bg-[#FFAF22]/30"><i class="fas fa-draw-polygon mr-1"></i>Dibujar</button>
                        </div>
                    </div>

                    <div wire:ignore class="space-y-3 overflow-y-auto pr-1" style="max-height: 520px;">
                        <template x-if="zonas.length === 0">
                            <p class="text-xs text-gray-400 py-4 text-center">Todavía no definiste zonas. Agregá una por radio, por código postal o dibujala en el mapa.</p>
                        </template>
                        <template x-for="(z, i) in zonas" :key="z.uid">
                            <div class="rounded-xl border p-3 space-y-2" :style="'border-color:' + colorDe(i) + '66'">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" :style="'background:' + colorDe(i)"></span>
                                    <input type="text" x-model="z.nombre" placeholder="Nombre de la zona" class="flex-1 min-w-0 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                    <span class="text-[10px] uppercase text-gray-400" x-text="z.tipo === 'radio' ? 'radio' : (z.tipo === 'cp' ? 'cód. postal' : 'polígono')"></span>
                                    <button type="button" @click="quitarZona(i)" class="text-red-400 hover:text-red-600 p-1" title="Quitar"><i class="fas fa-trash-alt"></i></button>
                                </div>

                                <template x-if="z.tipo === 'radio'">
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="text-xs text-gray-500">Radio (km)
                                            <input type="number" step="0.1" min="0.1" x-model="z.radio_km" @input="redibujar()" class="w-full mt-0.5 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                        </label>
                                        <label class="text-xs text-gray-500">Extra por km ($)
                                            <input type="number" step="0.01" min="0" x-model="z.tarifa_por_km" class="w-full mt-0.5 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                        </label>
                                    </div>
                                </template>
                                <template x-if="z.tipo === 'cp'">
                                    <label class="text-xs text-gray-500 block">Códigos postales (separados por coma)
                                        <textarea x-model="z.cp_lista" rows="2" placeholder="6600, 6601, 6640" class="w-full mt-0.5 px-2 py-1.5 border border-gray-300 rounded-lg text-sm"></textarea>
                                    </label>
                                </template>
                                <template x-if="z.tipo === 'poligono'">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-500" x-text="z.poligono && z.poligono.length >= 3 ? z.poligono.length + ' vértices' : 'Sin dibujar'"></span>
                                        <button type="button" @click="dibujarZona(i)" :disabled="dibujando" class="px-2 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 disabled:opacity-40">
                                            <i class="fas fa-pencil-alt mr-1"></i><span x-text="z.poligono && z.poligono.length >= 3 ? 'Volver a dibujar' : 'Dibujar en el mapa'"></span>
                                        </button>
                                    </div>
                                </template>

                                <div class="grid grid-cols-3 gap-2">
                                    <label class="text-xs text-gray-500">Tarifa ($)
                                        <input type="number" step="0.01" min="0" x-model="z.tarifa" class="w-full mt-0.5 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                    </label>
                                    <label class="text-xs text-gray-500">Gratis desde ($)
                                        <input type="number" step="0.01" min="0" x-model="z.minimo_gratis" placeholder="nunca" class="w-full mt-0.5 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                    </label>
                                    <label class="text-xs text-gray-500">Demora
                                        <input type="text" x-model="z.demora_estimada" placeholder="24 a 48 hs" maxlength="60" class="w-full mt-0.5 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                    </label>
                                </div>
                                <label class="flex items-center gap-2 text-xs text-gray-600">
                                    <input type="checkbox" x-model="z.activa" class="w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]"> Zona activa
                                </label>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="button" @click="guardar()" wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-[#1E1B35] text-white rounded-lg hover:bg-[#2d2952] transition font-semibold text-sm disabled:opacity-50">
            <i class="fas fa-save mr-1"></i> Guardar envíos
        </button>
    </div>
</div>

@script
<script>
Alpine.data('enviosConfig', () => ({
    zonas: [],
    map: null, marker: null,
    circulos: {}, poligonos: {},
    dibujando: false, dibujoIdx: null, dibujoVertices: [], dibujoMarkers: [], dibujoPolyline: null,
    dibujoClick: null, dibujoDbl: null, dibujoTimer: null,
    colores: ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'],
    uidSeq: 1,

    init() {
        this.zonas = this.normalizar(JSON.parse(JSON.stringify(this.$wire.zonas || [])));
        this.$wire.on('envios-guardados', () => {
            this.zonas = this.normalizar(JSON.parse(JSON.stringify(this.$wire.zonas || [])));
            this.redibujar();
        });
        this.cargarGoogleMaps();
    },

    normalizar(lista) {
        return lista.map(z => ({
            id: z.id || 0, uid: this.uidSeq++,
            nombre: z.nombre || '', tipo: z.tipo || 'radio',
            poligono: Array.isArray(z.poligono) ? z.poligono : [],
            cp_lista: z.cp_lista || '', radio_km: z.radio_km || '',
            tarifa: z.tarifa || 0, tarifa_por_km: z.tarifa_por_km || 0,
            minimo_gratis: z.minimo_gratis || '', demora_estimada: z.demora_estimada || '',
            activa: z.activa !== false && z.activa !== 0,
        }));
    },

    colorDe(i) { return this.colores[i % this.colores.length]; },

    origen() {
        const lat = parseFloat(this.$wire.config.origen_lat), lng = parseFloat(this.$wire.config.origen_lng);
        return (isFinite(lat) && isFinite(lng)) ? { lat, lng } : { lat: -34.6037, lng: -58.3816 };
    },

    cargarGoogleMaps() {
        if (typeof google !== 'undefined' && google.maps && google.maps.Map) { this.initMap(); return; }
        if (window.googleMapsLoading) { const t = setInterval(() => { if (typeof google !== 'undefined' && google.maps && google.maps.Map) { clearInterval(t); this.initMap(); } }, 300); return; }
        window.googleMapsLoading = true;
        const s = document.createElement('script');
        s.src = `https://maps.googleapis.com/maps/api/js?key=${@js($googleMapsApiKey)}&libraries=places`;
        s.async = true; s.defer = true;
        s.onload = () => { window.googleMapsLoading = false; this.initMap(); };
        s.onerror = () => {
            window.googleMapsLoading = false;
            const el = document.getElementById('mapaEnvios');
            if (el) el.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 text-sm p-4 text-center">No se pudo cargar Google Maps. Podés seguir cargando zonas por código postal.</div>';
        };
        document.head.appendChild(s);
    },

    initMap() {
        const el = document.getElementById('mapaEnvios');
        if (!el || this.map) return;
        const o = this.origen();
        this.map = new google.maps.Map(el, { zoom: 13, center: o, mapTypeId: google.maps.MapTypeId.ROADMAP });
        this.marker = new google.maps.Marker({ position: o, map: this.map, draggable: true, title: 'Tu local' });
        google.maps.event.addListener(this.marker, 'dragend', () => {
            const p = this.marker.getPosition();
            this.$wire.actualizarOrigen(p.lat(), p.lng());
            this.recentrarCirculos(p);
        });
        this.initBuscador();
        this.redibujar();
    },

    initBuscador() {
        try {
            const c = document.getElementById('buscador_origen');
            if (!c || !google.maps.places || !google.maps.places.PlaceAutocompleteElement || c.querySelector('gmp-place-autocomplete')) return;
            const ac = new google.maps.places.PlaceAutocompleteElement();
            ac.style.width = '100%';
            c.appendChild(ac);
            ac.addEventListener('gmp-select', async ({ placePrediction }) => {
                const place = placePrediction.toPlace();
                await place.fetchFields({ fields: ['location', 'formattedAddress'] });
                if (!place.location) return;
                const p = new google.maps.LatLng(place.location.lat(), place.location.lng());
                this.marker.setPosition(p); this.map.panTo(p);
                this.$wire.actualizarOrigen(p.lat(), p.lng(), place.formattedAddress || null);
                this.recentrarCirculos(p);
            });
        } catch (e) { console.warn('Buscador de direcciones no disponible', e); }
    },

    recentrarCirculos(p) { Object.values(this.circulos).forEach(c => c.setCenter(p)); },

    limpiarMapa() {
        Object.values(this.circulos).forEach(c => c.setMap(null));
        Object.values(this.poligonos).forEach(p => p.setMap(null));
        this.circulos = {}; this.poligonos = {};
    },

    redibujar() {
        if (!this.map) return;
        this.limpiarMapa();
        const o = this.marker ? this.marker.getPosition() : this.origen();
        this.zonas.forEach((z, i) => {
            const color = this.colorDe(i);
            if (z.tipo === 'radio' && parseFloat(z.radio_km) > 0) {
                this.circulos[z.uid] = new google.maps.Circle({
                    map: this.map, center: o, radius: parseFloat(z.radio_km) * 1000,
                    strokeColor: color, strokeOpacity: 0.9, strokeWeight: 2, fillColor: color, fillOpacity: 0.12, clickable: false,
                });
            }
            if (z.tipo === 'poligono' && z.poligono && z.poligono.length >= 3) {
                const poly = new google.maps.Polygon({
                    map: this.map, paths: z.poligono.map(p => ({ lat: p[0], lng: p[1] })),
                    strokeColor: color, fillColor: color, fillOpacity: 0.25, editable: true, draggable: true,
                });
                const sync = () => { z.poligono = poly.getPath().getArray().map(p => [p.lat(), p.lng()]); };
                const path = poly.getPath();
                ['insert_at', 'set_at', 'remove_at'].forEach(ev => google.maps.event.addListener(path, ev, sync));
                google.maps.event.addListener(poly, 'dragend', sync);
                this.poligonos[z.uid] = poly;
            }
        });
    },

    agregarZona(tipo) {
        this.zonas.push({ id: 0, uid: this.uidSeq++, nombre: '', tipo, poligono: [], cp_lista: '', radio_km: tipo === 'radio' ? 5 : '', tarifa: 0, tarifa_por_km: 0, minimo_gratis: '', demora_estimada: '', activa: true });
        if (tipo === 'poligono') { this.$nextTick(() => this.dibujarZona(this.zonas.length - 1)); }
        else { this.redibujar(); }
    },

    quitarZona(i) {
        if (this.dibujando && this.dibujoIdx === i) this.cancelarDibujo();
        this.zonas.splice(i, 1);
        this.redibujar();
    },

    dibujarZona(i) {
        if (!this.map || this.dibujando) return;
        const z = this.zonas[i];
        if (this.poligonos[z.uid]) { this.poligonos[z.uid].setMap(null); delete this.poligonos[z.uid]; }
        this.dibujando = true; this.dibujoIdx = i; this.dibujoVertices = []; this.dibujoMarkers = [];
        this.map.setOptions({ disableDoubleClickZoom: true, draggableCursor: 'crosshair', clickableIcons: false });
        this.dibujoPolyline = new google.maps.Polyline({ map: this.map, path: [], strokeColor: this.colorDe(i), strokeWeight: 2 });
        this.dibujoClick = this.map.addListener('click', (e) => {
            if (this.dibujoTimer) clearTimeout(this.dibujoTimer);
            const ll = e.latLng;
            this.dibujoTimer = setTimeout(() => { this.dibujoTimer = null; this.agregarVertice(ll); }, 220);
        });
        this.dibujoDbl = this.map.addListener('dblclick', () => { if (this.dibujoTimer) { clearTimeout(this.dibujoTimer); this.dibujoTimer = null; } this.finalizarDibujo(); });
    },

    agregarVertice(ll) {
        this.dibujoVertices.push(ll);
        this.dibujoPolyline.setPath(this.dibujoVertices);
        const m = new google.maps.Marker({ position: ll, map: this.map, icon: { path: google.maps.SymbolPath.CIRCLE, scale: 5, fillColor: this.colorDe(this.dibujoIdx), fillOpacity: 1, strokeColor: '#fff', strokeWeight: 1 } });
        if (this.dibujoVertices.length === 1) m.addListener('click', () => { if (this.dibujoTimer) { clearTimeout(this.dibujoTimer); this.dibujoTimer = null; } this.finalizarDibujo(); });
        this.dibujoMarkers.push(m);
    },

    finalizarDibujo() {
        if (!this.dibujando) return;
        if (this.dibujoVertices.length < 3) { alert('Marcá al menos 3 puntos para definir la zona.'); return; }
        const z = this.zonas[this.dibujoIdx];
        z.poligono = this.dibujoVertices.map(p => [p.lat(), p.lng()]);
        this.limpiarDibujo();
        this.redibujar();
    },

    cancelarDibujo() { this.limpiarDibujo(); this.redibujar(); },

    limpiarDibujo() {
        if (this.dibujoClick) { google.maps.event.removeListener(this.dibujoClick); this.dibujoClick = null; }
        if (this.dibujoDbl) { google.maps.event.removeListener(this.dibujoDbl); this.dibujoDbl = null; }
        if (this.dibujoTimer) { clearTimeout(this.dibujoTimer); this.dibujoTimer = null; }
        if (this.dibujoPolyline) { this.dibujoPolyline.setMap(null); this.dibujoPolyline = null; }
        this.dibujoMarkers.forEach(m => m.setMap(null));
        this.dibujoMarkers = []; this.dibujoVertices = []; this.dibujando = false; this.dibujoIdx = null;
        if (this.map) this.map.setOptions({ disableDoubleClickZoom: false, draggableCursor: null, clickableIcons: true });
    },

    guardar() {
        if (this.dibujando) { alert('Terminá de dibujar la zona antes de guardar.'); return; }
        const payload = this.zonas.map(z => ({
            id: z.id, nombre: z.nombre, tipo: z.tipo, poligono: z.poligono, cp_lista: z.cp_lista,
            radio_km: z.radio_km, tarifa: z.tarifa, tarifa_por_km: z.tarifa_por_km,
            minimo_gratis: z.minimo_gratis, demora_estimada: z.demora_estimada, activa: !!z.activa,
        }));
        this.$wire.guardar(payload);
    },
}));
</script>
@endscript
