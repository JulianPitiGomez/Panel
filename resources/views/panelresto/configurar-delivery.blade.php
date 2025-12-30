<div x-data="deliveryConfig()" x-init="init()">
    <!-- Mensajes -->
    <div x-show="mensaje.visible"
         x-transition
         :class="mensaje.tipo === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
         class="border px-4 py-3 rounded relative mb-4"
         role="alert">
        <span class="block sm:inline" x-text="mensaje.texto"></span>
    </div>

    <!-- Card de configuración -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-[#FFAF22] to-[#FFAF22]/90 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-white">
                <i class="fas fa-motorcycle mr-2"></i>Configuración de Delivery
            </h2>
            <p class="text-sm text-white mt-1">Configure las zonas de entrega y opciones de delivery</p>
        </div>

        <div class="p-6">
            <!-- Ubicación Geográfica -->
            <div class="mb-6">
                <h3 class="text-md font-semibold text-gray-800 mb-4">
                    <i class="fas fa-map-marker-alt mr-2 text-[#FFAF22]"></i>Ubicación del Local
                </h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Mapa -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <b>Ubicación geográfica</b>
                        </label>
                        <p class="text-xs text-gray-500 mb-2">Arrastrá el marcador hasta tu dirección</p>
                        <div wire:ignore>
                            <div id="mapCanvas" class="w-full h-80 border-2 border-[#FFAF22]/40 rounded-lg bg-gray-100"></div>
                        </div>
                    </div>

                    <!-- Búsqueda y coordenadas -->
                    <div class="space-y-4">
                        <div>
                            <label for="search_input" class="block text-sm font-medium text-gray-700 mb-2">
                                Ubicá tu local:
                            </label>
                            <input type="text"
                                   id="search_input"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]"
                                   placeholder="Ingrese dirección">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Coordenadas Google Maps:
                            </label>
                            <input wire:model="ubicacion"
                                   type="text"
                                   readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                        </div>

                        <div>
                            <label for="kmentrega" class="block text-sm font-medium text-gray-700 mb-2">
                                Radio de zona de entrega (km):
                            </label>
                            <input wire:model.live="kmentrega"
                                   type="number"
                                   step="0.01"
                                   id="kmentrega"
                                   @change="cambiarRadio($event.target.value)"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        </div>

                        <div class="flex items-center">
                            <input wire:model.live="porzona"
                                   type="checkbox"
                                   id="conzonas"
                                   @change="toggleZonas($event.target.checked)"
                                   class="w-4 h-4 text-[#FFAF22] bg-gray-100 border-gray-300 rounded focus:ring-[#FFAF22]">
                            <label for="conzonas" class="ml-2 text-sm font-medium text-gray-700">
                                Definir por zonas
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zonas de entrega -->
            <div x-show="$wire.porzona" x-transition class="mb-6">
                <h3 class="text-md font-semibold text-gray-800 mb-4">
                    <i class="fas fa-draw-polygon mr-2 text-[#FFAF22]"></i>Zonas de Entrega
                </h3>

                <button type="button"
                        @click="agregarZona()"
                        x-show="!dibujando"
                        class="w-full sm:w-auto px-4 py-2 bg-[#FFAF22] text-white rounded-md hover:bg-[#FFAF22]/90 transition-colors mb-4">
                    <i class="fas fa-plus mr-2"></i>Definir nueva zona
                </button>

                <div id="zonasContainer" class="space-y-3">
                    @foreach($zonas as $index => $zona)
                        <div class="bg-gray-50 p-4 rounded-lg zona-item" data-index="{{ $index }}">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <input type="hidden" class="zona-id" value="{{ $zona->id }}">
                                <input type="hidden" class="zona-poligono" value="{{ $zona->poligono }}">

                                <div class="sm:col-span-4">
                                    <input type="text"
                                           class="zona-nombre w-full px-3 py-2 border border-gray-300 rounded-md"
                                           value="{{ $zona->nombre }}"
                                           placeholder="Nombre zona"
                                           style="color: {{ ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'][$index % 12] }}">
                                </div>

                                <div class="sm:col-span-3">
                                    <input type="number"
                                           step="0.01"
                                           class="zona-precio w-full px-3 py-2 border border-gray-300 rounded-md"
                                           value="{{ $zona->precio }}"
                                           placeholder="Precio">
                                </div>

                                <div class="sm:col-span-3 flex items-center">
                                    <input type="checkbox"
                                           class="zona-habilitada w-4 h-4 text-[#FFAF22] bg-gray-100 border-gray-300 rounded focus:ring-[#FFAF22]"
                                           {{ $zona->habilitada ? 'checked' : '' }}>
                                    <label class="ml-2 text-sm text-gray-700">Habilitada</label>
                                </div>

                                <div class="sm:col-span-2 flex items-center justify-end">
                                    <button type="button"
                                            @click="eliminarZona({{ $index }})"
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button"
                        @click="guardar()"
                        class="flex-1 px-6 py-3 bg-[#FFAF22] text-white rounded-md hover:bg-[#FFAF22]/90 transition-colors font-medium">
                    <i class="fas fa-save mr-2"></i>Guardar Configuración
                </button>
                <button type="button"
                        @click="$wire.$refresh()"
                        class="flex-1 px-6 py-3 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors font-medium">
                    <i class="fas fa-undo mr-2"></i>Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('deliveryConfig', () => ({
    map: null,
    marker: null,
    delivery: null,
    geocoder: null,
    drawingManager: null,
    poligonos: [],
    dibujando: false,
    mensaje: {
        visible: false,
        tipo: '',
        texto: ''
    },

    init() {
        this.loadGoogleMaps();

        this.$wire.on('mostrarMensaje', (event) => {
            this.mensaje.visible = true;
            this.mensaje.tipo = event[0].tipo;
            this.mensaje.texto = event[0].mensaje;
            setTimeout(() => {
                this.mensaje.visible = false;
            }, 5000);
        });
    },

    loadGoogleMaps() {
        if (typeof google !== 'undefined' && google.maps) {
            this.initMap();
            return;
        }

        if (!window.googleMapsLoading) {
            window.googleMapsLoading = true;
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${@js($googleMapsApiKey)}&libraries=drawing,places`;
            script.async = true;
            script.defer = true;
            script.onload = () => {
                window.googleMapsLoading = false;
                this.initMap();
            };
            script.onerror = () => {
                window.googleMapsLoading = false;
                document.getElementById('mapCanvas').innerHTML =
                    '<div class="flex items-center justify-center h-full text-gray-600 p-4 text-center">' +
                    '<div><i class="fas fa-exclamation-triangle text-4xl text-[#FFAF22] mb-2"></i>' +
                    '<p class="font-semibold">No se pudo cargar Google Maps</p>' +
                    '<p class="text-sm mt-2">Desactive bloqueadores de anuncios o extensiones que bloqueen Google Maps.</p></div></div>';
            };
            document.head.appendChild(script);
        }
    },

    initMap() {
        const coords = this.$wire.ubicacion.split(',').map(c => parseFloat(c.trim()));
        const latLng = new google.maps.LatLng(coords[0] || -34.60024897372449, coords[1] || -58.38179087851561);

        this.map = new google.maps.Map(document.getElementById('mapCanvas'), {
            zoom: 14,
            center: latLng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        this.delivery = new google.maps.Circle({
            strokeColor: "#FF0000",
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: "#FF0000",
            fillOpacity: 0.35,
            map: this.$wire.porzona ? null : this.map,
            center: latLng,
            radius: parseInt(this.$wire.kmentrega) * 1000
        });

        this.marker = new google.maps.Marker({
            position: latLng,
            map: this.map,
            draggable: true
        });

        google.maps.event.addListener(this.marker, 'dragend', () => {
            const pos = this.marker.getPosition();
            const ubicacion = pos.lat() + ', ' + pos.lng();
            this.$wire.call('actualizarUbicacion', ubicacion);
            this.delivery.setCenter(pos);
        });

        // Autocomplete
        const searchInput = document.getElementById('search_input');
        const autocomplete = new google.maps.places.Autocomplete(searchInput, { types: ['geocode'] });

        google.maps.event.addListener(autocomplete, 'place_changed', () => {
            const place = autocomplete.getPlace();
            if (place.geometry) {
                const newLatLng = new google.maps.LatLng(
                    place.geometry.location.lat(),
                    place.geometry.location.lng()
                );
                this.marker.setPosition(newLatLng);
                this.map.panTo(newLatLng);
                const ubicacion = newLatLng.lat() + ', ' + newLatLng.lng();
                this.$wire.call('actualizarUbicacion', ubicacion);
                this.delivery.setCenter(newLatLng);
            }
        });

        // Cargar polígonos existentes
        this.cargarPoligonosExistentes();
    },

    cargarPoligonosExistentes() {
        const zonas = @js($zonas);
        const colores = ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'];

        zonas.forEach((zona, index) => {
            if (zona.poligono) {
                const paths = this.convertirPoligono(zona.poligono);
                const color = colores[index % 12];

                const polygon = new google.maps.Polygon({
                    paths: paths,
                    strokeColor: color,
                    fillColor: color,
                    fillOpacity: 0.35,
                    map: this.map
                });

                this.poligonos[index] = polygon;
            }
        });
    },

    convertirPoligono(poligonoStr) {
        const coordsStr = poligonoStr.replace(/\(/g, '').replace(/\)/g, '');
        const pares = coordsStr.split(',');
        const paths = [];

        for (let i = 0; i < pares.length; i += 2) {
            if (pares[i] && pares[i + 1]) {
                paths.push({
                    lat: parseFloat(pares[i]),
                    lng: parseFloat(pares[i + 1])
                });
            }
        }

        return paths;
    },

    cambiarRadio(radio) {
        if (this.delivery) {
            this.delivery.setRadius(parseInt(radio) * 1000);
        }
    },

    toggleZonas(checked) {
        if (this.delivery) {
            this.delivery.setMap(checked ? null : this.map);
        }
    },

    agregarZona() {
        this.dibujando = true;
        const colores = ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'];
        const index = this.poligonos.length;
        const color = colores[index % 12];

        this.drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON]
            },
            polygonOptions: {
                fillColor: color,
                strokeColor: color,
                fillOpacity: 0.35
            }
        });

        this.drawingManager.setMap(this.map);

        google.maps.event.addListener(this.drawingManager, 'overlaycomplete', (event) => {
            this.poligonos[index] = event.overlay;
            const path = event.overlay.getPath().getArray();
            const poligonoStr = path.map(p => `(${p.lat()},${p.lng()})`).join(',');

            const zonasContainer = document.getElementById('zonasContainer');
            const html = `
                <div class="bg-gray-50 p-4 rounded-lg zona-item" data-index="${index}">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <input type="hidden" class="zona-id" value="0">
                        <input type="hidden" class="zona-poligono" value="${poligonoStr}">
                        <div class="sm:col-span-4">
                            <input type="text" class="zona-nombre w-full px-3 py-2 border border-gray-300 rounded-md"
                                   placeholder="Nombre zona" style="color: ${color}">
                        </div>
                        <div class="sm:col-span-3">
                            <input type="number" step="0.01" class="zona-precio w-full px-3 py-2 border border-gray-300 rounded-md"
                                   placeholder="Precio">
                        </div>
                        <div class="sm:col-span-3 flex items-center">
                            <input type="checkbox" class="zona-habilitada w-4 h-4 text-[#FFAF22] bg-gray-100 border-gray-300 rounded focus:ring-[#FFAF22]" checked>
                            <label class="ml-2 text-sm text-gray-700">Habilitada</label>
                        </div>
                        <div class="sm:col-span-2 flex items-center justify-end">
                            <button type="button" @click="eliminarZona(${index})" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            zonasContainer.insertAdjacentHTML('beforeend', html);

            this.drawingManager.setMap(null);
            this.dibujando = false;
        });
    },

    eliminarZona(index) {
        if (this.poligonos[index]) {
            this.poligonos[index].setMap(null);
        }
        const zonaEl = document.querySelector(`[data-index="${index}"]`);
        if (zonaEl) {
            zonaEl.remove();
        }
    },

    guardar() {
        const zonasItems = document.querySelectorAll('.zona-item');
        const zonas = [];

        zonasItems.forEach(item => {
            const id = item.querySelector('.zona-id')?.value || 0;
            const nombre = item.querySelector('.zona-nombre')?.value || '';
            const precio = item.querySelector('.zona-precio')?.value || 0;
            const poligono = item.querySelector('.zona-poligono')?.value || '';
            const habilitada = item.querySelector('.zona-habilitada')?.checked || false;

            if (nombre && poligono) {
                zonas.push({ id, nombre, precio, poligono, habilitada });
            }
        });

        this.$wire.call('guardarConfiguracion', zonas);
    }
}));
</script>
@endscript
