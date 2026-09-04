<div class="pb-28 px-3" style="padding-bottom: calc(7rem + env(safe-area-inset-bottom));">

    <!-- Encabezado -->
    <div class="bg-[#222036] text-white rounded-lg p-4 mb-4 mt-3 shadow-md">
        <h2 class="text-lg font-semibold">Nuevo Cliente</h2>
        <p class="text-sm">Complete los datos del nuevo cliente</p>
    </div>

    <!-- Mensaje de éxito -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-md p-5">
        <form wire:submit.prevent="guardar">

            <!-- Nombre -->
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">
                    Nombre / Razón Social <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model="nombre"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFAF22]"
                       placeholder="Ingrese el nombre del cliente">
                @error('nombre')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Dirección -->
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">
                    Dirección <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model="direccion"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFAF22]"
                       placeholder="Ingrese la dirección">
                @error('direccion')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Localidad -->
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">
                    Localidad <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model="localidad"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFAF22]"
                       placeholder="Ingrese la localidad">
                @error('localidad')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Teléfono -->
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">
                    Teléfono
                </label>
                <input type="text"
                       wire:model="telefono"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFAF22]"
                       placeholder="Ingrese el teléfono">
                @error('telefono')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- CUIT -->
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">
                    CUIT
                </label>
                <input type="text"
                       wire:model.live="cuit"
                       maxlength="13"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFAF22]"
                       placeholder="XX-XXXXXXXX-X">
                @error('cuit')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Condición IVA -->
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">
                    Condición IVA <span class="text-red-500">*</span>
                </label>
                <select wire:model="coniva"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                    <option value="1">Responsable Inscripto</option>
                    <option value="4">Exento</option>
                    <option value="5">Consumidor Final</option>
                    <option value="6">Monotributista</option>
                </select>
                @error('coniva')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Lista de Precios Especial (solo si LISTAPRE = 3) -->
            @if(session('vendedor_listapre') == 3)
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Lista de Precios Especial <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="lispreesp"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFAF22]">
                        <option value="">Seleccione una lista...</option>
                        @foreach($listasEspeciales as $lista)
                            <option value="{{ $lista->CODIGO }}">{{ $lista->NOMBRE }}</option>
                        @endforeach
                    </select>
                    @error('lispreesp')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            @endif

            <!-- Información de Lista de Precios asignada -->
            <div class="mb-6 bg-gray-100 rounded-lg p-3">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    @if(session('vendedor_listapre') == 1)
                        Este cliente será asignado a <strong>Lista de Precios 1</strong>
                    @elseif(session('vendedor_listapre') == 2)
                        Este cliente será asignado a <strong>Lista de Precios 2</strong>
                    @elseif(session('vendedor_listapre') == 3)
                        Este cliente será asignado a la <strong>Lista de Precios Especial</strong> seleccionada
                    @endif
                </p>
            </div>

            <!-- Botones -->
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button"
                        wire:click="cancelar"
                        class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-4 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 bg-[#FFAF22] hover:bg-[#E89B0A] text-white font-semibold py-3 px-4 rounded-lg shadow-lg transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Cliente
                </button>
            </div>
        </form>
    </div>

</div>
