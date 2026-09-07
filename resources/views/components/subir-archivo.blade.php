{{--
    Zona de subida de archivos para Livewire: arrastrar y soltar o hacer clic, nombre y
    tamaño de lo elegido, miniatura si es imagen, barra de progreso mientras sube.
    Reemplaza al <input type="file"> pelado en todo el sistema.

    <x-subir-archivo model="fotoLogo" accept="image/*" titulo="Arrastrá el logo acá" ayuda="PNG o JPG, hasta 2 MB" />
    <x-subir-archivo model="fotosNuevas" accept="image/*" :multiple="true" />
    <x-subir-archivo model="planillaPesos" accept=".csv" :compacto="true" tema="panel" />

    tema: 'panel' (naranja del panel) o 'tienda' (colores del comercio, variables --t*).
--}}
@props(['model', 'accept' => '', 'multiple' => false, 'titulo' => null, 'ayuda' => null, 'tema' => 'panel', 'compacto' => false])
@php
    $esTienda = $tema === 'tienda';
    $acento = $esTienda ? 'var(--tp)' : '#FFAF22';
    $acentoSuave = $esTienda ? 'var(--tp-soft)' : '#FFF4DE';
    $borde = $esTienda ? 'var(--tborde)' : '#d1d5db';
    $texto = $esTienda ? 'var(--tt)' : '#374151';
    $muted = $esTienda ? 'var(--tt-muted)' : '#9ca3af';
    $titulo = $titulo ?? ($multiple ? 'Arrastrá los archivos acá o hacé clic para elegirlos' : 'Arrastrá el archivo acá o hacé clic para elegirlo');
@endphp
<div x-data="{
        archivos: [], arrastrando: false, subiendo: false, progreso: 0, error: false, multiple: {{ $multiple ? 'true' : 'false' }},
        elegir(files) {
            this.archivos = Array.from(files).map(f => ({ nombre: f.name, tamano: f.size, url: f.type.startsWith('image/') ? URL.createObjectURL(f) : null }));
            this.error = false;
        },
        soltar(e) {
            this.arrastrando = false;
            const dt = new DataTransfer();
            Array.from(e.dataTransfer.files).slice(0, this.multiple ? 50 : 1).forEach(f => dt.items.add(f));
            if (!dt.files.length) return;
            this.$refs.input.files = dt.files;
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.elegir(dt.files);
        },
        limpiar() { this.archivos = []; this.progreso = 0; this.subiendo = false; if (this.$refs.input) this.$refs.input.value = ''; },
        tam(n) { return n > 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
     }"
     x-init="$wire.$watch('{{ $model }}', v => { if (v === null || v === '' || (Array.isArray(v) && !v.length)) limpiar() })"
     x-on:livewire-upload-start="subiendo = true; progreso = 0; error = false"
     x-on:livewire-upload-progress="progreso = $event.detail.progress"
     x-on:livewire-upload-finish="subiendo = false; progreso = 100"
     x-on:livewire-upload-error="subiendo = false; error = true"
     {{ $attributes->merge(['class' => 'w-full']) }}>
    <label @dragover.prevent="arrastrando = true" @dragleave.prevent="arrastrando = false" @drop.prevent="soltar($event)"
           class="block cursor-pointer rounded-xl border-2 border-dashed transition-colors {{ $compacto ? 'px-3 py-2' : 'px-4 py-5' }}"
           :style="arrastrando ? 'border-color: {{ $acento }}; background-color: {{ $acentoSuave }};' : 'border-color: {{ $borde }};'">
        <input type="file" wire:model="{{ $model }}" @if($accept) accept="{{ $accept }}" @endif @if($multiple) multiple @endif x-ref="input" @change="elegir($event.target.files)" class="sr-only">

        {{-- Sin archivo elegido --}}
        <div x-show="!archivos.length" class="flex items-center gap-3 {{ $compacto ? '' : 'flex-col text-center' }}">
            <span class="flex items-center justify-center rounded-full flex-shrink-0 {{ $compacto ? 'w-8 h-8 text-sm' : 'w-12 h-12 text-xl' }}" style="background-color: {{ $acentoSuave }}; color: {{ $acento }};">
                <i class="fas fa-cloud-upload-alt"></i>
            </span>
            <span class="min-w-0">
                <span class="block {{ $compacto ? 'text-xs' : 'text-sm' }} font-medium" style="color: {{ $texto }};">{{ $titulo }}</span>
                @if($ayuda)<span class="block text-[11px] mt-0.5" style="color: {{ $muted }};">{{ $ayuda }}</span>@endif
            </span>
        </div>

        {{-- Archivo(s) elegido(s) --}}
        <div x-show="archivos.length" x-cloak class="space-y-2">
            <template x-for="(a, i) in archivos" :key="i">
                <div class="flex items-center gap-3">
                    <template x-if="a.url"><img :src="a.url" class="w-12 h-12 rounded-lg object-cover border flex-shrink-0" style="border-color: {{ $borde }};"></template>
                    <template x-if="!a.url"><span class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 text-lg" style="background-color: {{ $acentoSuave }}; color: {{ $acento }};"><i class="fas fa-file-alt"></i></span></template>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium truncate" style="color: {{ $texto }};" x-text="a.nombre"></span>
                        <span class="block text-[11px]" style="color: {{ $muted }};" x-text="tam(a.tamano)"></span>
                    </span>
                </div>
            </template>
            <div class="flex items-center justify-between text-[11px]" style="color: {{ $muted }};">
                <span x-show="subiendo"><i class="fas fa-spinner fa-spin mr-1"></i> Subiendo… <span x-text="progreso + '%'"></span></span>
                <span x-show="!subiendo && !error"><i class="fas fa-check mr-1" style="color: #16a34a;"></i> Listo para enviar</span>
                <span x-show="error" style="color: #dc2626;"><i class="fas fa-exclamation-circle mr-1"></i> No se pudo cargar el archivo</span>
                <span class="underline">{{ $multiple ? 'Elegir otros' : 'Cambiar' }}</span>
            </div>
            <div x-show="subiendo" class="h-1.5 rounded-full overflow-hidden" style="background-color: {{ $borde }};">
                <div class="h-full rounded-full transition-all" :style="'width: ' + progreso + '%; background-color: {{ $acento }};'"></div>
            </div>
        </div>
    </label>
</div>
