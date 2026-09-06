<div class="max-w-lg mx-auto py-8">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h1 class="text-2xl font-bold mb-1" style="color: {{ $colores['texto'] }}">Botón de arrepentimiento</h1>
        <p class="text-sm text-gray-500 mb-6">Podés revocar una compra hecha por la web dentro de los 10 días corridos de recibida, sin costo. Completá el formulario y el comercio se comunica con vos.</p>

        @if($enviado)
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm mb-4">
                <i class="fas fa-check-circle mr-1"></i> Recibimos tu pedido de revocación. El comercio te va a contactar al mail que indicaste.
            </div>
            <a href="{{ route('tienda.catalogo', $tienda) }}" class="text-sm underline text-gray-500">Volver a la tienda</a>
        @else
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('tienda.arrepentimiento.post', $tienda) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre y apellido</label>
                    <input type="text" name="nombre" value="{{ old('nombre', session('tienda_comprador_nombre')) }}" required maxlength="100" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', session('tienda_comprador_email')) }}" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de pedido <span class="text-gray-400 font-normal">(si lo tenés)</span></label>
                    <input type="number" name="pedido" value="{{ old('pedido') }}" min="1" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <textarea name="motivo" rows="3" maxlength="1000" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">{{ old('motivo') }}</textarea>
                </div>
                <button type="submit" class="w-full py-3 text-white font-semibold rounded-lg" style="background-color: {{ $colores['boton'] }}">Enviar solicitud</button>
            </form>
        @endif
    </div>
</div>
