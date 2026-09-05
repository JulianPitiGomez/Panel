<div>
    @if($pedidoConfirmado)
        <!-- Pantalla de éxito -->
        <div class="max-w-lg mx-auto text-center py-12">
            <div class="bg-white rounded-2xl shadow-sm p-8">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-green-500 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold mb-2" style="color: {{ $colores['texto'] }}">Pedido confirmado</h2>
                <p class="text-gray-500 mb-4">Tu pedido #{{ $numeroPedido }} fue registrado correctamente.</p>

                @if(!empty($pagoInfo))
                    @if($pagoInfo['medio'] === 'transferencia')
                        <div class="mb-6">
                            @include('tienda.partials.datos-transferencia', ['transferencia' => $pagoInfo['transferencia'], 'importe' => $pagoInfo['importe_unico'], 'colores' => $colores])
                        </div>
                    @elseif($pagoInfo['medio'] === 'mercadopago')
                        @if($pagoInfo['mp_url'])
                            <p class="text-sm text-gray-500 mb-6">Te estamos llevando a Mercado Pago para completar el pago…
                                <a href="{{ $pagoInfo['mp_url'] }}" class="underline" style="color: {{ $colores['boton'] }}">Si no pasa nada, hacé clic acá.</a></p>
                        @else
                            <p class="text-sm text-amber-700 bg-amber-50 rounded-lg p-3 mb-6">No pudimos iniciar el pago con Mercado Pago. Tu pedido quedó registrado: podés pagarlo desde <strong>Mis pedidos</strong>.</p>
                        @endif
                    @elseif($pagoInfo['medio'] === 'cuenta_corriente')
                        <p class="text-sm text-gray-500 mb-6"><i class="fas fa-book mr-1"></i> El importe de ${{ number_format($pagoInfo['total'], 2, ',', '.') }} se suma a tu cuenta corriente con el comercio.</p>
                    @else
                        <p class="text-sm text-gray-500 mb-6"><i class="fas fa-handshake mr-1"></i> El pago lo coordinás con el comercio al recibir el pedido.</p>
                    @endif
                @else
                    <p class="text-sm text-gray-400 mb-8">Recibiras novedades sobre tu pedido.</p>
                @endif

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button wire:click="irAMisPedidos"
                            class="px-6 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90"
                            style="background-color: {{ $colores['precio'] }}">
                        <i class="fas fa-clipboard-list mr-2"></i> Ver mis pedidos
                    </button>
                    <button wire:click="volverAlCatalogo"
                            class="px-6 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90"
                            style="background-color: {{ $colores['boton'] }}">
                        <i class="fas fa-arrow-left mr-2"></i> Volver a la tienda
                    </button>
                </div>
            </div>
        </div>
    @elseif(count($carrito) === 0)
        <!-- Carrito vacío -->
        <div class="max-w-lg mx-auto text-center py-12">
            <div class="bg-white rounded-2xl shadow-sm p-8">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-xl font-bold mb-2" style="color: {{ $colores['texto'] }}">Tu carrito esta vacio</h2>
                <p class="text-gray-500 mb-6">Agrega productos desde el catalogo.</p>
                <button wire:click="volverAlCatalogo"
                        class="px-6 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90"
                        style="background-color: {{ $colores['boton'] }}">
                    <i class="fas fa-arrow-left mr-2"></i> Ir al catalogo
                </button>
            </div>
        </div>
    @else
        <div class="grid lg:grid-cols-3 gap-6" x-data="{ confirmVaciar: false }">
            <!-- Lista de items (2/3) -->
            <div class="lg:col-span-2 space-y-3">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold" style="color: {{ $colores['texto'] }}">
                        <i class="fas fa-shopping-cart mr-2" style="color: {{ $colores['boton'] }}"></i> Mi carrito
                    </h2>
                    <button @click="confirmVaciar = true"
                            class="text-sm text-red-500 hover:text-red-700">
                        <i class="fas fa-trash mr-1"></i> Vaciar
                    </button>
                </div>

                {{-- Modal confirmación vaciar carrito --}}
                <div x-show="confirmVaciar" x-transition.opacity
                     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                     style="display: none;">
                    <div class="absolute inset-0 bg-black/50" @click="confirmVaciar = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-bold text-gray-800">¿Vaciar carrito?</h3>
                                <p class="text-sm text-gray-500 mt-1">Se eliminarán todos los productos del carrito. Esta acción no se puede deshacer.</p>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-5">
                            <button @click="confirmVaciar = false" type="button"
                                    class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-semibold text-sm">
                                Cancelar
                            </button>
                            <button @click="confirmVaciar = false; $wire.vaciarCarrito()" type="button"
                                    class="flex-1 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-semibold text-sm">
                                <i class="fas fa-trash mr-1"></i> Vaciar
                            </button>
                        </div>
                    </div>
                </div>

                @foreach($carrito as $index => $item)
                    <div class="bg-white rounded-xl shadow-sm p-3 sm:p-4">
                        <!-- Fila superior: imagen + info + eliminar -->
                        <div class="flex gap-3 items-start">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center overflow-hidden">
                                @if($item['foto_url'])
                                    <img src="{{ $item['foto_url'] }}" alt="{{ $item['nombre'] }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fas fa-image text-gray-300"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-medium truncate" style="color: {{ $colores['texto'] }}">{{ $item['nombre'] }}</h3>
                                <p class="text-xs text-gray-400">Cod: {{ $item['codigo'] }}</p>
                                <p class="text-sm font-bold mt-1" style="color: {{ $colores['precio'] }}">
                                    ${{ number_format($item['precio'], 2, ',', '.') }}
                                </p>
                                @php $ivaItem = \App\Services\TiendaPrecioService::desglosar((float) $item['precio'], $item['tasa'] ?? null); @endphp
                                @if($ivaItem['iva'] > 0)
                                    <p class="text-[10px] text-gray-400">Incluye IVA {{ \App\Services\TiendaPrecioService::etiquetaTasa($ivaItem['tasa']) }}: ${{ number_format($ivaItem['iva'], 2, ',', '.') }}</p>
                                @endif
                            </div>
                            <button wire:click="eliminarItem({{ $index }})"
                                    class="text-red-400 hover:text-red-600 transition flex-shrink-0 p-1">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <!-- Fila inferior: cantidad + subtotal -->
                        <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <button wire:click="decrementarCantidad({{ $index }})"
                                        class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="w-8 text-center font-semibold text-sm" style="color: {{ $colores['texto'] }}">{{ $item['cantidad'] }}</span>
                                <button wire:click="incrementarCantidad({{ $index }})"
                                        class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <p class="text-sm font-bold" style="color: {{ $colores['precio'] }}">${{ number_format($item['subtotal'], 2, ',', '.') }}</p>
                        </div>
                    </div>
                @endforeach

                <div class="mt-2">
                    <button wire:click="volverAlCatalogo"
                            class="text-sm hover:underline"
                            style="color: {{ $colores['boton'] }}">
                        <i class="fas fa-arrow-left mr-1"></i> Seguir comprando
                    </button>
                </div>
            </div>

            <!-- Panel de checkout (1/3) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-20">
                    <h3 class="text-lg font-bold mb-4" style="color: {{ $colores['texto'] }}">Resumen del pedido</h3>

                    <!-- Validación de email (solo modo público) -->
                    @if($modo === \App\Services\TiendaService::MODO_PUBLICA && !$compradorValidado)
                        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-700 mb-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                Para confirmar tu pedido, ingresá con tu email y contraseña.
                            </p>
                            <div class="space-y-2">
                                <input type="email" wire:model="emailComprador"
                                       placeholder="tu@email.com" autocomplete="email"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 outline-none"
                                       style="--tw-ring-color: {{ $colores['boton'] }}"
                                       wire:keydown.enter="ingresarComprador">
                                <input type="password" wire:model="passwordComprador"
                                       placeholder="Contraseña" autocomplete="current-password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 outline-none"
                                       style="--tw-ring-color: {{ $colores['boton'] }}"
                                       wire:keydown.enter="ingresarComprador">
                                <button wire:click="ingresarComprador"
                                        wire:loading.attr="disabled"
                                        class="w-full px-4 py-2 text-white text-sm font-semibold rounded-lg transition-opacity hover:opacity-90"
                                        style="background-color: {{ $colores['precio'] }}">
                                    <span wire:loading.remove wire:target="ingresarComprador">Ingresar</span>
                                    <span wire:loading wire:target="ingresarComprador"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                            @if($compradorError)
                                <p class="text-sm text-red-500 mt-2">{{ $compradorError }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- Info del comprador validado -->
                    @if($compradorValidado)
                        <div class="mb-4 p-3 bg-green-50 rounded-lg flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <div>
                                <p class="text-sm font-medium text-green-800">{{ $compradorNombre }}</p>
                                <p class="text-xs text-green-600">Cliente verificado</p>
                            </div>
                        </div>
                    @endif

                    <!-- Entrega: modalidad y dirección -->
                    @if(count($opcionesEntrega))
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">¿Cómo lo recibís?</label>
                        <div class="space-y-2">
                            @foreach($opcionesEntrega as $op)
                                <label class="flex items-start gap-2 p-2.5 rounded-lg border cursor-pointer {{ $entregaModo === $op['modo'] ? 'border-gray-400 bg-gray-50' : 'border-gray-200' }} {{ !$op['disponible'] ? 'opacity-60 cursor-not-allowed' : '' }}">
                                    <input type="radio" wire:model.live="entregaModo" value="{{ $op['modo'] }}" @disabled(!$op['disponible']) class="mt-1">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-medium" style="color: {{ $colores['texto'] }}">{{ $op['etiqueta'] }}</span>
                                            @if($op['disponible'])
                                                <span class="text-sm font-bold whitespace-nowrap" style="color: {{ $colores['precio'] }}">
                                                    {{ $op['importe'] > 0 ? '$' . number_format($op['importe'], 2, ',', '.') : ($op['modo'] === 'a_convenir' ? 'a definir' : 'Gratis') }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-400 leading-snug">{{ $op['detalle'] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @if(in_array($entregaModo, ['flota', 'correo', 'a_convenir']) || (isset($opcionesEntrega['flota']) && !$opcionesEntrega['flota']['disponible']) || (isset($opcionesEntrega['correo']) && !$opcionesEntrega['correo']['disponible']))
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg space-y-2">
                            <p class="text-xs font-medium text-gray-600">Dirección de entrega</p>

                            @if(count($direccionesGuardadas))
                                <select wire:change="seleccionarDireccion($event.target.value)"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                                    <option value="">Otra dirección…</option>
                                    @foreach($direccionesGuardadas as $d)
                                        <option value="{{ $d['id'] }}" @selected($direccionSeleccionada == $d['id'])>
                                            {{ $d['alias'] ? $d['alias'] . ': ' : '' }}{{ $d['calle'] }} {{ $d['numero'] }}, {{ $d['localidad'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <div wire:ignore id="tienda_dir_buscador"></div>

                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" wire:model.blur="direccion.calle" placeholder="Calle" class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <input type="text" wire:model.blur="direccion.numero" placeholder="Número" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <input type="text" wire:model.blur="direccion.piso" placeholder="Piso / depto" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <input type="text" wire:model.blur="direccion.localidad" placeholder="Localidad" class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <input type="text" wire:model.blur="direccion.provincia" placeholder="Provincia" class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <input type="text" wire:model.blur="direccion.cp" placeholder="Cód. postal" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <input type="text" wire:model.blur="direccion.referencia" placeholder="Referencia (timbre, entre calles…)" class="col-span-3 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            @if(session('tienda_comprador_web_id') && !$direccionSeleccionada)
                                <label class="flex items-center gap-2 text-xs text-gray-500">
                                    <input type="checkbox" wire:model="guardarDireccion" class="w-4 h-4 rounded"> Guardar esta dirección para la próxima
                                </label>
                            @endif
                        </div>
                        @endif

                        @if($entregaError)
                            <p class="text-sm text-red-500 mt-2">{{ $entregaError }}</p>
                        @endif
                    </div>
                    @endif

                    <!-- Pago: medio -->
                    @if(count($opcionesPago))
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">¿Cómo pagás?</label>
                        <div class="space-y-2">
                            @foreach($opcionesPago as $op)
                                <label class="flex items-start gap-2 p-2.5 rounded-lg border cursor-pointer {{ $pagoMedio === $op['medio'] ? 'border-gray-400 bg-gray-50' : 'border-gray-200' }} {{ !$op['disponible'] ? 'opacity-60 cursor-not-allowed' : '' }}">
                                    <input type="radio" wire:model.live="pagoMedio" value="{{ $op['medio'] }}" @disabled(!$op['disponible']) class="mt-1">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-medium" style="color: {{ $colores['texto'] }}">{{ $op['etiqueta'] }}</span>
                                        <p class="text-xs text-gray-400 leading-snug">{{ $op['detalle'] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @if($pagoError)
                            <p class="text-sm text-red-500 mt-2">{{ $pagoError }}</p>
                        @endif
                    </div>
                    @endif

                    <!-- Aviso de tienda cerrada -->
                    @if(!empty($agenda['mensaje_cerrada']))
                        <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                            <i class="fas fa-door-closed mr-1"></i> {{ $agenda['mensaje_cerrada'] }}
                        </div>
                    @endif

                    <!-- Cuándo: agenda del comercio o fecha libre -->
                    @if(!empty($agenda['activa']))
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">¿Cuándo lo querés?</label>
                            @if($agenda['anticipacion_horas'] > 0)
                                <p class="text-[11px] text-gray-400 mb-2">Este pedido necesita {{ $agenda['anticipacion_horas'] >= 48 ? round($agenda['anticipacion_horas'] / 24) . ' días' : $agenda['anticipacion_horas'] . ' horas' }} de anticipación.</p>
                            @endif

                            @if(empty($agenda['dias']))
                                <p class="text-sm text-red-500">Por ahora no hay días disponibles para este pedido. Consultá al comercio.</p>
                            @else
                                @if($agenda['lo_antes_posible'])
                                    <button type="button" wire:click="elegirLoAntesPosible"
                                            class="w-full text-left mb-2 px-3 py-2 rounded-lg border text-sm {{ $loAntesPosible ? 'border-gray-400 bg-gray-50 font-medium' : 'border-gray-200' }}" style="color: {{ $colores['texto'] }}">
                                        <i class="fas fa-bolt mr-1" style="color: {{ $colores['boton'] }}"></i> {{ $agenda['lo_antes_posible']['etiqueta'] }}
                                    </button>
                                @endif

                                <div class="flex gap-2 overflow-x-auto pb-1">
                                    @foreach($agenda['dias'] as $dia)
                                        <button type="button" wire:click="elegirDia('{{ $dia['fecha'] }}')"
                                                class="flex-shrink-0 px-3 py-2 rounded-lg border text-xs {{ !$loAntesPosible && $entregaFecha === $dia['fecha'] ? 'text-white border-transparent' : 'border-gray-200 text-gray-700' }}"
                                                style="{{ !$loAntesPosible && $entregaFecha === $dia['fecha'] ? 'background-color: ' . $colores['precio'] : '' }}">
                                            {{ $dia['etiqueta'] }}
                                        </button>
                                    @endforeach
                                </div>

                                @php $diaElegido = !$loAntesPosible ? collect($agenda['dias'])->firstWhere('fecha', $entregaFecha) : null; @endphp
                                @if($diaElegido)
                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        @foreach($diaElegido['franjas'] as $f)
                                            <button type="button" wire:click="elegirFranja({{ $f['id'] }})" @disabled(!$f['disponible'])
                                                    title="{{ $f['motivo'] ?? '' }}"
                                                    class="px-3 py-2 rounded-lg border text-xs {{ (int) $entregaFranjaId === (int) $f['id'] && $f['disponible'] ? 'text-white border-transparent' : 'border-gray-200 text-gray-700' }} {{ !$f['disponible'] ? 'opacity-40 line-through cursor-not-allowed' : '' }}"
                                                    style="{{ (int) $entregaFranjaId === (int) $f['id'] && $f['disponible'] ? 'background-color: ' . $colores['precio'] : '' }}">
                                                {{ $f['etiqueta'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                            @if($agendaError)
                                <p class="text-sm text-red-500 mt-2">{{ $agendaError }}</p>
                            @endif
                        </div>
                    @else
                        <!-- Fecha de entrega libre (sin agenda) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de entrega</label>
                            <input type="date" wire:model="parafecha"
                                   min="{{ date('Y-m-d') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 outline-none"
                                   style="--tw-ring-color: {{ $colores['boton'] }}">
                            @if($agendaError)
                                <p class="text-sm text-red-500 mt-2">{{ $agendaError }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- Observaciones -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea wire:model="observaciones"
                                  rows="2"
                                  placeholder="Notas para tu pedido..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 outline-none resize-none"
                                  style="--tw-ring-color: {{ $colores['boton'] }}"></textarea>
                    </div>

                    <hr class="my-4">

                    <!-- Resumen -->
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Productos ({{ count($carrito) }})</span>
                            <span class="font-medium">${{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        @if($resumenIva['iva'] > 0)
                            <div class="text-xs text-gray-400 pt-1 border-t border-dashed border-gray-200">
                                <p class="mb-0.5">Los precios incluyen IVA:</p>
                                @foreach($resumenIva['por_tasa'] as $t)
                                    <div class="flex justify-between">
                                        <span>IVA {{ \App\Services\TiendaPrecioService::etiquetaTasa($t['tasa']) }} sobre ${{ number_format($t['neto'], 2, ',', '.') }}</span>
                                        <span>${{ number_format($t['iva'], 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($entregaModo)
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Envío</span>
                            <span class="font-medium">{{ $envioImporte > 0 ? '$' . number_format($envioImporte, 2, ',', '.') : ($entregaModo === 'a_convenir' ? 'a convenir' : 'Gratis') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-lg font-bold border-t pt-3">
                        <span style="color: {{ $colores['texto'] }}">Total</span>
                        <span style="color: {{ $colores['precio'] }}">${{ number_format($totalConEnvio, 2, ',', '.') }}</span>
                    </div>

                    <!-- Botón confirmar -->
                    <button wire:click="confirmarPedido"
                            wire:loading.attr="disabled"
                            @if(!$compradorValidado) disabled @endif
                            class="w-full mt-4 py-3 text-white font-semibold rounded-lg transition-opacity hover:opacity-90 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background-color: {{ $colores['boton'] }}">
                        <span wire:loading.remove wire:target="confirmarPedido">
                            <i class="fas fa-check mr-2"></i> Confirmar pedido
                        </span>
                        <span wire:loading wire:target="confirmarPedido">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Procesando...
                        </span>
                    </button>

                    @if(session()->has('error'))
                        <p class="text-sm text-red-500 mt-2 text-center">{{ session('error') }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- WhatsApp floating button --}}
    @php
        $telefonoWa = session('tienda_datos')['telefono'] ?? null;
        // Pedir por WhatsApp con el carrito armado: el mensaje lleva los items y el total
        $textoWa = '';
        if (!$pedidoConfirmado && count($carrito)) {
            $lineas = array_map(fn ($i) => rtrim(rtrim(number_format((float) $i['cantidad'], 2, ',', '.'), '0'), ',') . ' x ' . $i['nombre'] . ' ($' . number_format((float) $i['subtotal'], 2, ',', '.') . ')', $carrito);
            $textoWa = '?text=' . rawurlencode("¡Hola! Quiero hacer este pedido:\n- " . implode("\n- ", $lineas) . "\nTotal: $" . number_format((float) $total, 2, ',', '.') . (session('tienda_comprador_nombre') ? "\nSoy " . session('tienda_comprador_nombre') : ''));
        }
    @endphp
    @if($telefonoWa)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $telefonoWa) }}{{ $textoWa }}"
           title="{{ $textoWa ? 'Pedir por WhatsApp con el carrito armado' : 'Escribinos por WhatsApp' }}"
           target="_blank"
           class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-[#25D366] text-white rounded-full shadow-lg flex items-center justify-center hover:bg-[#1ebe57] transition-colors hover:scale-110 transform duration-200">
            <i class="fab fa-whatsapp text-2xl"></i>
        </a>
    @endif
</div>

@script
<script>
// Autocompletado de direccion de entrega (Google Places). Es opcional: si no carga,
// el comprador escribe la direccion a mano y las zonas se resuelven por codigo postal
// o geocodificando en el servidor.
(() => {
    const KEY = @js($googleMapsApiKey);
    let montado = false;

    const montar = () => {
        const c = document.getElementById('tienda_dir_buscador');
        if (!c || montado || typeof google === 'undefined' || !google.maps?.places?.PlaceAutocompleteElement) return;
        if (c.querySelector('gmp-place-autocomplete')) { montado = true; return; }
        try {
            const ac = new google.maps.places.PlaceAutocompleteElement({ includedRegionCodes: ['ar'] });
            ac.style.width = '100%';
            c.appendChild(ac);
            montado = true;
            ac.addEventListener('gmp-select', async ({ placePrediction }) => {
                const place = placePrediction.toPlace();
                await place.fetchFields({ fields: ['location', 'addressComponents'] });
                const d = { calle: '', numero: '', localidad: '', provincia: '', cp: '', lat: null, lng: null };
                (place.addressComponents || []).forEach(comp => {
                    const t = comp.types || [];
                    if (t.includes('route')) d.calle = comp.longText;
                    else if (t.includes('street_number')) d.numero = comp.longText;
                    else if (t.includes('locality')) d.localidad = comp.longText;
                    else if (t.includes('administrative_area_level_1')) d.provincia = comp.longText;
                    else if (t.includes('postal_code')) d.cp = comp.longText;
                });
                if (place.location) { d.lat = place.location.lat(); d.lng = place.location.lng(); }
                $wire.setDireccionDesdeMapa(d);
            });
        } catch (e) { console.warn('Autocompletado de direccion no disponible', e); }
    };

    const cargar = () => {
        if (!KEY) return;
        if (typeof google !== 'undefined' && google.maps?.places) { montar(); return; }
        if (window.googleMapsLoading) { const t = setInterval(() => { if (typeof google !== 'undefined' && google.maps?.places) { clearInterval(t); montar(); } }, 300); return; }
        window.googleMapsLoading = true;
        const s = document.createElement('script');
        s.src = `https://maps.googleapis.com/maps/api/js?key=${KEY}&libraries=places`;
        s.async = true; s.defer = true;
        s.onload = () => { window.googleMapsLoading = false; montar(); };
        s.onerror = () => { window.googleMapsLoading = false; };
        document.head.appendChild(s);
    };

    cargar();
    // El bloque de direccion aparece/desaparece segun la modalidad: re-montar tras cada actualizacion
    Livewire.hook('morph.updated', () => { montado = !!document.querySelector('#tienda_dir_buscador gmp-place-autocomplete'); montar(); });
})();
</script>
@endscript
