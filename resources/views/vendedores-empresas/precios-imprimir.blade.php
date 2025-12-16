<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Precios</title>
    <style>
        @media print {
            @page {
                margin: 0.5in;
                size: A4;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 2px solid #222036;
        }

        .header h1 {
            font-size: 24px;
            color: #222036;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .header .subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .header .filters {
            font-size: 11px;
            color: #888;
            margin-top: 10px;
        }

        .header .date {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }

        .table-container {
            width: 100%;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .table-header {
            background-color: #222036;
            color: white;
        }

        .table-header th {
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            border: 1px solid #333;
        }

        .table-header th.text-center {
            text-align: center;
        }

        .table-header th.text-right {
            text-align: right;
        }

        tbody tr {
            border-bottom: 1px solid #ddd;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f0f0f0;
        }

        td {
            padding: 6px 4px;
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

        .categoria {
            font-size: 10px;
            color: #666;
        }

        .precio {
            text-align: right;
            font-weight: bold;
        }

        .precio-venta {
            color: #2563eb;
            font-size: 12px;
        }

        .precio-reventa {
            color: #7c3aed;
            font-size: 10px;
        }

        .precio-especial {
            color: #dc2626;
            font-size: 11px;
            font-weight: bold;
        }

        .stock {
            text-align: center;
            font-weight: 500;
        }

        .stock-alto {
            color: #16a34a;
        }

        .stock-medio {
            color: #ea580c;
        }

        .stock-bajo {
            color: #dc2626;
        }

        .fecha {
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .summary {
            margin-top: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .summary h3 {
            color: #222036;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #222036;
            display: block;
        }

        .summary-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        .no-products {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
        }

        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        
        /* Botón de impresión */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #222036;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }

        .print-button:hover {
            background: #333;
        }
    </style>
</head>
<body>
    <!-- Botón de impresión -->
    <button onclick="window.print()" class="print-button no-print">
        🖨️ Imprimir
    </button>

    <!-- Header -->
    <div class="header">
        <h1>Lista de Precios</h1>
        <div class="subtitle">{{ session('cliente_nombre') }}</div>
        

            <div class="filters">
                <strong>Filtros aplicados:</strong>
                @if(!empty($busqueda))
                    Búsqueda: "{{ $busqueda }}" •
                @endif                
                @if(!empty($depto))
                    Depto: {{ $deptoNombre }} •
                @endif                
                @if(!empty($listaEspecial))
                    Lista Especial: {{ $listaNombre }} •
                @endif                
            </div>

        
        <div class="date">
            Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="table-container">
         @if(count($productos))
            <table>
                <thead class="table-header">
                    <tr>
                        <th style="width: 8%">Código</th>
                        <th style="width: 35%">Producto</th>
                        <th style="width: 20%" class="text-right">Precios</th>
                        <th style="width: 8%" class="text-center">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos as $producto)
                        <tr>
                            <!-- Código -->
                            <td>
                                <span class="codigo">{{ $producto->CODIGO }}</span>
                            </td>
                            
                            <!-- Producto -->
                            <td>
                                <div class="nombre">{{ $producto->NOMBRE }}</div>
                            </td>                                                        
                            
                            <!-- Precios -->
                            <td class="precio">
                                <div class="precio-venta">${{ number_format($producto->PRECIO, 2) }}</div>
                                @if(!empty($listaEspecial) && isset($preciosEspeciales[$producto->CODIGO]))
                                    <div class="precio-especial">
                                        Especial: ${{ number_format($preciosEspeciales[$producto->CODIGO], 2) }}
                                    </div>
                                @endif
                            </td>
                            
                            <!-- Stock -->
                            <td class="stock">
                                @php
                                    $stock = $producto->STOCK;
                                    $stockClass = 'stock-alto';
                                    if ($stock <= 0) $stockClass = 'stock-bajo';
                                    elseif ($stock <= 5) $stockClass = 'stock-medio';
                                @endphp
                                <span class="{{ $stockClass }}">{{ number_format($stock, 2) }}</span>
                                @if($stock <= 0)
                                    <br><small style="color: #dc2626;">Sin stock</small>
                                @elseif($stock <= 5)
                                    <br><small style="color: #ea580c;">Stock bajo</small>
                                @endif
                            </td>
                            
                            <!-- Fecha -->                            
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-products">
                No se encontraron productos con los filtros aplicados.
            </div>
        @endif
    </div>

    <!-- Resumen -->
    @if(count($productos))
        <div class="summary">
            <h3>Resumen de la Lista</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-value">{{ number_format($productos->count()) }}</span>
                    <span class="summary-label">Productos</span>
                </div>
                <div class="summary-item">
                    <span class="summary-value">${{ number_format($productos->avg('PRECIO'), 2) }}</span>
                    <span class="summary-label">Precio Promedio</span>
                </div>
                <div class="summary-item">
                    <span class="summary-value">{{ number_format($productos->sum('STOCK'), 2) }}</span>
                    <span class="summary-label">Stock Total</span>
                </div>
                <div class="summary-item">
                    <span class="summary-value">{{ $productos->where('STOCK', '<=', 0)->count() }}</span>
                    <span class="summary-label">Sin Stock</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Lista de Precios - Página generada automáticamente</p>
        <p>{{ url()->current() }}</p>
    </div>

    <script>
        // Auto-print cuando se carga la página
        window.onload = function() {
            // Pequeña pausa para asegurar que todo se cargue
            setTimeout(function() {
                window.print();
            }, 500);
        }

        // Cerrar ventana después de imprimir
        window.onafterprint = function() {
            window.close();
        }
    </script>
</body>
</html>