@php
    $money = fn ($n) => '$' . number_format((float) $n, 0, ',', '.');
    $semColors = [
        'verde'    => ['dot' => 'bg-green-500', 'ring' => 'border-green-200', 'soft' => 'bg-green-50', 'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-700'],
        'amarillo' => ['dot' => 'bg-amber-500', 'ring' => 'border-amber-200', 'soft' => 'bg-amber-50', 'text' => 'text-amber-700', 'badge' => 'bg-amber-100 text-amber-700'],
        'rojo'     => ['dot' => 'bg-red-500', 'ring' => 'border-red-200', 'soft' => 'bg-red-50', 'text' => 'text-red-700', 'badge' => 'bg-red-100 text-red-700'],
    ];
    $sc = fn ($s) => $semColors[$s] ?? $semColors['verde'];
    $pctBadge = function ($pct) {
        if ($pct === null) return '<span class="text-gray-400 text-xs">s/base</span>';
        $up = $pct >= 0;
        $cls = $up ? 'text-green-600' : 'text-red-600';
        $ico = $up ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
        $txt = abs($pct) > 999 ? '>999' : number_format(abs($pct), 1);
        return '<span class="' . $cls . ' text-sm font-semibold"><i class="fas ' . $ico . ' mr-1"></i>' . $txt . '%</span>';
    };
@endphp

