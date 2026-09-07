{{-- Sistema visual de la tienda: fuentes y variables CSS derivadas de los colores del comercio (TiendaEstiloService) --}}
@php $datosEstilo = session('tienda_datos', []); @endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@foreach(\App\Services\TiendaEstiloService::fuentesGoogle($datosEstilo) as $urlFuente)
    <link href="{{ $urlFuente }}" rel="stylesheet">
@endforeach
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>{!! \App\Services\TiendaEstiloService::cssVariables($datosEstilo) !!}</style>
