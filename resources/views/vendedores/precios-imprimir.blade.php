<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Precios</title>
    <style>
        @page {
            margin: 1.5cm 1cm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #222036;
        }

        .header h1 {
            font-size: 20px;
            color: #222036;
            margin-bottom: 4px;
        }

        .header .subtitle {
            font-size: 13px;
            color: #666;
            margin-bottom: 6px;
        }

        .header .filters {
            font-size: 10px;
            color: #555;
            margin-top: 6px;
        }

        .header .date {
            font-size: 10px;
            color: #888;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        thead.table-header {
            background-color: #222036;
            color: white;
            display: table-header-group;
        }

        thead.table-header th {
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            border: 1px solid #1a1830;
        }

        thead.table-header th.text-center { text-align: center; }
        thead.table-header th.text-right { text-align: right; }

        tbody tr {
            page-break-inside: avoid;
        }

        tbody tr.alt {
            background-color: #f5f5f5;
        }

        td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .codigo {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #222036;
        }

        .nombre {
            font-weight: 500;
            color: #333;
        }

        .precio { text-align: right; }
        .precio-venta { color: #2563eb; font-weight: bold; font-size: 11px; }
        .precio-reventa { color: #7c3aed; font-size: 9px; }
        .precio-especial { color: #dc2626; font-size: 10px; font-weight: bold; }

        .stock { text-align: center; }
        .stock-alto { color: #16a34a; }
        .stock-medio { color: #ea580c; }
        .stock-bajo { color: #dc2626; }

        .fecha { text-align: center; font-size: 9px; color: #666; }

        .summary {
            margin-top: 15px;
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .summary h3 {
            color: #222036;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .summary table {
            width: 100%;
            border: none;
        }

        .summary table td {
            border: none;
            text-align: center;
            padding: 4px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #222036;
            display: block;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }

        .no-products {
            text-align: center;
            padding: 30px;
            color: #888;
            font-style: italic;
        }

        /* Barra visible sólo en modo "imprimir" (vista HTML auto-print).
           Se oculta al imprimir para que no aparezca en el papel/PDF. */
        .print-bar {
            position: fixed; top: 0; left: 0; right: 0;
            background: #222036; color: white;
            padding: 10px 14px; font-size: 13px;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.25); z-index: 999;
        }
        .print-bar button {
            background: #FFAF22; color: #222036; border: 0;
            padding: 6px 12px; border-radius: 4px; font-weight: bold;
            cursor: pointer; font-size: 13px;
        }
        .print-bar .msg { flex: 1; }
        .stage-with-bar { padding-top: 48px; }

        @media print {
            .print-bar { display: none !important; }
            .stage-with-bar { padding-top: 0 !important; }
        }
    </style>
</head>
<body @if(!empty($autoImprimir)) class="stage-with-bar" @endif>
    @if(!empty($autoImprimir))
        <div class="print-bar">
            <span class="msg">🖨 Preparando impresión... Si no se abre el diálogo, tocá el botón.</span>
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    @endif
    <div class="header">
        <h1>Lista de Precios</h1>
        <div class="subtitle">{{ session('cliente_nombre') }}</div>

        @if(!empty($clienteNombre))
            <div class="filters" style="font-size:12px; color:#333; margin-top:8px;">
                <strong>Para:</strong> {{ $clienteNombre }}
            </div>
        @endif

        <div class="filters">
            @php
                $hayFiltros = !empty($busqueda) || !empty($rubroNombre) || !empty($marcaNombre)
                           || !empty($deptoNombre) || !empty($proveedorNombre) || !empty($listaNombre)
                           || !empty($fechaDesde) || !empty($fechaHasta);
            @endphp
            @if($hayFiltros)
                <strong>Filtros:</strong>
                @if(!empty($busqueda)) Búsqueda: "{{ $busqueda }}" • @endif
                @if(!empty($rubro)) Rubro: {{ $rubroNombre }} • @endif
                @if(!empty($marca)) Marca: {{ $marcaNombre }} • @endif
                @if(!empty($depto)) Depto: {{ $deptoNombre }} • @endif
                @if(!empty($proveedor)) Proveedor: {{ $proveedorNombre }} • @endif
                @if(!empty($listaEspecial)) Lista Especial: {{ $listaNombre }} • @endif
                @if(!empty($fechaDesde)) Desde: {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} • @endif
                @if(!empty($fechaHasta)) Hasta: {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }} @endif
            @endif
        </div>

        <div class="date">
            @if(!empty($fechaEmision))
                Fecha de emisión: <strong>{{ \Carbon\Carbon::parse($fechaEmision)->format('d/m/Y') }}</strong>
            @else
                Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
            @endif
            @if(!empty($fechaValidez))
                &nbsp;|&nbsp; Válida hasta: <strong>{{ \Carbon\Carbon::parse($fechaValidez)->format('d/m/Y') }}</strong>
            @endif
        </div>
    </div>

    @if(count($productos))
        <table>
            <thead class="table-header">
                <tr>
                    <th style="width: 10%">Código</th>
                    <th style="width: 42%">Producto</th>
                    <th style="width: 22%" class="text-right">Precios</th>
                    <th style="width: 12%" class="text-center">Stock</th>
                    <th style="width: 14%" class="text-center">Actualizado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $i => $producto)
                    <tr class="{{ $i % 2 ? 'alt' : '' }}">
                        <td><span class="codigo">{{ $producto->CODIGO }}</span></td>
                        <td><div class="nombre">{{ $producto->NOMBRE }}</div></td>
                        <td class="precio">
                            @if(!empty($mostrarLista1))
                                <div class="precio-venta">${{ number_format($producto->PRECIOVEN, 2) }}</div>
                            @endif
                            @if(!empty($mostrarLista2))
                                <div class="precio-reventa">Lista 2: ${{ number_format($producto->REVENTA, 2) }}</div>
                            @endif
                            @if(!empty($mostrarListaEspecial) && !empty($listaEspecial) && isset($preciosEspeciales[$producto->CODIGO]))
                                <div class="precio-especial">
                                    Especial: ${{ number_format($preciosEspeciales[$producto->CODIGO], 2) }}
                                </div>
                            @endif
                        </td>
                        <td class="stock">
                            @php
                                $stock = $producto->STOCKACT;
                                $stockClass = 'stock-alto';
                                if ($stock <= 0) $stockClass = 'stock-bajo';
                                elseif ($stock <= 5) $stockClass = 'stock-medio';
                            @endphp
                            <span class="{{ $stockClass }}">{{ number_format($stock, 2) }}</span>
                        </td>
                        <td class="fecha">
                            {{ \Carbon\Carbon::parse($producto->FECMOD)->format('d/m/Y') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(!empty($mostrarTotales))
            <div class="summary">
                <h3>Resumen de la Lista</h3>
                <table>
                    <tr>
                        <td>
                            <span class="summary-value">{{ number_format($productos->count()) }}</span>
                            <span class="summary-label">Productos</span>
                        </td>
                        <td>
                            <span class="summary-value">${{ number_format($productos->avg('PRECIOVEN'), 2) }}</span>
                            <span class="summary-label">Precio Promedio</span>
                        </td>
                        <td>
                            <span class="summary-value">{{ number_format($productos->sum('STOCKACT'), 2) }}</span>
                            <span class="summary-label">Stock Total</span>
                        </td>
                        <td>
                            <span class="summary-value">{{ $productos->where('STOCKACT', '<=', 0)->count() }}</span>
                            <span class="summary-label">Sin Stock</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    @else
        <div class="no-products">
            No se encontraron productos con los filtros aplicados.
        </div>
    @endif

    @if(!empty($autoImprimir))
        <script>
            // Llamar al diálogo de impresión al cargar. En móvil abre la
            // bandeja nativa con "Guardar como PDF" + impresoras disponibles.
            window.addEventListener('load', function() {
                setTimeout(function() { window.print(); }, 300);
            });
        </script>
    @endif
</body>
</html>