<div class="space-y-6 font-secondary">

    {{-- ===== Encabezado ===== --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-primary font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-store text-[#FFAF22]"></i> Tu negocio hoy
            </h2>
            @if(!empty($data['meta']))
                <p class="text-sm text-gray-500">
                    {{ ucfirst($data['meta']['periodo']) }} · día {{ $data['meta']['dia_del_mes'] }} de {{ $data['meta']['dias_del_mes'] }}
                    · actualizado {{ $data['meta']['generado'] }}
                </p>
            @endif
        </div>
        <button wire:click="refrescar" wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#222036] text-white rounded-xl hover:bg-[#FFAF22] transition-colors disabled:opacity-50">
            <i class="fas fa-rotate-right" wire:loading.class="animate-spin" wire:target="refrescar"></i>
            <span wire:loading.remove wire:target="refrescar">Actualizar</span>
            <span wire:loading wire:target="refrescar">Calculando…</span>
        </button>
    </div>

    @if($error)
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-6 text-center">
            <i class="fas fa-triangle-exclamation text-2xl mb-2"></i>
            <p>{{ $error }}</p>
        </div>
    @elseif($cargando)
        <div class="bg-white rounded-2xl p-12 text-center text-gray-400 shadow-sm">
            <i class="fas fa-circle-notch fa-spin text-3xl mb-3"></i>
            <p>Calculando indicadores…</p>
        </div>
    @elseif(empty($data))
        <div class="bg-white rounded-2xl p-12 text-center text-gray-400 shadow-sm">
            <i class="fas fa-inbox text-3xl mb-3"></i>
            <p>Sin datos suficientes para mostrar indicadores.</p>
        </div>
    @else

        {{-- ===== 3 cosas para mirar hoy ===== --}}
        <div class="bg-[#222036] rounded-2xl p-5 shadow-lg">
            <h3 class="text-white font-primary text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-bullseye text-[#FFAF22]"></i> Las 3 cosas para mirar hoy
            </h3>
            @if(count($data['prioridades']) === 0)
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 flex items-center gap-3">
                    <i class="fas fa-circle-check text-xl"></i>
                    <span>Sin alertas relevantes hoy. El negocio viene ordenado.</span>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($data['prioridades'] as $i => $p)
                        @php $pc = $sc($p['semaforo']); @endphp
                        <div class="bg-white rounded-xl p-4 border-l-4 {{ $pc['ring'] }} shadow">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-gray-400">#{{ $i + 1 }}</span>
                                <span class="w-3 h-3 rounded-full {{ $pc['dot'] }}"></span>
                            </div>
                            <p class="font-semibold text-gray-800 leading-tight">{{ $p['titulo'] }}</p>
                            @if($p['impacto'] > 0)
                                <p class="text-lg font-primary {{ $pc['text'] }} my-1">{{ $money($p['impacto']) }}</p>
                            @endif
                            <p class="text-xs text-gray-500 leading-snug">{{ $p['detalle'] }}</p>
                            <p class="text-xs text-gray-700 mt-2"><i class="fas fa-lightbulb text-[#FFAF22] mr-1"></i>{{ $p['accion'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ===== De dónde vienen tus ventas (mostrador vs clientes con nombre) ===== --}}
        {{-- Bloque descriptivo, sin semáforo: el mostrador suma al total pero
             no es un cliente, así que no entra a ningún ranking ni conteo. --}}
        @if(!empty($data['origen']))
            @php $o = $data['origen']; @endphp
            <div class="bg-white rounded-2xl p-5 shadow-sm">
                <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas {{ $o['icono'] }} text-[#FFAF22]"></i> {{ $o['titulo'] }}
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                            {{ $o['label_mostrador'] }}
                        </div>
                        <div class="text-2xl font-primary text-gray-800">{{ $money($o['fact_mostrador']) }}</div>
                        <div class="text-sm text-gray-500">{{ $o['pct_mostrador_txt'] }}% de tus ventas</div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ number_format($o['comp_mostrador'], 0, ',', '.') }} comp. · ticket {{ $money($o['ticket_mostrador']) }}
                        </div>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-4">
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FFAF22]"></span>
                            Clientes con nombre
                        </div>
                        <div class="text-2xl font-primary text-gray-800">{{ $money($o['fact_ident']) }}</div>
                        <div class="text-sm text-gray-500">{{ $o['pct_ident_txt'] }}% de tus ventas</div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ number_format($o['clientes_ident'], 0, ',', '.') }} {{ $o['clientes_ident'] === 1 ? 'cliente' : 'clientes' }}
                            · ticket {{ $money($o['ticket_ident']) }}
                        </div>
                    </div>
                </div>

                {{-- Barra apilada: el mismo total, partido en los dos orígenes --}}
                <div class="flex w-full h-2.5 rounded-full overflow-hidden bg-gray-100 mb-3">
                    <div class="bg-gray-400 h-full" style="width: {{ max(0, min(100, round($o['pct_mostrador']))) }}%"></div>
                    <div class="bg-[#FFAF22] h-full" style="width: {{ max(0, min(100, round($o['pct_ident']))) }}%"></div>
                </div>

                <p class="text-xs text-gray-500 leading-snug">{{ $o['lectura'] }}</p>
                <p class="text-xs text-gray-700 mt-2 pt-2 border-t border-gray-100">
                    <i class="fas fa-lightbulb text-[#FFAF22] mr-1"></i>{{ $o['accion'] }}
                </p>
            </div>
        @endif

        {{-- ===== Tarjetas de indicadores ===== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @php
                $cards = [
                    $data['crecimiento'], $data['ritmo'], $data['ticket'],
                    $data['concentracion'], $data['descuentos'],
                ];
            @endphp
            @foreach($cards as $card)
                @php $cc = $sc($card['semaforo']); @endphp
                <div class="bg-white rounded-2xl p-5 shadow-sm border {{ $cc['ring'] }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2 text-gray-700">
                            <i class="fas {{ $card['icono'] }} text-[#FFAF22]"></i>
                            <span class="font-semibold">{{ $card['titulo'] }}</span>
                        </div>
                        <span class="w-3 h-3 rounded-full {{ $cc['dot'] }}"></span>
                    </div>

                    {{-- Valores destacados por indicador --}}
                    @if($card['titulo'] === 'Crecimiento (nominal)')
                        <div class="flex items-end gap-3 mb-2">
                            <span class="text-2xl font-primary text-gray-800">{{ $money($card['valor_actual']) }}</span>
                            <div class="pb-1">
                                <div>Mensual {!! $pctBadge($card['mom_pct']) !!}</div>
                                <div>Interanual {!! $pctBadge($card['yoy_pct']) !!}</div>
                            </div>
                        </div>
                    @elseif($card['titulo'] === 'Ritmo del mes')
                        <div class="flex items-end gap-3 mb-2">
                            <span class="text-2xl font-primary text-gray-800">{{ $money($card['proyeccion']) }}</span>
                            <div class="pb-1">Proyección {!! $pctBadge($card['pct']) !!}</div>
                        </div>
                    @elseif($card['titulo'] === 'Ticket promedio y unidades')
                        <div class="flex items-end gap-4 mb-2">
                            <div>
                                <span class="text-2xl font-primary text-gray-800">{{ $money($card['ticket_prom']) }}</span>
                                <div class="text-xs text-gray-500">Ticket {!! $pctBadge($card['ticket_pct']) !!}</div>
                            </div>
                            <div>
                                <span class="text-2xl font-primary text-gray-800">{{ number_format($card['unidades_ticket'], 1) }}</span>
                                <div class="text-xs text-gray-500">Unid./ticket {!! $pctBadge($card['unidades_pct']) !!}</div>
                            </div>
                        </div>
                    @elseif($card['titulo'] === 'Concentración')
                        <div class="grid grid-cols-2 gap-2 mb-2 text-center">
                            <div class="bg-gray-50 rounded-lg py-2">
                                <div class="text-xl font-primary text-gray-800">{{ number_format($card['top1_prod_pct'], 0) }}%</div>
                                <div class="text-xs text-gray-500">Top producto</div>
                            </div>
                            {{-- El % grande es sobre el TOTAL facturado; el de la
                                 cartera identificada va abajo, en chico. Si casi
                                 todo es mostrador el dato no se evalúa. --}}
                            <div class="bg-gray-50 rounded-lg py-2">
                                @if($card['cli_relevante'] ?? false)
                                    <div class="text-xl font-primary text-gray-800">{{ number_format($card['top1_cli_pct'], 0) }}%</div>
                                    <div class="text-xs text-gray-500">Top cliente</div>
                                    <div class="text-[11px] text-gray-400">
                                        {{ number_format($card['top1_cli_pct_ident'] ?? 0, 0) }}% de clientes con nombre
                                    </div>
                                @else
                                    <div class="text-xl font-primary text-gray-300">—</div>
                                    <div class="text-xs text-gray-500">Top cliente</div>
                                    <div class="text-[11px] text-gray-400">casi todo es mostrador</div>
                                @endif
                            </div>
                        </div>
                    @elseif($card['titulo'] === 'Descuentos otorgados')
                        <div class="flex items-end gap-3 mb-2">
                            <span class="text-2xl font-primary text-gray-800">{{ $money($card['monto']) }}</span>
                            <span class="pb-1 text-sm text-gray-500">{{ number_format($card['pct'], 1) }}% de ventas</span>
                        </div>
                    @endif

                    <p class="text-xs text-gray-500 leading-snug">{{ $card['lectura'] }}</p>
                    <p class="text-xs text-gray-700 mt-2 pt-2 border-t border-gray-100">
                        <i class="fas fa-lightbulb text-[#FFAF22] mr-1"></i>{{ $card['accion'] }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- ===== Top productos / clientes / medios de pago ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Top productos --}}
            <div class="bg-white rounded-2xl p-5 shadow-sm">
                <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-box text-[#FFAF22]"></i> Top productos del mes
                </h3>
                @forelse($data['top_productos'] as $p)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <span class="text-sm text-gray-600 truncate mr-2">{{ mb_substr($p['detalle'], 0, 28) }}</span>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-semibold text-gray-800">{{ $money($p['fact']) }}</div>
                            @if($p['margen'] === null)
                                <div class="text-xs text-gray-400" title="Este producto no tiene costo cargado">s/costo</div>
                            @else
                                <div class="text-xs text-gray-400">
                                    margen {{ $money($p['margen']) }}@if($p['margen_pct'] !== null) <span class="text-gray-300">({{ number_format($p['margen_pct'], 0) }}%)</span>@endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Sin ventas en el período.</p>
                @endforelse
            </div>

            {{-- Top clientes --}}
            <div class="bg-white rounded-2xl p-5 shadow-sm">
                <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-users text-[#FFAF22]"></i> Top clientes del mes
                </h3>
                {{-- Fila fija de mostrador: da la magnitud sin competir en el
                     ranking. No es un cliente, por eso va en gris y aparte. --}}
                @if(!empty($data['origen']) && $data['origen']['fact_mostrador'] > 0)
                    @php $o = $data['origen']; @endphp
                    <div class="flex items-start justify-between py-2 mb-1 px-2 -mx-2 rounded-lg bg-gray-50">
                        <div class="min-w-0 mr-2">
                            <div class="text-sm text-gray-500 truncate flex items-center gap-1.5">
                                <i class="fas fa-cash-register text-gray-400 text-xs"></i>
                                {{ $o['label_mostrador'] }}
                            </div>
                            <div class="text-xs text-gray-400">No es un cliente: agrupa las ventas sin identificar.</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-semibold text-gray-500">{{ $money($o['fact_mostrador']) }}</div>
                            <div class="text-xs text-gray-400">{{ $o['pct_mostrador_txt'] }}% del total</div>
                        </div>
                    </div>
                @endif

                @forelse($data['top_clientes'] as $c)
                    <div class="flex items-start justify-between py-2 border-b border-gray-50 last:border-0">
                        <div class="min-w-0 mr-2">
                            <div class="text-sm text-gray-600 truncate">{{ mb_substr($c['nombre'], 0, 28) }}</div>
                            {{-- Hay clientes con el mismo nombre: código y dirección los distinguen --}}
                            <div class="text-xs text-gray-400 truncate">
                                <span class="font-mono">#{{ $c['codcli'] }}</span>@if(!empty($c['direccion'])) · {{ mb_substr($c['direccion'], 0, 30) }}@endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-semibold text-gray-800">{{ $money($c['fact']) }}</div>
                            <div class="text-xs text-gray-400">
                                {{ number_format($c['pct_total'] ?? 0, 1) }}% del total · {{ $c['comprobantes'] }} comp.
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Todavía no hay clientes con nombre cargados este mes.</p>
                @endforelse
            </div>

            {{-- Medios de pago --}}
            <div class="bg-white rounded-2xl p-5 shadow-sm">
                <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-[#FFAF22]"></i> Medios de pago
                </h3>
                @forelse($data['medios_pago'] as $m)
                    <div class="py-2">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 truncate mr-2">{{ $m['forma'] }}</span>
                            <span class="font-semibold text-gray-800 shrink-0">{{ $money($m['total']) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-[#FFAF22] h-2 rounded-full" style="width: {{ min(100, round($m['pct'])) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Sin registros de formas de pago.</p>
                @endforelse
            </div>
        </div>

    @endif
</div>
