<div class="space-y-6">
    <!-- Mensajes de éxito/error -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Card de configuración -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-orange-400 to-orange-500 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-white">
                <i class="fas fa-motorcycle mr-2"></i>Configuración de Delivery
            </h2>
            <p class="text-sm text-white mt-1">Configure las zonas de entrega y opciones de delivery</p>
        </div>

        <div class="p-6">
            <form id="deliveryForm">
                <!-- Ubicación Geográfica -->
                <div class="mb-6">
                    <h3 class="text-md font-semibold text-gray-800 mb-4">
                        <i class="fas fa-map-marker-alt mr-2 text-orange-500"></i>Ubicación del Local
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Mapa -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Ubicación geográfica</b>
                            </label>
                            <p class="text-xs text-gray-500 mb-2">Arrastrá el marcador hasta tu dirección</p>
                            <div id="mapCanvas" class="w-full h-80 border-2 border-orange-300 rounded-lg"></div>
                        </div>

                        <!-- Búsqueda y coordenadas -->
                        <div class="space-y-4">
                            <div>
                                <label for="search_input" class="block text-sm font-medium text-gray-700 mb-2">
                                    Ubicá tu local:
                                </label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                                       id="search_input"
                                       placeholder="Ingrese dirección">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Coordenadas Google Maps:
                                </label>
                                <input wire:model="ubicacion"
                                       type="text"
                                       id="puntomapa"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50"
                                       readonly>
                            </div>

                            <div>
                                <label for="kmentrega" class="block text-sm font-medium text-gray-700 mb-2">
                                    Radio de zona de entrega (km):
                                </label>
                                <input wire:model="kmentrega"
                                       type="number"
                                       step="0.01"
                                       id="kmentrega"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                                       onchange="cambiarRadio()">
                            </div>

                            <div class="flex items-center">
                                <input wire:model="porzona"
                                       type="checkbox"
                                       id="conzonas"
                                       class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500"
                                       onclick="conZonas()">
                                <label for="conzonas" class="ml-2 text-sm font-medium text-gray-700">
                                    Definir por zonas
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zonas de entrega -->
                <div id="detallezonas" class="mb-6" style="display: {{ $porzona ? 'block' : 'none' }};">
                    <h3 class="text-md font-semibold text-gray-800 mb-4">
                        <i class="fas fa-draw-polygon mr-2 text-orange-500"></i>Zonas de Entrega
                    </h3>

                    <button type="button"
                            id="botonzona"
                            onclick="agregapoligono()"
                            class="w-full sm:w-auto px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors mb-4">
                        <i class="fas fa-plus mr-2"></i>Definir nueva zona
                    </button>

                    <div id="zonasContainer" class="space-y-3">
                        @foreach($zonas as $index => $zona)
                            <div id="poligono{{ $index }}" class="bg-gray-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <input type="hidden" id="id{{ $index }}" name="id{{ $index }}" value="{{ $zona->id }}">
                                    <input type="hidden" id="zona{{ $index }}" name="zona{{ $index }}" value="{{ $zona->poligono }}">

                                    <div class="sm:col-span-4">
                                        <input name="nombrezona{{ $index }}"
                                               type="text"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                               value="{{ $zona->nombre }}"
                                               placeholder="Nombre zona"
                                               style="color: {{ EligeColor($index) }}">
                                    </div>

                                    <div class="sm:col-span-3">
                                        <input name="preciozona{{ $index }}"
                                               type="number"
                                               step="0.01"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                               value="{{ $zona->precio }}"
                                               placeholder="Precio">
                                    </div>

                                    <div class="sm:col-span-3 flex items-center">
                                        <input type="checkbox"
                                               name="habilitada{{ $index }}"
                                               id="habilitada{{ $index }}"
                                               {{ $zona->habilitada ? 'checked' : '' }}
                                               class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500">
                                        <label for="habilitada{{ $index }}" class="ml-2 text-sm text-gray-700">Habilitada</label>
                                    </div>

                                    <div class="sm:col-span-2 flex items-center justify-end">
                                        <button type="button"
                                                onclick="borrazona({{ $index }})"
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
                            onclick="guardarConfiguracion()"
                            class="flex-1 px-6 py-3 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i>Guardar Configuración
                    </button>
                    <button type="button"
                            wire:click="$refresh"
                            class="flex-1 px-6 py-3 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors font-medium">
                        <i class="fas fa-undo mr-2"></i>Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
