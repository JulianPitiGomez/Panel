<div class="space-y-4 font-secondary">
    <p class="text-sm text-gray-500">Servicios de terceros con la cuenta del comercio. Las claves se guardan cifradas y no se vuelven a mostrar: si querés cambiarlas, pegás una nueva.</p>

    @if($mensaje)
        <div class="px-4 py-3 rounded-xl text-sm {{ $tipoMensaje === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $mensaje }} <button wire:click="$set('mensaje', '')" class="float-right">&times;</button>
        </div>
    @endif

    {{-- Asistente de IA --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h3 class="font-primary font-semibold text-gray-800"><i class="fas fa-magic text-[#FFAF22] mr-2"></i>Asistente de IA en la tienda</h3>
        <p class="text-xs text-gray-400 mt-1 mb-4">Con tu propia clave de <strong>Anthropic</strong> (console.anthropic.com). Cuesta centavos por consulta y se cobra en tu cuenta. Si no la cargás, la tienda funciona igual, sin asistente.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
                <input type="checkbox" wire:model="iaBusqueda" class="mt-1 w-4 h-4 rounded text-[#FFAF22]">
                <div><span class="text-sm font-medium text-gray-700">Búsqueda que entiende</span><p class="text-xs text-gray-400">Si el comprador escribe "algo para el mate" y el buscador no encuentra nada, la IA lo traduce a productos de tu catálogo.</p></div>
            </label>
            <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
                <input type="checkbox" wire:model="iaPedido" class="mt-1 w-4 h-4 rounded text-[#FFAF22]">
                <div><span class="text-sm font-medium text-gray-700">Pedido desde un mensaje</span><p class="text-xs text-gray-400">El comprador escribe "2 kg de arroz, 3 gaseosas y una yerba" y la IA le arma el carrito para que lo revise.</p></div>
            </label>
        </div>

        @if($iaEstado['cargada'])
            <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 text-sm mb-3">
                <div>
                    <span class="font-medium text-gray-700"><i class="fas fa-key mr-1 text-gray-400"></i>Clave cargada</span>
                    @if($iaEstado['verificada'])<span class="ml-2 text-green-600"><i class="fas fa-check-circle mr-1"></i>Funciona{{ $iaEstado['dato'] ? ' (' . $iaEstado['dato'] . ')' : '' }}</span>
                    @elseif($iaEstado['error'])<span class="ml-2 text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $iaEstado['error'] }}</span>
                    @else<span class="ml-2 text-gray-400">Sin probar todavía</span>@endif
                </div>
                <button type="button" wire:click="quitarIa" wire:confirm="¿Quitar la clave de IA? El asistente deja de ofrecerse." class="text-xs text-red-500 hover:underline">Quitar</button>
            </div>
        @endif
        <div class="flex flex-col sm:flex-row gap-2">
            <input type="password" wire:model="iaClave" autocomplete="off" placeholder="{{ $iaEstado['cargada'] ? 'Pegá una clave nueva solo si querés reemplazarla' : 'sk-ant-…' }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            <input type="text" wire:model="iaModelo" placeholder="Modelo (opcional)" class="sm:w-56 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            <button type="button" wire:click="probarIa" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#FFAF22]/15 text-[#B8860B] hover:bg-[#FFAF22]/30"><i class="fas fa-plug mr-1"></i> Probar</button>
            <button type="button" wire:click="guardarIa" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#1E1B35] text-white hover:bg-[#2d2952]"><i class="fas fa-save mr-1"></i> Guardar</button>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">Sin modelo, se usa uno rápido y económico pensado para responderle al comprador al instante.</p>
    </div>

    {{-- WhatsApp --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h3 class="font-primary font-semibold text-gray-800"><i class="fab fa-whatsapp text-[#25D366] mr-2"></i>Avisos por WhatsApp (API de Meta)</h3>
        <p class="text-xs text-gray-400 mt-1 mb-4">Con tu número verificado en Meta y tus plantillas aprobadas. Cada mensaje tiene el costo que Meta te cobra a vos. Los avisos por mail siguen saliendo igual. Los links "escribinos por WhatsApp" de la tienda no necesitan nada de esto.</p>

        @if($waEstado['cargado'])
            <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 text-sm mb-3">
                <div>
                    <span class="font-medium text-gray-700"><i class="fas fa-key mr-1 text-gray-400"></i>Token cargado</span>
                    @if($waEstado['verificada'])<span class="ml-2 text-green-600"><i class="fas fa-check-circle mr-1"></i>Verificado{{ $waEstado['dato'] ? ': ' . $waEstado['dato'] : '' }}</span>
                    @elseif($waEstado['error'])<span class="ml-2 text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $waEstado['error'] }}</span>
                    @else<span class="ml-2 text-gray-400">Sin probar todavía</span>@endif
                </div>
                <button type="button" wire:click="quitarWhatsapp" wire:confirm="¿Quitar WhatsApp de la tienda?" class="text-xs text-red-500 hover:underline">Quitar</button>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Token permanente (System User)</label>
                <input type="password" wire:model="waToken" autocomplete="off" placeholder="{{ $waEstado['cargado'] ? 'Pegá uno nuevo solo si querés reemplazarlo' : 'EAAG…' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">ID del número de teléfono (Phone number ID)</label>
                <input type="text" wire:model="wa.phone_number_id" placeholder="1234567890" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Idioma de las plantillas</label>
                <input type="text" wire:model="wa.idioma" placeholder="es_AR" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tu número para recibir los avisos de pedidos</label>
                <input type="text" wire:model="wa.telefono_comercio" placeholder="2324 123456" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-700 mb-2">Plantillas aprobadas en Meta (nombre exacto) y qué avisos mandar</p>
            <div class="space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-[auto_1fr] gap-2 items-center">
                    <label class="flex items-center gap-2 text-sm text-gray-700 sm:w-64"><input type="checkbox" wire:model="wa.avisa_pedido" class="w-4 h-4 rounded text-[#FFAF22]"> Pedido nuevo</label>
                    <input type="text" wire:model="wa.plantilla_pedido" placeholder="pedido_recibido · @{{1}} nombre, @{{2}} nº, @{{3}} total, @{{4}} tienda" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[auto_1fr] gap-2 items-center">
                    <label class="flex items-center gap-2 text-sm text-gray-700 sm:w-64"><input type="checkbox" wire:model="wa.avisa_estado" class="w-4 h-4 rounded text-[#FFAF22]"> Cambio de estado del pedido</label>
                    <input type="text" wire:model="wa.plantilla_estado" placeholder="pedido_estado · @{{1}} nombre, @{{2}} nº, @{{3}} estado, @{{4}} tienda" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[auto_1fr] gap-2 items-center">
                    <label class="flex items-center gap-2 text-sm text-gray-700 sm:w-64"><input type="checkbox" wire:model="wa.avisa_carrito" class="w-4 h-4 rounded text-[#FFAF22]"> Carrito abandonado, después de <input type="number" min="1" max="72" wire:model="wa.carrito_horas" class="w-14 px-1 py-0.5 border border-gray-300 rounded text-sm text-center"> hs</label>
                    <input type="text" wire:model="wa.plantilla_carrito" placeholder="carrito_abandonado · @{{1}} nombre, @{{2}} cantidad, @{{3}} tienda, @{{4}} link" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Las plantillas las creás y aprobás en el WhatsApp Manager de Meta, con esas variables en el cuerpo. El aviso de carrito abandonado lo manda el servidor una vez por día (comando <span class="font-mono">tienda:carritos-abandonados</span>) y es de la categoría marketing, la más cara.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 mt-4">
            <input type="text" wire:model="waTelefonoPrueba" placeholder="Número para la prueba (ej. 2324 123456)" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <button type="button" wire:click="probarWhatsapp" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#25D366]/15 text-[#128C7E] hover:bg-[#25D366]/30"><i class="fab fa-whatsapp mr-1"></i> Mandar prueba</button>
            <button type="button" wire:click="guardarWhatsapp" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#1E1B35] text-white hover:bg-[#2d2952]"><i class="fas fa-save mr-1"></i> Guardar</button>
        </div>
    </div>

    {{-- Correo Argentino (API MiCorreo) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h3 class="font-primary font-semibold text-gray-800"><i class="fas fa-shipping-fast text-[#0057A8] mr-2"></i>Correo Argentino: cotización automática (API MiCorreo)</h3>
        <p class="text-xs text-gray-400 mt-1 mb-4">Con tu cuenta de <strong>MiCorreo</strong>, la tienda le cotiza al comprador el envío a domicilio con el precio real del Correo para su código postal y el peso del pedido. Es opcional: si no la cargás, se usa el tarifario manual que armás en Envíos, que además queda de respaldo si el Correo no responde. Las credenciales de la API (usuario y contraseña) se las pedís a Correo Argentino desde tu cuenta MiCorreo; son distintas de las que usás para entrar a la web.</p>

        @if($correoProveedorElegido === null)
            <div class="mb-3 px-4 py-3 rounded-xl text-xs bg-amber-50 text-amber-800 border border-amber-200"><i class="fas fa-info-circle mr-1"></i>El envío por correo está apagado en <strong>Envíos</strong>. Podés cargar la cuenta igual, pero la tienda no la va a usar hasta que lo prendas.</div>
        @elseif($correoProveedorElegido !== 'correo_argentino')
            <div class="mb-3 px-4 py-3 rounded-xl text-xs bg-amber-50 text-amber-800 border border-amber-200"><i class="fas fa-info-circle mr-1"></i>En <strong>Envíos</strong> el transportista elegido no es Correo Argentino. Para cotizar con la API, elegí Correo Argentino ahí.</div>
        @endif

        @if($correoEstado['cargado'])
            <div class="flex flex-wrap items-center justify-between gap-2 bg-gray-50 rounded-xl px-4 py-3 text-sm mb-3">
                <div>
                    <span class="font-medium text-gray-700"><i class="fas fa-key mr-1 text-gray-400"></i>Cuenta cargada</span>
                    @if($correoEstado['verificada'])<span class="ml-2 text-green-600"><i class="fas fa-check-circle mr-1"></i>Verificada{{ $correoEstado['dato'] ? ': ' . $correoEstado['dato'] : '' }}</span>
                    @elseif($correoEstado['error'])<span class="ml-2 text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $correoEstado['error'] }}</span>
                    @else<span class="ml-2 text-gray-400">Sin probar todavía</span>@endif
                    @if(!$correoEstado['activa'])<span class="ml-2 text-amber-600"><i class="fas fa-pause-circle mr-1"></i>Apagada: la tienda usa el tarifario manual</span>@endif
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="alternarCorreo" class="text-xs text-[#0057A8] hover:underline">{{ $correoEstado['activa'] ? 'Apagar cotización por API' : 'Prender cotización por API' }}</button>
                    <button type="button" wire:click="quitarCorreo" wire:confirm="¿Quitar la cuenta de Correo Argentino? La tienda vuelve al tarifario manual." class="text-xs text-red-500 hover:underline">Quitar</button>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Ambiente</label>
                <select wire:model="correo.env" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="produccion">Producción (cuenta real)</option>
                    <option value="test">Prueba (credenciales de test del Correo)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Usuario de la API</label>
                <input type="text" wire:model="correo.usuario" autocomplete="off" placeholder="usuario que te dio el Correo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Contraseña de la API</label>
                <input type="password" wire:model="correoPassword" autocomplete="off" placeholder="{{ $correoEstado['cargado'] ? 'Pegá una nueva solo si querés reemplazarla' : 'contraseña de la API' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-700 mb-1">Customer ID de tu cuenta MiCorreo</p>
            <p class="text-[11px] text-gray-400 mb-3">Es el número que identifica tu cuenta. Si no lo sabés, escribí el email y la contraseña con los que entrás a la web de MiCorreo y lo buscamos: la contraseña se usa una sola vez y no se guarda.</p>
            <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_auto] gap-2 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Customer ID</label>
                    <input type="text" wire:model="correo.customer_id" autocomplete="off" placeholder="0000123456" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Email de MiCorreo</label>
                    <input type="email" wire:model="correo.micorreo_email" autocomplete="off" placeholder="vos@tucomercio.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Contraseña de MiCorreo (no se guarda)</label>
                    <input type="password" wire:model="correoMicorreoPassword" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <button type="button" wire:click="obtenerCustomerIdCorreo" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#0057A8]/10 text-[#0057A8] hover:bg-[#0057A8]/20 whitespace-nowrap"><i class="fas fa-search mr-1"></i> Obtener</button>
            </div>
        </div>

        @if(!empty($correoResultadoPrueba))
            <div class="mt-4 px-4 py-3 rounded-xl text-xs bg-green-50 text-green-800 border border-green-200 space-y-0.5">
                @foreach($correoResultadoPrueba as $linea)<div>{{ $linea }}</div>@endforeach
            </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-2 mt-4 sm:items-center">
            <div class="flex items-center gap-2 flex-1">
                <span class="text-xs text-gray-500 whitespace-nowrap">Probar cotizando desde el CP {{ $correoOrigenCp !== '' ? $correoOrigenCp : '(cargá el CP del local en Envíos)' }} a</span>
                <input type="text" wire:model="correoCpPrueba" maxlength="10" placeholder="CP destino" class="w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button type="button" wire:click="probarCorreo" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#0057A8]/10 text-[#0057A8] hover:bg-[#0057A8]/20"><i class="fas fa-plug mr-1"></i> Probar</button>
            <button type="button" wire:click="guardarCorreo" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg text-sm font-semibold bg-[#1E1B35] text-white hover:bg-[#2d2952]"><i class="fas fa-save mr-1"></i> Guardar</button>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">El peso y las medidas de cada artículo se cargan en Fotos Productos; los que no los tengan usan el peso y las medidas por defecto de Envíos. El pago del envío y la impresión del rótulo siguen haciéndose en la web de MiCorreo.</p>
    </div>
</div>
