<div>
    @if($pedidoConfirmado)
        {{-- Pedido confirmado --}}
        <div class="max-w-lg mx-auto text-center py-8">
            <div class="t-card p-8">
                <div class="t-success-ico mb-5"><i class="fas fa-check"></i></div>
                <p class="text-xs uppercase tracking-wider t-muted">Pedido #{{ $numeroPedido }}</p>
                <h2 class="t-h text-2xl font-extrabold t-text mt-1">¡Gracias por tu compra!</h2>
                <p class="t-soft mt-2">Tu pedido quedó registrado. Te vamos avisando por mail a medida que avanza.</p>

                @if(!empty($pagoInfo))
                    <div class="mt-6 text-left">
                        @if($pagoInfo['medio'] === 'transferencia')
                            @include('tienda.partials.datos-transferencia', ['transferencia' => $pagoInfo['transferencia'], 'importe' => $pagoInfo['importe_unico'], 'colores' => $colores])
                        @elseif($pagoInfo['medio'] === 'mercadopago')
                            @if($pagoInfo['mp_url'])
                                <p class="text-sm t-soft text-center">Te estamos llevando a Mercado Pago para completar el pago…
                                    <a href="{{ $pagoInfo['mp_url'] }}" class="t-link">Si no pasa nada, hacé clic acá.</a></p>
                            @else
                                <p class="text-sm rounded-xl p-3" style="background-color: var(--twarn-soft); color: var(--twarn);">No pudimos iniciar el pago con Mercado Pago. Tu pedido quedó registrado: podés pagarlo desde <strong>Mis pedidos</strong>.</p>
                            @endif
                        @elseif($pagoInfo['medio'] === 'debin')
                            <p class="text-sm t-soft text-center"><i class="fas fa-university mr-1"></i> Te va a llegar un pedido de débito (DEBIN) de {{ $nombreTienda }} para aprobar en tu home banking. Cuando lo apruebes, el comercio prepara tu pedido.</p>
                        @elseif($pagoInfo['medio'] === 'cuenta_corriente')
                            <p class="text-sm t-soft text-center"><i class="fas fa-book mr-1"></i> El importe de ${{ number_format($pagoInfo['total'], 2, ',', '.') }} se suma a tu cuenta corriente con el comercio.</p>
                        @else
                            <p class="text-sm t-soft text-center"><i class="fas fa-handshake mr-1"></i> El pago lo coordinás con el comercio al recibir el pedido.</p>
                        @endif
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-3 justify-center mt-8">
                    <button wire:click="irAMisPedidos" class="t-btn-dark"><i class="fas fa-clipboard-list"></i> Seguir mi pedido</button>
                    <button wire:click="volverAlCatalogo" class="t-btn"><i class="fas fa-arrow-left"></i> Volver a la tienda</button>
                </div>
            </div>
        </div>
    @elseif(count($carrito) === 0)
        {{-- Carrito vacio --}}
        <div class="max-w-lg mx-auto py-8">
            <div class="t-card t-empty">
                <div class="t-empty-ico"><i class="fas fa-shopping-bag"></i></div>
                <h2 class="t-h text-xl font-bold t-text">Tu carrito está vacío</h2>
                <p class="t-soft mt-1">Todo lo que agregues desde el catálogo aparece acá.</p>
                <button wire:click="volverAlCatalogo" class="t-btn mt-6"><i class="fas fa-th-large"></i> Ir al catálogo</button>
            </div>
        </div>
    @else
        @php $pasos = $this->pasos(); $clavesPasos = array_keys($pasos); $idxPaso = array_search($paso, $clavesPasos, true); $eleccion = $this->resumenEleccion(); @endphp
        <div class="grid lg:grid-cols-5 gap-6" x-data="{ confirmVaciar: false }">

            {{-- ======================= Productos ======================= --}}
            <div class="lg:col-span-2 lg:order-2 space-y-3">
                @if($linkVendedor)
                    <div class="px-4 py-3 rounded-xl text-sm" style="background-color: var(--tp-soft); color: var(--tp-dark);">
                        <p><i class="fas fa-user-tie mr-1"></i> Este pedido lo armó <strong>{{ $linkVendedor['vendedor'] ?: 'tu vendedor' }}</strong>. Revisalo, cambiá lo que quieras y confirmalo.</p>
                        @if(!empty($linkVendedor['observa']))<p class="text-xs mt-1 opacity-80"><i class="fas fa-comment mr-1"></i>{{ $linkVendedor['observa'] }}</p>@endif
                        @if(!empty($linkVendedor['salteados']))<p class="text-xs mt-1" style="color: var(--twarn);"><i class="fas fa-exclamation-triangle mr-1"></i>No se pudieron cargar (ya no se venden): {{ implode(', ', $linkVendedor['salteados']) }}</p>@endif
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <h2 class="t-h text-lg font-bold t-text"><i class="fas fa-shopping-bag mr-1.5" style="color: var(--tp);"></i> Tu carrito <span class="t-muted font-normal text-sm">({{ count($carrito) }})</span></h2>
                    <button @click="confirmVaciar = true" class="text-xs t-muted hover:underline"><i class="fas fa-trash mr-1"></i> Vaciar</button>
                </div>

                {{-- Confirmacion vaciar --}}
                <div x-show="confirmVaciar" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
                    <div class="absolute inset-0 bg-black/50" @click="confirmVaciar = false"></div>
                    <div class="relative t-card shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #fee2e2; color: var(--tdanger);"><i class="fas fa-trash text-lg"></i></div>
                            <div>
                                <h3 class="t-h font-bold t-text">¿Vaciar el carrito?</h3>
                                <p class="text-sm t-soft mt-1">Se quitan todos los productos. Después podés volver a agregarlos.</p>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-5">
                            <button @click="confirmVaciar = false" type="button" class="t-btn-outline flex-1">Cancelar</button>
                            <button @click="confirmVaciar = false; $wire.vaciarCarrito()" type="button" class="flex-1 py-2.5 rounded-xl text-white font-semibold text-sm" style="background-color: var(--tdanger);">Vaciar</button>
                        </div>
                    </div>
                </div>

                <div class="t-card divide-y" style="border-color: var(--tborde);">
                    @foreach($carrito as $index => $item)
                        <div class="p-3 sm:p-4 flex gap-3" style="border-color: var(--tborde);" wire:key="item-{{ $item['codigo'] }}">
                            <div class="w-16 h-16 t-img rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden border" style="border-color: var(--tborde);">
                                @if($item['foto_url'] ?: session('tienda_foto_default'))
                                    <img src="{{ $item['foto_url'] ?: session('tienda_foto_default') }}" alt="" class="w-full h-full object-contain p-1">
                                @else
                                    <i class="fas fa-image t-muted"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2">
                                    <p class="text-sm font-semibold t-text leading-snug line-clamp-2 flex-1">{{ \App\Services\TiendaEstiloService::nombreBonito($item['nombre'], $item['codigo']) }}</p>
                                    <button wire:click="eliminarItem({{ $index }})" class="t-icon-btn !w-7 !h-7 -mt-1 -mr-1 flex-shrink-0" title="Quitar"><i class="fas fa-times text-xs"></i></button>
                                </div>
                                @php $ivaItem = \App\Services\TiendaPrecioService::desglosar((float) $item['precio'], $item['tasa'] ?? null); @endphp
                                <p class="text-xs t-muted">${{ number_format($item['precio'], 2, ',', '.') }} c/u{{ $ivaItem['iva'] > 0 ? ' · IVA ' . \App\Services\TiendaPrecioService::etiquetaTasa($ivaItem['tasa']) . ' incl.' : '' }}</p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="inline-flex items-center rounded-full border" style="border-color: var(--tborde);">
                                        <button wire:click="decrementarCantidad({{ $index }})" class="w-8 h-8 rounded-full flex items-center justify-center t-soft hover:bg-[var(--tp-tint)]"><i class="fas fa-minus text-[10px]"></i></button>
                                        <span class="w-8 text-center font-semibold text-sm t-text">{{ $item['cantidad'] }}</span>
                                        <button wire:click="incrementarCantidad({{ $index }})" class="w-8 h-8 rounded-full flex items-center justify-center t-soft hover:bg-[var(--tp-tint)]"><i class="fas fa-plus text-[10px]"></i></button>
                                    </div>
                                    <p class="t-precio text-base">${{ number_format($item['subtotal'], 2, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button wire:click="volverAlCatalogo" class="t-link text-sm"><i class="fas fa-arrow-left mr-1"></i> Seguir comprando</button>
            </div>

            {{-- ======================= Checkout ======================= --}}
            <div class="lg:col-span-3 lg:order-1" id="checkout">
                <div class="t-card p-5 sm:p-7 lg:sticky lg:top-20">

                    {{-- Progreso --}}
                    <div class="flex items-center gap-1.5 mb-5 overflow-x-auto pb-1">
                        @foreach($pasos as $k => $titulo)
                            @php $i = $loop->index; $hecho = $idxPaso !== false && $i < $idxPaso; $actual = $k === $paso; @endphp
                            <button type="button" wire:click="irAPaso('{{ $k }}')" class="t-step flex-shrink-0 {{ $actual ? 'is-on' : ($hecho ? 'is-done cursor-pointer' : 'cursor-default') }}">
                                <span class="t-step-n">@if($hecho)<i class="fas fa-check text-[9px]"></i>@else{{ $i + 1 }}@endif</span>
                                <span class="text-xs {{ $actual ? 'font-bold t-text' : 't-muted' }} {{ !$actual ? 'hidden sm:inline' : '' }}">{{ $titulo }}</span>
                            </button>
                            @if(!$loop->last)<span class="t-step-line {{ $hecho ? 'is-done' : '' }}"></span>@endif
                        @endforeach
                    </div>

                    <h3 class="t-h text-xl font-bold t-text mb-4">{{ $paso === 'confirmar' ? 'Confirmá tu pedido' : ($pasos[$paso] ?? 'Tu pedido') }}</h3>

                    @if(!empty($agenda['mensaje_cerrada']))
                        <div class="mb-4 p-3 rounded-xl text-xs" style="background-color: var(--twarn-soft); color: var(--twarn);"><i class="fas fa-door-closed mr-1"></i> {{ $agenda['mensaje_cerrada'] }}</div>
                    @endif

                    {{-- Paso: cuenta --}}
                    @if($paso === 'cuenta')
                        @if($modo === \App\Services\TiendaService::MODO_PUBLICA && !$compradorValidado)
                            <div class="rounded-xl p-4" style="background-color: var(--tp-tint);">
                                <p class="text-sm t-text mb-3"><i class="fas fa-user-circle mr-1" style="color: var(--tp);"></i> Para confirmar el pedido ingresá con tu email y contraseña.</p>
                                <div class="space-y-2.5">
                                    <input type="email" wire:model="emailComprador" placeholder="tu@email.com" autocomplete="email" class="t-input" wire:keydown.enter="ingresarComprador">
                                    <input type="password" wire:model="passwordComprador" placeholder="Contraseña" autocomplete="current-password" class="t-input" wire:keydown.enter="ingresarComprador">
                                    <button wire:click="ingresarComprador" wire:loading.attr="disabled" class="t-btn w-full">
                                        <span wire:loading.remove wire:target="ingresarComprador">Ingresar y continuar</span>
                                        <span wire:loading wire:target="ingresarComprador"><i class="fas fa-spinner fa-spin"></i></span>
                                    </button>
                                </div>
                                @if($compradorError)<p class="text-sm mt-2" style="color: var(--tdanger);">{{ $compradorError }}</p>@endif
                                <div class="mt-3 text-xs t-muted flex flex-wrap gap-x-4 gap-y-1">
                                    <a href="{{ route('tienda.recuperar', $tiendaSlug) }}" class="hover:underline">Olvidé mi contraseña</a>
                                    @if(\App\Services\TiendaService::permiteRegistro($modo))
                                        <a href="{{ route('tienda.registro', $tiendaSlug) }}" class="t-link">¿Primera vez? Creá tu cuenta</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Paso: entrega --}}
                    @if($paso === 'entrega')
                        @php $iconosEntrega = ['retiro' => 'fa-store', 'flota' => 'fa-motorcycle', 'correo' => 'fa-truck', 'correo_sucursal' => 'fa-building', 'a_convenir' => 'fa-handshake']; @endphp
                        <p class="t-label">¿Cómo lo recibís?</p>
                        <div class="grid sm:grid-cols-2 gap-2.5">
                            @foreach($opcionesEntrega as $op)
                                @php $elegible = $op['disponible'] || ($this->necesitaDireccion($op['modo']) && !$this->direccionCompleta($op['modo'])); @endphp
                                <label class="t-option {{ $entregaModo === $op['modo'] ? 'is-on' : '' }} {{ !$elegible ? 'is-off' : '' }}">
                                    <input type="radio" wire:model.live="entregaModo" value="{{ $op['modo'] }}" @disabled(!$elegible) class="sr-only">
                                    <span class="t-option-ico"><i class="fas {{ $iconosEntrega[$op['modo']] ?? 'fa-box' }}"></i></span>
                                    <span class="flex-1 min-w-0">
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-semibold t-text">{{ $op['etiqueta'] }}</span>
                                            @if($op['disponible'])<span class="text-sm font-bold whitespace-nowrap t-precio">{{ $op['importe'] > 0 ? '$' . number_format($op['importe'], 2, ',', '.') : ($op['modo'] === 'a_convenir' ? 'a definir' : 'Gratis') }}</span>@endif
                                        </span>
                                        <span class="block text-xs t-muted leading-snug mt-0.5">{{ $op['detalle'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @if($this->necesitaDireccion($entregaModo))
                            <div class="mt-4 rounded-xl p-4 space-y-2.5" style="background-color: var(--tp-tint);">
                                <p class="t-label !mb-0"><i class="fas fa-map-marker-alt mr-1" style="color: var(--tp);"></i> {{ $this->esSucursalCorreo() ? 'Tu localidad y código postal (para buscar sucursales cerca)' : 'Dirección de entrega' }}</p>
                                @if(count($direccionesGuardadas))
                                    <select wire:change="seleccionarDireccion($event.target.value)" class="t-input !py-2.5 text-sm">
                                        <option value="">Otra dirección…</option>
                                        @foreach($direccionesGuardadas as $d)
                                            <option value="{{ $d['id'] }}" @selected($direccionSeleccionada == $d['id'])>{{ $d['alias'] ? $d['alias'] . ': ' : '' }}{{ $d['calle'] }} {{ $d['numero'] }}, {{ $d['localidad'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <div wire:ignore id="tienda_dir_buscador"></div>
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="text" wire:model.blur="direccion.calle" placeholder="Calle" class="t-input !py-2.5 text-sm col-span-2">
                                    <input type="text" wire:model.blur="direccion.numero" placeholder="Número" class="t-input !py-2.5 text-sm">
                                    <input type="text" wire:model.blur="direccion.piso" placeholder="Piso / depto" class="t-input !py-2.5 text-sm">
                                    <input type="text" wire:model.blur="direccion.localidad" placeholder="Localidad" class="t-input !py-2.5 text-sm col-span-2">
                                    <input type="text" wire:model.blur="direccion.provincia" placeholder="Provincia" class="t-input !py-2.5 text-sm col-span-2">
                                    <input type="text" wire:model.blur="direccion.cp" placeholder="Cód. postal" class="t-input !py-2.5 text-sm">
                                    <input type="text" wire:model.blur="direccion.referencia" placeholder="Referencia (timbre, entre calles…)" class="t-input !py-2.5 text-sm col-span-3">
                                </div>
                                @if(session('tienda_comprador_web_id') && !$direccionSeleccionada && !$this->esSucursalCorreo())
                                    <label class="flex items-center gap-2 text-xs t-soft"><input type="checkbox" wire:model="guardarDireccion" class="w-4 h-4 rounded"> Guardar esta dirección para la próxima</label>
                                @endif
                            </div>
                            @if($this->esSucursalCorreo() && $this->direccionCompleta())
                                <div class="mt-3 rounded-xl p-4 space-y-2" style="background-color: var(--tp-tint);">
                                    <p class="t-label !mb-0"><i class="fas fa-building mr-1" style="color: var(--tp);"></i> ¿En qué sucursal del Correo lo retirás?</p>
                                    @if($sucursalesError)
                                        <p class="text-sm" style="color: var(--tdanger);"><i class="fas fa-exclamation-circle mr-1"></i>{{ $sucursalesError }}</p>
                                    @elseif(count($sucursales))
                                        <select wire:model.live="sucursalCodigo" class="t-input !py-2.5 text-sm">
                                            <option value="">Elegí una sucursal…</option>
                                            @foreach($sucursales as $s)
                                                <option value="{{ $s['code'] }}">{{ $s['cerca'] ? '★ ' : '' }}{{ $s['texto'] }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] t-muted">★ sucursales de tu mismo código postal. Cuando el Correo la reciba te avisamos para que pases a buscarla.</p>
                                    @else
                                        <p class="text-sm t-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Buscando sucursales…</p>
                                    @endif
                                </div>
                            @endif
                        @endif
                        @if($entregaError)<p class="text-sm mt-3" style="color: var(--tdanger);"><i class="fas fa-exclamation-circle mr-1"></i>{{ $entregaError }}</p>@endif
                    @endif

                    {{-- Paso: cuando --}}
                    @if($paso === 'cuando')
                        @if(!empty($agenda['activa']))
                            <p class="t-label">¿Cuándo lo querés?</p>
                            @if($agenda['anticipacion_horas'] > 0)
                                <p class="text-xs t-muted mb-2">Este pedido necesita {{ $agenda['anticipacion_horas'] >= 48 ? round($agenda['anticipacion_horas'] / 24) . ' días' : $agenda['anticipacion_horas'] . ' horas' }} de anticipación.</p>
                            @endif
                            @if(empty($agenda['dias']))
                                <p class="text-sm" style="color: var(--tdanger);">Por ahora no hay días disponibles para este pedido. Consultá al comercio.</p>
                            @else
                                @if($agenda['lo_antes_posible'])
                                    <button type="button" wire:click="elegirLoAntesPosible" class="t-option w-full mb-3 {{ $loAntesPosible ? 'is-on' : '' }}">
                                        <span class="t-option-ico"><i class="fas fa-bolt"></i></span>
                                        <span class="text-sm font-semibold t-text self-center">{{ $agenda['lo_antes_posible']['etiqueta'] }}</span>
                                    </button>
                                @endif
                                <div class="t-strip">
                                    @foreach($agenda['dias'] as $dia)
                                        <button type="button" wire:click="elegirDia('{{ $dia['fecha'] }}')" class="t-pill flex-shrink-0 {{ !$loAntesPosible && $entregaFecha === $dia['fecha'] ? 'is-on' : '' }}">{{ $dia['etiqueta'] }}</button>
                                    @endforeach
                                </div>
                                @php $diaElegido = !$loAntesPosible ? collect($agenda['dias'])->firstWhere('fecha', $entregaFecha) : null; @endphp
                                @if($diaElegido)
                                    <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        @foreach($diaElegido['franjas'] as $fr)
                                            <button type="button" wire:click="elegirFranja({{ $fr['id'] }})" @disabled(!$fr['disponible']) title="{{ $fr['motivo'] ?? '' }}"
                                                    class="t-pill justify-center {{ (int) $entregaFranjaId === (int) $fr['id'] && $fr['disponible'] ? 'is-on' : '' }} {{ !$fr['disponible'] ? 'opacity-40 line-through cursor-not-allowed' : '' }}">{{ $fr['etiqueta'] }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        @else
                            <label class="t-label">Fecha de entrega</label>
                            <input type="date" wire:model="parafecha" min="{{ date('Y-m-d') }}" class="t-input">
                        @endif
                        @if($agendaError)<p class="text-sm mt-3" style="color: var(--tdanger);"><i class="fas fa-exclamation-circle mr-1"></i>{{ $agendaError }}</p>@endif
                    @endif

                    {{-- Paso: pago --}}
                    @if($paso === 'pago')
                        @php $iconosPago = ['mercadopago' => 'fa-credit-card', 'transferencia' => 'fa-university', 'debin' => 'fa-bolt', 'cuenta_corriente' => 'fa-book', 'a_convenir' => 'fa-handshake']; @endphp
                        <p class="t-label">¿Cómo pagás?</p>
                        <div class="grid sm:grid-cols-2 gap-2.5">
                            @foreach($opcionesPago as $op)
                                <label class="t-option {{ $pagoMedio === $op['medio'] ? 'is-on' : '' }} {{ !$op['disponible'] ? 'is-off' : '' }}">
                                    <input type="radio" wire:model.live="pagoMedio" value="{{ $op['medio'] }}" @disabled(!$op['disponible']) class="sr-only">
                                    <span class="t-option-ico"><i class="fas {{ $iconosPago[$op['medio']] ?? 'fa-wallet' }}"></i></span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-sm font-semibold t-text">{{ $op['etiqueta'] }}</span>
                                        <span class="block text-xs t-muted leading-snug mt-0.5">{{ $op['detalle'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if($pagoMedio === 'debin')
                            <div class="mt-4 rounded-xl p-4 space-y-2.5" style="background-color: var(--tp-tint);">
                                <p class="t-label !mb-0"><i class="fas fa-university mr-1" style="color: var(--tp);"></i> Datos para el débito</p>
                                <div class="grid sm:grid-cols-2 gap-2">
                                    <input type="text" wire:model.blur="debinCuit" placeholder="CUIT / CUIL (11 números)" inputmode="numeric" class="t-input !py-2.5 text-sm">
                                    <input type="text" wire:model.blur="debinCbu" placeholder="CBU (22 números) o alias" class="t-input !py-2.5 text-sm">
                                </div>
                                <p class="text-[11px] t-muted">El comercio genera el pedido de débito desde su banco. Te va a llegar para aprobar en tu home banking; el pedido se prepara cuando se acredita.</p>
                            </div>
                        @endif
                        @if($pagoError)<p class="text-sm mt-3" style="color: var(--tdanger);"><i class="fas fa-exclamation-circle mr-1"></i>{{ $pagoError }}</p>@endif
                    @endif

                    {{-- Paso: confirmar --}}
                    @if($paso === 'confirmar')
                        <div class="rounded-xl border divide-y text-sm mb-4" style="border-color: var(--tborde);">
                            @if($compradorValidado)
                                <div class="px-3 py-2.5 flex items-center gap-3" style="border-color: var(--tborde);"><span class="w-7 h-7 rounded-full flex items-center justify-center text-xs" style="background-color: var(--tp-soft); color: var(--tp-dark);"><i class="fas fa-user"></i></span><span class="flex-1 t-text">{{ $compradorNombre }}</span></div>
                            @endif
                            @foreach(['entrega' => ['fa-truck', 'Entrega'], 'cuando' => ['fa-clock', 'Cuándo'], 'pago' => ['fa-wallet', 'Pago']] as $k => [$icono, $titulo])
                                @if($eleccion[$k] !== '')
                                    <div class="px-3 py-2.5 flex items-center gap-3" style="border-color: var(--tborde);">
                                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs flex-shrink-0" style="background-color: var(--tp-soft); color: var(--tp-dark);"><i class="fas {{ $icono }}"></i></span>
                                        <span class="flex-1 min-w-0 t-text"><span class="text-[10px] uppercase t-muted block leading-none mb-0.5">{{ $titulo }}</span>{{ $eleccion[$k] }}</span>
                                        @if(isset($pasos[$k]))<button type="button" wire:click="irAPaso('{{ $k }}')" class="t-link text-xs">Cambiar</button>@endif
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <label class="t-label">Observaciones <span class="t-muted font-normal">(opcional)</span></label>
                        <textarea wire:model="observaciones" rows="2" placeholder="Algo que el comercio tenga que saber sobre tu pedido…" class="t-input resize-none"></textarea>

                        <div class="mt-5 space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="t-soft">Productos ({{ count($carrito) }})</span><span class="font-medium t-text">${{ number_format($total, 2, ',', '.') }}</span></div>
                            @foreach($promoLineas as $pl)
                                <div class="flex justify-between" style="color: var(--tok);"><span><i class="fas fa-tag mr-1 text-xs"></i>{{ $pl['nombre'] }}</span><span class="font-medium">-${{ number_format(abs($pl['importe']), 2, ',', '.') }}</span></div>
                            @endforeach
                            @if($promoExcluido)<p class="text-[11px] t-muted">Tu cuenta no participa de las promociones.</p>@endif
                            @if(count($promoLineas))<p class="text-[10px] t-muted">Las promociones se confirman al facturar según su vigencia.</p>@endif
                            @if($entregaModo)
                                <div class="flex justify-between"><span class="t-soft">Envío</span><span class="font-medium t-text">{{ $envioImporte > 0 ? '$' . number_format($envioImporte, 2, ',', '.') : ($entregaModo === 'a_convenir' ? 'a convenir' : 'Gratis') }}</span></div>
                            @endif
                            @if($resumenIva['iva'] > 0)
                                <div class="text-xs t-muted pt-1.5 border-t border-dashed" style="border-color: var(--tborde);">
                                    <p class="mb-0.5">Los precios incluyen IVA:</p>
                                    @foreach($resumenIva['por_tasa'] as $t)
                                        <div class="flex justify-between"><span>IVA {{ \App\Services\TiendaPrecioService::etiquetaTasa($t['tasa']) }} sobre ${{ number_format($t['neto'], 2, ',', '.') }}</span><span>${{ number_format($t['iva'], 2, ',', '.') }}</span></div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Total, siempre --}}
                    <div class="flex items-end justify-between border-t pt-4 mt-5" style="border-color: var(--tborde);">
                        <span class="t-h font-bold t-text">Total</span>
                        <div class="text-right">
                            <span class="t-precio text-2xl">${{ number_format($totalConEnvio, 2, ',', '.') }}</span>
                            @if($paso !== 'confirmar' && $entregaModo && $envioImporte > 0)<p class="text-[11px] t-muted">incluye envío ${{ number_format($envioImporte, 2, ',', '.') }}</p>@endif
                        </div>
                    </div>

                    {{-- Navegacion --}}
                    @if($paso === 'confirmar')
                        <button wire:click="confirmarPedido" wire:loading.attr="disabled" @if(!$compradorValidado) disabled @endif class="t-btn t-btn-lg w-full mt-4 shadow-lg">
                            <span wire:loading.remove wire:target="confirmarPedido"><i class="fas fa-check"></i> Confirmar pedido</span>
                            <span wire:loading wire:target="confirmarPedido"><i class="fas fa-spinner fa-spin"></i> Procesando...</span>
                        </button>
                        @if(session()->has('error'))<p class="text-sm mt-2 text-center" style="color: var(--tdanger);">{{ session('error') }}</p>@endif
                    @elseif($paso !== 'cuenta')
                        <div class="flex gap-2 mt-4">
                            @if($idxPaso > 0)<button type="button" wire:click="pasoAnterior" class="t-btn-outline !px-4"><i class="fas fa-arrow-left"></i></button>@endif
                            <button type="button" wire:click="siguientePaso" wire:loading.attr="disabled" class="t-btn t-btn-lg flex-1 shadow-lg">
                                <span wire:loading.remove wire:target="siguientePaso">Continuar <i class="fas fa-arrow-right"></i></span>
                                <span wire:loading wire:target="siguientePaso"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Celular: barra fija con el total --}}
            <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t px-4 py-3 safe-bottom flex items-center justify-between gap-3" style="background-color: var(--tcard); border-color: var(--tborde); box-shadow: 0 -4px 12px rgb(0 0 0 / 0.06);">
                <div>
                    <p class="text-[11px] t-muted leading-none">Total</p>
                    <p class="t-precio text-lg leading-tight">${{ number_format($totalConEnvio ?? 0, 2, ',', '.') }}</p>
                </div>
                @if($paso === 'confirmar' || $paso === 'cuenta')
                    <a href="#checkout" class="t-btn">{{ $paso === 'confirmar' ? 'Confirmar pedido' : 'Ingresar' }} <i class="fas fa-arrow-down"></i></a>
                @else
                    <a href="#checkout" wire:click.prevent="siguientePaso" class="t-btn">Continuar <i class="fas fa-arrow-right"></i></a>
                @endif
            </div>
            <div class="lg:hidden h-20"></div>
        </div>
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
