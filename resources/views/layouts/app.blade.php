<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{asset('images/logo.ico')}}" rel="shortcut icon" />

    <title>{{ config('app.name', 'Panel') }}</title>

    <!-- Meta Tags para compartir en redes sociales -->
    <meta property="og:title" content="Panel Web - bncsoft - Sistemas de Gestion para Pymes y Gastronómicos">
    <meta property="og:description" content="Sistema integral de gestión para restaurantes, empresas y vendedores. Panel de control, gestión de pedidos, stock, mozos y más.">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    <!-- Meta Tags para Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Panel Web - bncsoft - Sistemas de Gestion para Pymes y Gastronómicos">
    <meta name="twitter:description" content="Sistema integral de gestión para restaurantes, empresas y vendedores.">
    <meta name="twitter:image" content="{{ asset('images/logo.png') }}">

    <!-- Meta Description para SEO -->
    <meta name="description" content="Panel Web - bncsoft - Sistemas de Gestion para Pymes y Gastronómicos. Sistema integral de gestión para restaurantes, empresas y vendedores."

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&family=Source+Sans+Pro:wght@200;300;400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .font-primary { font-family: 'Montserrat', sans-serif; }
        .font-secondary { font-family: 'Source Sans Pro', sans-serif; }
        .btn-primary {
            background-color: #FFAF22;
            color: white;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(255, 175, 34, 0.2);
        }
        .btn-primary:hover {
            background-color: #E69A1E;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(255, 175, 34, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(255, 175, 34, 0.2);
        }
    </style>
