<div class="space-y-6 font-secondary">
    <p class="text-sm text-gray-500">Cuándo entregás los pedidos de la tienda. Pensado para el que fabrica a pedido: la tienda ofrece solo los días y horarios que podés cumplir.</p>

    @if($mensaje)
        <div class="px-4 py-3 rounded-xl text-sm {{ $tipoMensaje === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $mensaje }} <button wire:click="$set('mensaje', '')" class="float-right">&times;</button>
        </div>
    @endif

    {{-- Estado de la tienda --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 flex flex-col md:flex-row md:items-center gap-4">
        <div class="flex-1">
            <h3 class="font-primary font-semibold text-gray-800"><i class="fas {{ $config['abierta'] ? 'fa-door-open text-green-500' : 'fa-door-closed text-red-500' }} mr-2"></i>La tienda está {{ $config['abierta'] ? 'abierta' : 'cerrada' }}</h3>
            <p class="text-xs text-gray-400">Cerrala cuando no puedas tomar pedidos (vacaciones, corte de luz, demasiado trabajo). Se abre de nuevo con un toque.</p>
            <div class="flex flex-wrap gap-4 mt-3">
                <label class="flex items-center gap-2 text-xs text-gray-600"><input type="checkbox" wire:model.live="config.recibe_cerrada" class="w-4 h-4 rounded text-[#FFAF22]"> Cerrada, igual recibe pedidos (con aviso)</label>
                <label class="flex items-center gap-2 text-xs text-gray-600"><input type="checkbox" wire:model.live="config.muestra_cerrada" class="w-4 h-4 rounded text-[#FFAF22]"> Mostrar el aviso de cerrado en la tienda</label>
            </div>
        </div>
        <button type="button" wire:click="alternarAbierta" class="px-5 py-2.5 rounded-xl font-semibold text-sm text-white {{ $config['abierta'] ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600' }}">
            {{ $config['abierta'] ? 'Cerrar la tienda' : 'Abrir la tienda' }}
        </button>
    </div>

    {{-- Reglas --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <label class="flex items-start gap-3 cursor-pointer mb-4">
            <input type="checkbox" wire:model.live="config.activa" class="mt-1 w-4 h-4 rounded text-[#FFAF22] focus:ring-[#FFAF22]">
            <div>
                <span class="font-primary font-semibold text-gray-800"><i class="fas fa-calendar-check text-[#FFAF22] mr-2"></i>Usar agenda de entregas</span>
                <p class="text-xs text-gray-400">Apagada: el comprador elige cualquier fecha desde hoy, como hasta ahora. Encendida: elige entre los días y franjas de abajo.</p>
            </div>
        </label>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4" x-show="$wire.config.activa" x-transition>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Anticipación mínima (horas)</label>
                <input type="number" min="0" wire:model="config.anticipacion_min_horas" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <p class="text-[10px] text-gray-400 mt-1">Por artículo se carga en la planilla de Fotos Productos (columna anticipacion_horas).</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Hasta cuántos días adelante</label>
                <input type="number" min="1" max="120" wire:model="config.anticipacion_max_dias" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Margen antes de la franja (min)</label>
                <input type="number" min="0" wire:model="config.margen_minutos" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Hora de corte</label>
                <input type="time" wire:model="config.hora_corte" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <p class="text-[10px] text-gray-400 mt-1">Pasada esta hora, hoy ya no se ofrece.</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cupo de pedidos por día</label>
                <input type="number" min="0" wire:model="config.cupo_diario" placeholder="sin tope" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cupo de unidades por día</label>
                <input type="number" min="0" wire:model="config.cupo_unidades_diario" placeholder="sin tope" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <p class="text-[10px] text-gray-400 mt-1">Para el que cuenta tortas y no pedidos: suma las cantidades de los artículos que consumen cupo.</p>
            </div>
            <label class="col-span-2 md:col-span-5 flex items-center gap-2 text-xs text-gray-600">
                <input type="checkbox" wire:model="config.permite_lo_antes_posible" class="w-4 h-4 rounded text-[#FFAF22]"> Ofrecer también "lo antes posible" (toma la primera franja libre)
            </label>
        </div>
    </div>

    {{-- Franjas por dia --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6" x-show="$wire.config.activa" x-transition>
        <h3 class="font-primary font-semibold text-gray-800 mb-1"><i class="fas fa-clock text-[#FFAF22] mr-2"></i>Días y franjas de entrega</h3>
        <p class="text-xs text-gray-400 mb-4">Un día sin franjas no se ofrece. Los cupos por franja son opcionales: por cantidad de pedidos o por unidades.</p>

        <div class="space-y-3">
            @foreach($dias as $num => $nombre)
                @php $delDia = collect($franjas)->filter(fn ($f) => (int) $f['dia_semana'] === $num); @endphp
                <div class="rounded-xl border border-gray-200 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-700">{{ $nombre }} <span class="text-xs font-normal text-gray-400">{{ $delDia->count() ? '' : '· no entrega' }}</span></span>
                        <div class="flex gap-1">
                            @if($delDia->count() && $num !== 6)
                                <button type="button" wire:click="copiarFranjasA({{ $num }}, {{ $num === 0 ? 1 : $num + 1 }})" class="px-2 py-1 text-[11px] rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" title="Copiar estas franjas al día siguiente"><i class="fas fa-copy mr-1"></i>Copiar al siguiente</button>
                            @endif
                            <button type="button" wire:click="agregarFranja({{ $num }})" class="px-2 py-1 text-[11px] rounded-lg bg-[#FFAF22]/15 text-[#B8860B] hover:bg-[#FFAF22]/30"><i class="fas fa-plus mr-1"></i>Franja</button>
                        </div>
                    </div>
                    @foreach($franjas as $i => $f)
                        @if((int) $f['dia_semana'] === $num)
                            <div class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-center py-1.5 border-t border-gray-100" wire:key="franja-{{ $i }}">
                                <input type="time" wire:model="franjas.{{ $i }}.hora_desde" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                <input type="time" wire:model="franjas.{{ $i }}.hora_hasta" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                <div class="flex gap-1">
                                    <input type="number" min="0" wire:model="franjas.{{ $i }}.cupo_franja" placeholder="Pedidos" title="Cupo de pedidos de la franja" class="w-1/2 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                    <input type="number" min="0" wire:model="franjas.{{ $i }}.cupo_unidades" placeholder="Unid." title="Cupo de unidades de la franja" class="w-1/2 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <label class="flex items-center gap-1 text-xs text-gray-600"><input type="checkbox" wire:model="franjas.{{ $i }}.vale_envio" class="w-4 h-4 rounded text-[#FFAF22]"> Envío</label>
                                <label class="flex items-center gap-1 text-xs text-gray-600"><input type="checkbox" wire:model="franjas.{{ $i }}.vale_retiro" class="w-4 h-4 rounded text-[#FFAF22]"> Retiro</label>
                                <div class="flex items-center justify-between gap-2">
                                    <label class="flex items-center gap-1 text-xs text-gray-600"><input type="checkbox" wire:model="franjas.{{ $i }}.activa" class="w-4 h-4 rounded text-[#FFAF22]"> Activa</label>
                                    <button type="button" wire:click="quitarFranja({{ $i }})" class="text-red-400 hover:text-red-600 p-1"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- Excepciones --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6" x-show="$wire.config.activa" x-transition>
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-primary font-semibold text-gray-800"><i class="fas fa-calendar-times text-[#FFAF22] mr-2"></i>Excepciones</h3>
            <button type="button" wire:click="agregarExcepcion" class="px-2 py-1 text-[11px] rounded-lg bg-[#FFAF22]/15 text-[#B8860B] hover:bg-[#FFAF22]/30"><i class="fas fa-plus mr-1"></i>Fecha</button>
        </div>
        <p class="text-xs text-gray-400 mb-3">Feriados o vacaciones (cerrado), o un día que abrís fuera de lo habitual (abierto todo el día).</p>
        <div class="space-y-2">
            @forelse($excepciones as $i => $e)
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 items-center" wire:key="exc-{{ $i }}">
                    <input type="date" wire:model="excepciones.{{ $i }}.fecha" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    <select wire:model="excepciones.{{ $i }}.cerrado" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                        <option value="1">Cerrado</option>
                        <option value="0">Abierto todo el día</option>
                    </select>
                    <input type="text" wire:model="excepciones.{{ $i }}.motivo" maxlength="80" placeholder="Motivo (opcional)" class="col-span-2 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    <button type="button" wire:click="quitarExcepcion({{ $i }})" class="text-red-400 hover:text-red-600 p-1 justify-self-start"><i class="fas fa-trash-alt"></i></button>
                </div>
            @empty
                <p class="text-xs text-gray-400">Sin excepciones cargadas.</p>
            @endforelse
        </div>
    </div>

    <div class="flex justify-end">
        <button type="button" wire:click="guardar" wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-[#1E1B35] text-white rounded-lg hover:bg-[#2d2952] transition font-semibold text-sm disabled:opacity-50">
            <i class="fas fa-save mr-1"></i> Guardar agenda
        </button>
    </div>
</div>
