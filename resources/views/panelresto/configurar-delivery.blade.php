<div x-data="deliveryConfig()" x-init="init()">
    <!-- Mensajes -->
    <div x-show="mensaje.visible"
         x-transition
         :class="mensaje.tipo === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
         class="border px-4 py-3 rounded relative mb-4"
         role="alert">
        <span class="block sm:inline" x-text="mensaje.texto"></span>
    </div>

    <!-- Card de configuración Delivery -->
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
                            <div wire:ignore>
                                <div id="search_container" class="w-full"></div>
                            </div>
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
                                            class="zona-eliminar text-red-500 hover:text-red-700">
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
    poligonos: [],
    dibujando: false,
    // Estado del dibujo manual de zonas (reemplaza google.maps.drawing.DrawingManager)
    dibujoVertices: [],
    dibujoMarkers: [],
    dibujoPolyline: null,
    dibujoClickListener: null,
    dibujoDblClickListener: null,
    dibujoClickTimer: null,
    dibujoIdx: null,
    dibujoColor: null,
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
        if (typeof google !== 'undefined' && google.maps && google.maps.Map) {
            this.initMap();
            return;
        }

        if (!window.googleMapsLoading) {
            window.googleMapsLoading = true;
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${@js($googleMapsApiKey)}&libraries=places`;
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

        // Cargar polígonos existentes (antes del buscador, así no depende de él)
        this.cargarPoligonosExistentes();

        // Buscador de direcciones (PlaceAutocompleteElement reemplaza al viejo Autocomplete)
        this.initAutocomplete();
    },

    cargarPoligonosExistentes() {
        const colores = ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'];

        // Recorremos las filas del DOM (no dependemos de índices de Livewire).
        const filas = document.querySelectorAll('#zonasContainer .zona-item');
        filas.forEach((fila, index) => {
            const poligonoStr = fila.querySelector('.zona-poligono')?.value || '';
            if (!poligonoStr) return;

            const paths = this.convertirPoligono(poligonoStr);
            if (!paths.length) return;

            const color = colores[index % 12];
            const polygon = new google.maps.Polygon({
                paths: paths,
                strokeColor: color,
                fillColor: color,
                fillOpacity: 0.35,
                map: this.map,
                clickable: true,
                editable: true,
                draggable: true
            });

            this.poligonos.push(polygon);
            this.vincularZona(fila, polygon);
        });
    },

    // Conecta una fila de zona con su polígono: borrar y actualizar coordenadas al editar.
    vincularZona(fila, polygon) {
        // Botón borrar (cableado en JS, funciona también en filas inyectadas).
        const btn = fila.querySelector('.zona-eliminar');
        if (btn) {
            btn.addEventListener('click', () => {
                polygon.setMap(null);
                fila.remove();
            });
        }

        // Cuando se mueve un vértice o toda la zona, guardamos el nuevo polígono
        // en el input oculto, así se persiste al grabar.
        const actualizar = () => {
            const pathArr = polygon.getPath().getArray();
            const str = pathArr.map(p => `(${p.lat()},${p.lng()})`).join(',');
            const input = fila.querySelector('.zona-poligono');
            if (input) input.value = str;
        };

        const path = polygon.getPath();
        google.maps.event.addListener(path, 'insert_at', actualizar);
        google.maps.event.addListener(path, 'set_at', actualizar);
        google.maps.event.addListener(path, 'remove_at', actualizar);
        google.maps.event.addListener(polygon, 'dragend', actualizar);
    },

    initAutocomplete() {
        // El buscador es opcional: si falla (p. ej. Places API New no habilitada),
        // NO debe romper el mapa. Por eso todo va dentro de un try/catch.
        try {
            const container = document.getElementById('search_container');
            if (!container || !google.maps.places || !google.maps.places.PlaceAutocompleteElement) {
                return;
            }

            // Evita duplicar el elemento si Livewire re-inicializa el componente
            if (container.querySelector('gmp-place-autocomplete')) {
                return;
            }

            const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement();
            placeAutocomplete.style.width = '100%';
            container.appendChild(placeAutocomplete);

            // El evento moderno es 'gmp-select', que trae una predicción de lugar
            placeAutocomplete.addEventListener('gmp-select', async ({ placePrediction }) => {
                const place = placePrediction.toPlace();
                await place.fetchFields({ fields: ['location'] });

                const loc = place.location;
                if (!loc) return;

                const newLatLng = new google.maps.LatLng(loc.lat(), loc.lng());
                this.marker.setPosition(newLatLng);
                this.map.panTo(newLatLng);

                const ubicacion = loc.lat() + ', ' + loc.lng();
                this.$wire.call('actualizarUbicacion', ubicacion);
                this.delivery.setCenter(newLatLng);
            });
        } catch (e) {
            console.error('No se pudo inicializar el buscador de direcciones:', e);
        }
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
        if (this.dibujando) { return; }   // evita iniciar dos dibujos a la vez

        const colores = ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'];
        const index = this.poligonos.length;
        const color = colores[index % 12];

        this.iniciarDibujo(index, color);
    },

    // Inicia el modo de dibujo: cada click en el mapa agrega un vértice,
    // se cierra la zona con doble click o haciendo click en el primer vértice.
    iniciarDibujo(index, color) {
        this.dibujando = true;
        this.dibujoIdx = index;
        this.dibujoColor = color;
        this.dibujoVertices = [];
        this.dibujoMarkers = [];

        // Modo dibujo: cursor de cruz, sin zoom por doble click (lo uso para cerrar)
        // y sin íconos de lugares clickeables (evita que se abra el popup de la dirección).
        this.map.setOptions({
            disableDoubleClickZoom: true,
            draggableCursor: 'crosshair',
            clickableIcons: false
        });

        this.dibujoPolyline = new google.maps.Polyline({
            map: this.map,
            path: [],
            strokeColor: color,
            strokeWeight: 2,
            strokeOpacity: 0.9
        });

        // Click simple: agrega un vértice (con un pequeño retardo para
        // poder distinguirlo de un doble click sin agregar puntos de más).
        this.dibujoClickListener = this.map.addListener('click', (e) => {
            if (this.dibujoClickTimer) { clearTimeout(this.dibujoClickTimer); }
            const latLng = e.latLng;
            this.dibujoClickTimer = setTimeout(() => {
                this.dibujoClickTimer = null;
                this.agregarVertice(latLng);
            }, 220);
        });

        // Doble click: cierra y finaliza la zona.
        this.dibujoDblClickListener = this.map.addListener('dblclick', () => {
            if (this.dibujoClickTimer) { clearTimeout(this.dibujoClickTimer); this.dibujoClickTimer = null; }
            this.finalizarDibujo();
        });
    },

    agregarVertice(latLng) {
        this.dibujoVertices.push(latLng);
        this.dibujoPolyline.setPath(this.dibujoVertices);

        const marker = new google.maps.Marker({
            position: latLng,
            map: this.map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 5,
                fillColor: this.dibujoColor,
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 1
            }
        });

        // Al hacer click sobre el primer vértice se cierra el polígono.
        if (this.dibujoVertices.length === 1) {
            marker.addListener('click', () => {
                if (this.dibujoClickTimer) { clearTimeout(this.dibujoClickTimer); this.dibujoClickTimer = null; }
                this.finalizarDibujo();
            });
        }
        this.dibujoMarkers.push(marker);
    },

    finalizarDibujo() {
        if (!this.dibujando) { return; }
        if (this.dibujoVertices.length < 3) {
            alert('Hacé al menos 3 clicks en el mapa para definir la zona.');
            return;
        }

        const index = this.dibujoIdx;
        const color = this.dibujoColor;

        // Polígono final, editable y arrastrable para poder ajustarlo después.
        const poligono = new google.maps.Polygon({
            map: this.map,
            paths: this.dibujoVertices,
            clickable: true,
            editable: true,
            draggable: true,
            fillColor: color,
            strokeColor: color,
            fillOpacity: 0.35
        });

        this.poligonos.push(poligono);

        // Mismo formato que usaba DrawingManager: "(lat,lng),(lat,lng),..."
        const path = poligono.getPath().getArray();
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
                        <button type="button" class="zona-eliminar text-red-500 hover:text-red-700">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        zonasContainer.insertAdjacentHTML('beforeend', html);

        // Cableamos borrar + edición en la fila recién insertada.
        const nuevaFila = zonasContainer.lastElementChild;
        this.vincularZona(nuevaFila, poligono);

        this.limpiarDibujo();
    },

    limpiarDibujo() {
        if (this.dibujoClickListener) { google.maps.event.removeListener(this.dibujoClickListener); this.dibujoClickListener = null; }
        if (this.dibujoDblClickListener) { google.maps.event.removeListener(this.dibujoDblClickListener); this.dibujoDblClickListener = null; }
        if (this.dibujoClickTimer) { clearTimeout(this.dibujoClickTimer); this.dibujoClickTimer = null; }
        if (this.dibujoPolyline) { this.dibujoPolyline.setMap(null); this.dibujoPolyline = null; }
        this.dibujoMarkers.forEach(m => m.setMap(null));
        this.dibujoMarkers = [];
        this.dibujoVertices = [];
        this.dibujando = false;
        this.dibujoIdx = null;
        this.dibujoColor = null;
        if (this.map) {
            this.map.setOptions({
                disableDoubleClickZoom: false,
                draggableCursor: null,
                clickableIcons: true
            });
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
