{{-- resources/views/pedidos/imprimir.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pedido #{{ $pedido->id }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .info-box {
            width: 48%;
        }
        
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-box p {
            margin: 3px 0;
            font-size: 11px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 11px;
        }
        
        td {
            font-size: 10px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        
        .total-final {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 12px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            clear: both;
        }
        
        .observaciones {
            margin: 20px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #333;
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>PEDIDO DE VENTA</h1>
        <p><strong>Número:</strong> {{ $pedido->id ?? $pedido->id }}</p>
        <p><strong>Fecha:</strong> {{ $pedido->fecha }}</p>
        <p><strong>Para Fecha:</strong> {{ $pedido->parafecha }}</p>
    </div>
    
    <!-- Información del pedido -->
    <div class="info-section">
        <!-- Información del cliente -->
        <div class="info-box">
            <h3>DATOS DEL CLIENTE</h3>
            <p><strong>Cliente:</strong> {{ $pedido->cliente ?? 'Cliente no especificado' }}</p>            
        </div>
        
        <!-- Información del vendedor -->
        <div class="info-box">
            <h3>DATOS DEL VENDEDOR</h3>
            <p><strong>Vendedor:</strong> {{ $pedido->vendedor ?? 'Vendedor no especificado' }}</p>
            <p><strong>Código:</strong> {{ $pedido->codven ?? '-' }}</p>
        </div>
    </div>
    
    <!-- Detalles del pedido -->
    <div class="table-container">
        <h3>DETALLE DE PRODUCTOS</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%">Código</th>
                    <th style="width: 40%">Descripción</th>
                    <th style="width: 10%" class="text-center">Cantidad</th>
                    <th style="width: 15%" class="text-right">Precio Unit.</th>
                    <th style="width: 10%" class="text-right">Desc. %</th>
                    <th style="width: 15%" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach($detalles as $detalle)
                    @php
                        $subtotal = $detalle->cantidad * $detalle->punitario ;
                        $total += $subtotal;
                    @endphp
                    <tr>
                        <td>{{ $detalle->codart }}</td>
                        <td>{{ $detalle->detart }}</td>
                        <td class="text-center">{{ number_format($detalle->cantidad, 2) }}</td>
                        <td class="text-right">${{ number_format($detalle->punitario, 2) }}</td>
                        <td class="text-right">{{ number_format($detalle->descup ?? 0, 2) }}%</td>
                        <td class="text-right">${{ number_format($subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Totales -->
    <div class="totals">
        <table>
            <tr class="total-final">
                <td><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>${{ number_format($total, 2) }}</strong></td>
            </tr>
        </table>
    </div>
    
    <!-- Observaciones -->
    @if($pedido->observa)
        <div class="observaciones">
            <h4>OBSERVACIONES:</h4>
            <p>{{ $pedido->observa }}</p>
        </div>
    @endif
    
    <!-- Footer -->
    <div class="footer">
        <p>Documento generado el {{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>