</head>
<body class="font-secondary antialiased bg-gray-50">
    <div class="min-h-screen">
        @yield('content')        
        @if (isset($slot))
            {{ $slot }}
        @endif
    </div>

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        /* ===== Inicio: Ventas y Compras por Día ===== */
        function inicioChartVentas() {
            return {
                chart: null,
                init() {
                    const src = document.getElementById('inicioVentasData');
                    if (src) {
                        new MutationObserver(() => this.render()).observe(src, { attributes: true, attributeFilter: ['data-ventas','data-compras'] });
                    }
                },
                render() {
                    this.$nextTick(() => {
                        if (this.chart) this.chart.destroy();
                        const ctx = this.$refs.chartVentasCompras;
                        const src = document.getElementById('inicioVentasData');
                        if (!ctx || !src) return;
                        const ventasPorDia = JSON.parse(src.getAttribute('data-ventas') || '[]');
                        const comprasPorDia = JSON.parse(src.getAttribute('data-compras') || '[]');
                        const todasFechas = [...new Set([...ventasPorDia.map(v=>v.fecha),...comprasPorDia.map(c=>c.fecha)])].sort();
                        if (todasFechas.length === 0) return;
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: todasFechas,
                                datasets: [
                                    { label:'Ventas', data:todasFechas.map(f=>{const i=ventasPorDia.find(v=>v.fecha===f);return i?i.total:0;}), backgroundColor:'rgba(34,197,94,0.8)', borderColor:'rgb(34,197,94)', borderWidth:1 },
                                    { label:'Compras', data:todasFechas.map(f=>{const i=comprasPorDia.find(c=>c.fecha===f);return i?i.total:0;}), backgroundColor:'rgba(239,68,68,0.8)', borderColor:'rgb(239,68,68)', borderWidth:1 }
                                ]
                            },
                            options: {
                                responsive:true, maintainAspectRatio:false,
                                plugins:{ legend:{display:true,position:'top'}, tooltip:{mode:'index',intersect:false,callbacks:{label:function(c){return (c.dataset.label||'')+': $'+c.parsed.y.toLocaleString();}}} },
                                scales:{ y:{beginAtZero:true,ticks:{callback:function(v){return '$'+v.toLocaleString();}}} }
                            }
                        });
                    });
                }
            };
        }

        /* ===== Inicio: Departamentos ===== */
        function inicioChartDeptos() {
            return {
                chart: null,
                init() {
                    const src = document.getElementById('inicioDeptosData');
                    if (src) {
                        new MutationObserver(() => this.render()).observe(src, { attributes: true, attributeFilter: ['data-deptos'] });
                    }
                },
                render() {
                    this.$nextTick(() => {
                        if (this.chart) this.chart.destroy();
                        const ctx = this.$refs.chartDeptos;
                        const src = document.getElementById('inicioDeptosData');
                        if (!ctx || !src) return;
                        const departamentos = JSON.parse(src.getAttribute('data-deptos') || '[]');
                        if (!departamentos || departamentos.length === 0) return;
                        this.chart = new Chart(ctx, {
                            type:'bar',
                            data:{ labels:departamentos.map(d=>d.departamento), datasets:[{label:'Ventas por Departamento',data:departamentos.map(d=>d.total),backgroundColor:'rgba(147,51,234,0.8)',borderColor:'rgb(147,51,234)',borderWidth:1}] },
                            options:{ indexAxis:'y',responsive:true,maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return '$'+c.parsed.x.toLocaleString();}}}}, scales:{x:{beginAtZero:true,ticks:{callback:function(v){return '$'+v.toLocaleString();}}}} }
                        });
                    });
                }
            };
        }

        /* ===== Ventas Fechas: Clientes + Día Semana ===== */
        function ventasFechasCharts() {
            return {
                visible: false, dataHash: '', chartClientes: null, chartDiaSemana: null,
                init() {
                    const source = document.getElementById('ventasChartsDataSource');
                    if (source) {
                        this.dataHash = source.getAttribute('data-clientes');
                        new MutationObserver(() => { this.dataHash = source.getAttribute('data-clientes')+'_'+Date.now(); }).observe(source, { attributes: true, attributeFilter: ['data-clientes','data-diasemana'] });
                    }
                },
                toggle() { this.visible = !this.visible; if (this.visible) this.$nextTick(() => this.renderCharts()); },
                readData() {
                    const s = document.getElementById('ventasChartsDataSource');
                    if (!s) return { clientes: [], diaSemana: [] };
                    return { clientes: JSON.parse(s.getAttribute('data-clientes')||'[]'), diaSemana: JSON.parse(s.getAttribute('data-diasemana')||'[]') };
                },
                renderCharts() {
                    const { clientes: dc, diaSemana: dds } = this.readData();
                    const colores = ['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6','#EC4899','#06B6D4','#84CC16','#F97316','#6366F1'];
                    if (this.chartClientes) { this.chartClientes.destroy(); this.chartClientes = null; }
                    if (this.chartDiaSemana) { this.chartDiaSemana.destroy(); this.chartDiaSemana = null; }

                    const ctxC = this.$refs.chartClientes;
                    if (ctxC && dc && dc.length > 0) {
                        this.chartClientes = new Chart(ctxC, {
                            type: 'bar',
                            data: { labels: dc.map(d=>d.cliente), datasets: [{ label:'Total Vendido', data: dc.map(d=>d.total), backgroundColor: colores, borderColor: colores.map(c=>c+'CC'), borderWidth:1, borderRadius:4 }] },
                            options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins: { legend:{display:false}, tooltip:{ callbacks:{ label:function(ctx){ return '$'+ctx.parsed.x.toLocaleString('es-AR',{minimumFractionDigits:2})+' ('+dc[ctx.dataIndex].cantidad+' fact.)'; } } } }, scales: { x:{ticks:{callback:function(v){return '$'+v.toLocaleString('es-AR');}}}, y:{ticks:{font:{size:11}}} } }
                        });
                    }
                    const ctxD = this.$refs.chartDiaSemana;
                    if (ctxD && dds && dds.length > 0) {
                        this.chartDiaSemana = new Chart(ctxD, {
                            type: 'bar',
                            data: { labels: dds.map(d=>d.dia), datasets: [
                                { label:'Total Vendido', data:dds.map(d=>d.total), backgroundColor:'#3B82F680', borderColor:'#3B82F6', borderWidth:2, borderRadius:6 },
                                { label:'Cant. Facturas', data:dds.map(d=>d.cantidad), type:'line', borderColor:'#F59E0B', backgroundColor:'#F59E0B33', pointBackgroundColor:'#F59E0B', pointRadius:5, tension:0.3, yAxisID:'y1' }
                            ] },
                            options: { responsive:true, maintainAspectRatio:false, plugins:{ tooltip:{ callbacks:{ label:function(ctx){ if(ctx.dataset.label==='Total Vendido') return '$'+ctx.parsed.y.toLocaleString('es-AR',{minimumFractionDigits:2}); return ctx.parsed.y+' facturas'; } } } }, scales:{ y:{beginAtZero:true,ticks:{callback:function(v){return '$'+v.toLocaleString('es-AR');}}}, y1:{position:'right',beginAtZero:true,grid:{drawOnChartArea:false},ticks:{stepSize:1,callback:function(v){return v+' fact.';}}} } }
                        });
                    }
                }
            };
        }

        /* ===== Estado de Resultados: Solapa Gráficos ===== */
        function estadoResultadoCharts() {
            return {
                chartResumen: null,
                chartConciliacion: null,
                chartVentasComp: null,
                chartComprasComp: null,
                chartEgresosComp: null,
                chartCobros: null,
                chartPagos: null,
                chartCuentas: null,
                chartGastosCuentas: null,
                init() {
                    const src = document.getElementById('estadoResultadoChartsData');
                    if (src) {
                        new MutationObserver(() => this.render()).observe(src, {
                            attributes: true,
                            attributeFilter: ['data-resumen','data-conciliacion','data-cobros','data-pagos','data-cuentas','data-gastoscuentas','data-ventascomp','data-comprascomp','data-egresoscomp']
                        });
                    }
                },
                readData() {
                    const s = document.getElementById('estadoResultadoChartsData');
                    if (!s) return null;
                    return {
                        resumen: JSON.parse(s.getAttribute('data-resumen') || '{}'),
                        conciliacion: JSON.parse(s.getAttribute('data-conciliacion') || '[]'),
                        cobros: JSON.parse(s.getAttribute('data-cobros') || '[]'),
                        pagos: JSON.parse(s.getAttribute('data-pagos') || '[]'),
                        cuentas: JSON.parse(s.getAttribute('data-cuentas') || '[]'),
                        gastosCuentas: JSON.parse(s.getAttribute('data-gastoscuentas') || '[]'),
                        ventasComp: JSON.parse(s.getAttribute('data-ventascomp') || '{}'),
                        comprasComp: JSON.parse(s.getAttribute('data-comprascomp') || '{}'),
                        egresosComp: JSON.parse(s.getAttribute('data-egresoscomp') || '{}'),
                    };
                },
                destroyAll() {
                    ['chartResumen','chartConciliacion','chartVentasComp','chartComprasComp','chartEgresosComp','chartCobros','chartPagos','chartCuentas','chartGastosCuentas']
                        .forEach(k => { if (this[k]) { this[k].destroy(); this[k] = null; } });
                },
                render() {
                    this.$nextTick(() => {
                        const d = this.readData();
                        if (!d) return;
                        this.destroyAll();

                        const colores = ['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6','#EC4899','#06B6D4','#84CC16','#F97316','#6366F1'];
                        const fmt = v => '$' + Number(v).toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2});

                        // Resumen del período
                        const ctxR = this.$refs.chartResumen;
                        if (ctxR) {
                            const labels = ['Result. bruto','Result. operativo','Saldo IVA','Flujo neto'];
                            const vals = [d.resumen.resultadoBruto||0, d.resumen.resultadoOperativo||0, d.resumen.saldoIva||0, d.resumen.flujoNeto||0];
                            const bg = vals.map(v => v >= 0 ? 'rgba(34,197,94,0.75)' : 'rgba(239,68,68,0.75)');
                            const bd = vals.map(v => v >= 0 ? 'rgb(34,197,94)' : 'rgb(239,68,68)');
                            this.chartResumen = new Chart(ctxR, {
                                type: 'bar',
                                data: { labels, datasets: [{ label:'Importe', data: vals, backgroundColor: bg, borderColor: bd, borderWidth:1, borderRadius:4 }] },
                                options: {
                                    responsive:true, maintainAspectRatio:false,
                                    plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c => fmt(c.parsed.y) } } },
                                    scales: { y:{ ticks:{ callback: v => fmt(v) } } }
                                }
                            });
                        }

                        // Devengado vs Percibido
                        const ctxC = this.$refs.chartConciliacion;
                        if (ctxC && d.conciliacion.length > 0) {
                            this.chartConciliacion = new Chart(ctxC, {
                                type: 'bar',
                                data: {
                                    labels: d.conciliacion.map(x => x.concepto),
                                    datasets: [
                                        { label:'Devengado', data: d.conciliacion.map(x=>x.devengado), backgroundColor:'rgba(59,130,246,0.8)', borderColor:'rgb(59,130,246)', borderWidth:1, borderRadius:4 },
                                        { label:'Percibido', data: d.conciliacion.map(x=>x.percibido), backgroundColor:'rgba(245,158,11,0.8)', borderColor:'rgb(245,158,11)', borderWidth:1, borderRadius:4 },
                                    ]
                                },
                                options: {
                                    responsive:true, maintainAspectRatio:false,
                                    plugins: { legend:{position:'top'}, tooltip:{ callbacks:{ label: c => (c.dataset.label||'')+': '+fmt(c.parsed.y) } } },
                                    scales: { y:{ beginAtZero:true, ticks:{ callback: v => fmt(v) } } }
                                }
                            });
                        }

                        // Composición de ventas (fiscal vs no fiscal)
                        const ctxVC = this.$refs.chartVentasComp;
                        if (ctxVC) {
                            const f = d.ventasComp.fiscal || 0;
                            const nf = d.ventasComp.no_fiscal || 0;
                            if (f > 0 || nf > 0) {
                                this.chartVentasComp = new Chart(ctxVC, {
                                    type: 'doughnut',
                                    data: { labels: ['Fiscales (con CAE)','No fiscales'], datasets: [{ data: [f, nf], backgroundColor: ['#3B82F6','#93C5FD'], borderWidth: 2 }] },
                                    options: { responsive:true, maintainAspectRatio:false, plugins: { legend:{position:'bottom'}, tooltip:{ callbacks:{ label: c => c.label+': '+fmt(c.parsed) } } } }
                                });
                            }
                        }

                        // Composición de compras (computables vs no)
                        const ctxCC = this.$refs.chartComprasComp;
                        if (ctxCC) {
                            const f = d.comprasComp.fiscal || 0;
                            const nf = d.comprasComp.no_fiscal || 0;
                            if (f > 0 || nf > 0) {
                                this.chartComprasComp = new Chart(ctxCC, {
                                    type: 'doughnut',
                                    data: { labels: ['Computables IVA','No fiscales'], datasets: [{ data: [f, nf], backgroundColor: ['#F97316','#FDBA74'], borderWidth: 2 }] },
                                    options: { responsive:true, maintainAspectRatio:false, plugins: { legend:{position:'bottom'}, tooltip:{ callbacks:{ label: c => c.label+': '+fmt(c.parsed) } } } }
                                });
                            }
                        }

                        // Compras vs Gastos (según tipo de cuenta contable)
                        const ctxEC = this.$refs.chartEgresosComp;
                        if (ctxEC) {
                            const co = d.egresosComp.compras || 0;
                            const ga = d.egresosComp.gastos || 0;
                            if (co !== 0 || ga !== 0) {
                                this.chartEgresosComp = new Chart(ctxEC, {
                                    type: 'doughnut',
                                    data: { labels: ['Compras (mercadería)','Gastos'], datasets: [{ data: [co, ga], backgroundColor: ['#F97316','#8B5CF6'], borderWidth: 2 }] },
                                    options: { responsive:true, maintainAspectRatio:false, plugins: { legend:{position:'bottom'}, tooltip:{ callbacks:{ label: c => c.label+': '+fmt(c.parsed) } } } }
                                });
                            }
                        }

                        // Cobros por forma de pago
                        const ctxP1 = this.$refs.chartCobros;
                        if (ctxP1 && d.cobros.length > 0) {
                            this.chartCobros = new Chart(ctxP1, {
                                type: 'doughnut',
                                data: { labels: d.cobros.map(x=>x.nombre), datasets: [{ data: d.cobros.map(x=>x.total), backgroundColor: colores, borderWidth: 2 }] },
                                options: { responsive:true, maintainAspectRatio:false, plugins: { legend:{position:'right',labels:{font:{size:11}}}, tooltip:{ callbacks:{ label: c => c.label+': '+fmt(c.parsed) } } } }
                            });
                        }

                        // Pagos por forma de pago
                        const ctxP2 = this.$refs.chartPagos;
                        if (ctxP2 && d.pagos.length > 0) {
                            this.chartPagos = new Chart(ctxP2, {
                                type: 'doughnut',
                                data: { labels: d.pagos.map(x=>x.nombre), datasets: [{ data: d.pagos.map(x=>x.total), backgroundColor: colores, borderWidth: 2 }] },
                                options: { responsive:true, maintainAspectRatio:false, plugins: { legend:{position:'right',labels:{font:{size:11}}}, tooltip:{ callbacks:{ label: c => c.label+': '+fmt(c.parsed) } } } }
                            });
                        }

                        // Top compras / gastos por cuenta contable (horizontal)
                        const barrasPorCuenta = (ctx, filas) => new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: filas.map(x => x.nombre),
                                datasets: [{ label:'Importe', data: filas.map(x=>x.importe), backgroundColor: colores, borderWidth: 1, borderRadius: 4 }]
                            },
                            options: {
                                indexAxis:'y', responsive:true, maintainAspectRatio:false,
                                plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c => fmt(c.parsed.x) } } },
                                scales: { x:{ ticks:{ callback: v => fmt(v) } }, y:{ ticks:{ font:{size:11} } } }
                            }
                        });

                        const ctxCu = this.$refs.chartCuentas;
                        if (ctxCu && d.cuentas.length > 0) {
                            this.chartCuentas = barrasPorCuenta(ctxCu, d.cuentas);
                        }

                        const ctxGCu = this.$refs.chartGastosCuentas;
                        if (ctxGCu && d.gastosCuentas.length > 0) {
                            this.chartGastosCuentas = barrasPorCuenta(ctxGCu, d.gastosCuentas);
                        }
                    });
                }
            };
        }
    </script>
    @stack('scripts')
</body>
</html>