(function() {
    'use strict';

    // Variables del mapa
    var delivery, map, marker, geocoder;
    var poligonos = [];
    var newShape = [];
    @php
        $coor = explode(",", $ubicacion ?: '-34.60024897372449, -58.38179087851561');
    @endphp
    var latitud = {{ $coor[0] }};
    var longitud = {{ $coor[1] }};
    var maxpoligonos = {{ count($zonas) - 1 }};

    window.EligeColor = function(i) {
        var colores = ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'];
        if (i > 11) { i = i % 12; }
        return colores[i];
    }

    function updateMarkerPosition(latLng) {
        var puntomapaEl = document.getElementById('puntomapa');
        if (puntomapaEl) {
            puntomapaEl.value = latLng.lat() + ', ' + latLng.lng();
            if (typeof Livewire !== 'undefined') {
                Livewire.find(puntomapaEl.closest('[wire\\:id]').getAttribute('wire:id')).set('ubicacion', latLng.lat() + ', ' + latLng.lng());
            }
        }
        if (delivery) {
            delivery.setCenter(latLng);
        }
    }

    window.cambiarRadio = function() {
        var radioEl = document.getElementById('kmentrega');
        if (radioEl && delivery) {
            var radio = radioEl.value;
            delivery.setRadius(parseInt(radio * 1000));
        }
    }

    window.conZonas = function() {
        var conzonasEl = document.getElementById('conzonas');
        var detallezonasEl = document.getElementById("detallezonas");
        if (conzonasEl && detallezonasEl) {
            var checked = conzonasEl.checked;
            detallezonasEl.style.display = checked ? "block" : "none";
        }
    }

    window.agregapoligono = function() {
        var botonzonaEl = document.getElementById("botonzona");
        if (!botonzonaEl || !map) return;

        botonzonaEl.style.display = "none";
        maxpoligonos = maxpoligonos + 1;
        var color = window.EligeColor(maxpoligonos);

        poligonos[maxpoligonos] = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON]
            },
            polygonOptions: {
                editable: false,
                fillColor: color,
                strokeColor: color,
                fillOpacity: 0.35
            }
        });

        var detalle = `
            <div id="poligono${maxpoligonos}" class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <input type="hidden" id="id${maxpoligonos}" name="id${maxpoligonos}" value="0">
                    <input type="hidden" id="zona${maxpoligonos}" name="zona${maxpoligonos}" value="">

                    <div class="sm:col-span-4">
                        <input name="nombrezona${maxpoligonos}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="Nombre zona" style="color: ${color}">
                    </div>

                    <div class="sm:col-span-3">
                        <input name="preciozona${maxpoligonos}" type="number" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Precio">
                    </div>

                    <div class="sm:col-span-3 flex items-center">
                        <input type="checkbox" name="habilitada${maxpoligonos}" id="habilitada${maxpoligonos}" checked
                               class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500">
                        <label for="habilitada${maxpoligonos}" class="ml-2 text-sm text-gray-700">Habilitada</label>
                    </div>

                    <div class="sm:col-span-2 flex items-center justify-end">
                        <button type="button" onclick="borrazona(${maxpoligonos})" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        var zonasContainerEl = document.getElementById("zonasContainer");
        if (zonasContainerEl) {
            zonasContainerEl.insertAdjacentHTML('beforeend', detalle);
        }

        var currentMax = maxpoligonos;
        google.maps.event.addListener(poligonos[currentMax], "overlaycomplete", function(event) {
            newShape[currentMax] = event.overlay;
            newShape[currentMax].type = event.type;
            var zonaEl = document.getElementById('zona' + currentMax);
            if (zonaEl) {
                zonaEl.value = event.overlay.getPath().getArray();
            }
            poligonos[currentMax].setMap(null);
            var botonEl = document.getElementById("botonzona");
            if (botonEl) {
                botonEl.style.display = "block";
            }
        });

        poligonos[currentMax].setMap(map);
    }

    window.borrazona = function(n) {
        var poligonoEl = document.getElementById("poligono" + n);
        if (poligonoEl) {
            poligonoEl.remove();
        }
        if (newShape[n]) {
            newShape[n].setMap(null);
        }
    }

    window.guardarConfiguracion = function() {
        var zonas = [];
        var zonasContainer = document.getElementById('zonasContainer');
        if (!zonasContainer) return;

        var zonasDivs = zonasContainer.querySelectorAll('[id^="poligono"]');

        zonasDivs.forEach(function(zonaDiv) {
            var index = zonaDiv.id.replace('poligono', '');
            var idEl = document.getElementById('id' + index);
            var nombreEl = document.querySelector('[name="nombrezona' + index + '"]');
            var precioEl = document.querySelector('[name="preciozona' + index + '"]');
            var poligonoEl = document.getElementById('zona' + index);
            var habilitadaEl = document.querySelector('[name="habilitada' + index + '"]');

            var id = idEl ? idEl.value : 0;
            var nombre = nombreEl ? nombreEl.value : '';
            var precio = precioEl ? precioEl.value : 0;
            var poligono = poligonoEl ? poligonoEl.value : '';
            var habilitada = habilitadaEl ? habilitadaEl.checked : false;

            if (nombre && poligono) {
                zonas.push({
                    id: id,
                    nombre: nombre,
                    precio: precio,
                    poligono: poligono,
                    habilitada: habilitada
                });
            }
        });

        var component = document.querySelector('[wire\\:id]');
        if (component && typeof Livewire !== 'undefined') {
            Livewire.find(component.getAttribute('wire:id')).call('guardar', zonas);
        }
    }

    function initializeMap() {
        var mapCanvas = document.getElementById('mapCanvas');
        var kmentregaEl = document.getElementById('kmentrega');

        if (!mapCanvas || !kmentregaEl) {
            console.error('Map canvas or kmentrega element not found');
            return;
        }

        var latLng = new google.maps.LatLng(latitud, longitud);
        map = new google.maps.Map(mapCanvas, {
            zoom: 14,
            center: latLng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        delivery = new google.maps.Circle({
            strokeColor: "#FF0000",
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: "#FF0000",
            fillOpacity: 0.35,
            @if(!$porzona)
            map: map,
            @endif
            center: latLng,
            radius: parseInt(kmentregaEl.value) * 1000,
        });

        geocoder = new google.maps.Geocoder();
        marker = new google.maps.Marker({
            position: latLng,
            map: map,
            draggable: true
        });

        updateMarkerPosition(latLng);

        @foreach($zonas as $index => $zona)
            @php
                $paths = str_replace('),(',';{lat: ',$zona->poligono);
                $paths = str_replace('(','{lat: ',$paths);
                $paths = str_replace(',',',lng: ',$paths);
                $paths = str_replace(';','},',$paths);
                $paths = str_replace(')','}',$paths);
                $color = EligeColor($index);
            @endphp
            newShape[{{ $index }}] = new google.maps.Polygon({
                paths: [{{ $paths }}],
                strokeColor: '{{ $color }}',
                fillColor: '{{ $color }}',
                fillOpacity: 0.35,
            });
            newShape[{{ $index }}].setMap(map);
        @endforeach

        google.maps.event.addListener(marker, 'drag', function() {
            updateMarkerPosition(marker.getPosition());
        });

        google.maps.event.addListener(marker, 'dragend', function() {
            updateMarkerPosition(marker.getPosition());
        });

        // Inicializar autocomplete
        var searchInput = document.getElementById('search_input');
        if (searchInput && google.maps.places) {
            var autocomplete = new google.maps.places.Autocomplete(searchInput, {
                types: ['geocode']
            });

            google.maps.event.addListener(autocomplete, 'place_changed', function() {
                var place = autocomplete.getPlace();
                if (place.geometry && marker && map) {
                    var newLatLng = new google.maps.LatLng(
                        place.geometry.location.lat(),
                        place.geometry.location.lng()
                    );
                    marker.setPosition(newLatLng);
                    map.panTo(newLatLng);
                    updateMarkerPosition(newLatLng);
                }
            });
        }
    }

    // Cargar Google Maps API si no está cargado
    function loadGoogleMapsAPI(callback) {
        if (typeof google !== 'undefined' && google.maps) {
            callback();
            return;
        }

        if (window.googleMapsLoading) {
            var checkGoogleMapsLoaded = setInterval(function() {
                if (typeof google !== 'undefined' && google.maps) {
                    clearInterval(checkGoogleMapsLoaded);
                    callback();
                }
            }, 100);
            return;
        }

        window.googleMapsLoading = true;
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBjSiomNNl6sqzJp1a4svteLXY1MTi--Tw&libraries=drawing,places';
        script.async = true;
        script.defer = true;
        script.onload = function() {
            window.googleMapsLoading = false;
            callback();
        };
        script.onerror = function() {
            window.googleMapsLoading = false;
            console.error('Error al cargar Google Maps. Puede estar bloqueado por una extensión del navegador.');
            var mapCanvas = document.getElementById('mapCanvas');
            if (mapCanvas) {
                mapCanvas.innerHTML = '<div class="flex items-center justify-center h-full bg-gray-100 text-gray-600 p-4 text-center">' +
                    '<div>' +
                    '<i class="fas fa-exclamation-triangle text-4xl text-orange-500 mb-2"></i>' +
                    '<p class="font-semibold">No se pudo cargar Google Maps</p>' +
                    '<p class="text-sm mt-2">Por favor, desactive bloqueadores de anuncios o extensiones que puedan estar bloqueando Google Maps.</p>' +
                    '</div>' +
                    '</div>';
            }
        };
        document.head.appendChild(script);
    }

    // Inicializar cuando el DOM esté listo y Google Maps cargado
    function initDeliveryMap() {
        // Timeout de 10 segundos para mostrar error si no carga
        var timeout = setTimeout(function() {
            if (typeof google === 'undefined' || !google.maps) {
                console.error('Timeout al cargar Google Maps');
                var mapCanvas = document.getElementById('mapCanvas');
                if (mapCanvas && !mapCanvas.querySelector('.bg-gray-100')) {
                    mapCanvas.innerHTML = '<div class="flex items-center justify-center h-full bg-gray-100 text-gray-600 p-4 text-center">' +
                        '<div>' +
                        '<i class="fas fa-exclamation-triangle text-4xl text-orange-500 mb-2"></i>' +
                        '<p class="font-semibold">Tiempo de espera agotado al cargar Google Maps</p>' +
                        '<p class="text-sm mt-2">Verifique su conexión a internet o desactive bloqueadores de anuncios.</p>' +
                        '</div>' +
                        '</div>';
                }
            }
        }, 10000);

        loadGoogleMapsAPI(function() {
            clearTimeout(timeout);
            if (document.getElementById('mapCanvas')) {
                try {
                    initializeMap();
                } catch (error) {
                    console.error('Error al inicializar el mapa:', error);
                    var mapCanvas = document.getElementById('mapCanvas');
                    if (mapCanvas) {
                        mapCanvas.innerHTML = '<div class="flex items-center justify-center h-full bg-gray-100 text-gray-600 p-4 text-center">' +
                            '<div>' +
                            '<i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-2"></i>' +
                            '<p class="font-semibold">Error al inicializar el mapa</p>' +
                            '<p class="text-sm mt-2">' + error.message + '</p>' +
                            '</div>' +
                            '</div>';
                    }
                }
            }
        });
    }

    // Ejecutar cuando Livewire navegue a esta página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDeliveryMap);
    } else {
        initDeliveryMap();
    }

})();
</script>
</div>

@php
function EligeColor($i) {
    $colores = ['#8C52FF','#5271FF','#FFBD59','#FF5757','#00C2CB','#7ED957','#FF66C4','#5CE1E6','#C9E265','#CB6CE6','#38B6FF','#FFDE59'];
    if ($i > 11) { $i = $i % 12; }
    return $colores[$i];
}
@endphp
