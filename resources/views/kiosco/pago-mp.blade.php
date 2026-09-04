{{-- PAGO MERCADO PAGO VIEW --}}
<div class="h-full flex flex-col overflow-y-auto" id="main"
     x-data="{
        segundos: 300,
        intervaloTimer: null,
        intervaloPoll: null,
        pagoAprobado: false,
        pagoRechazado: false,
        qrError: false,
        regenerando: false,
        tipoIntegracion: @js($mpTipoIntegracion),
        esMobile: /Android|iPhone|iPad|iPod/i.test(navigator.userAgent),

        init() {
            if (this.tipoIntegracion === 'checkout_pro') {
                this.cargarQR();
            }
            this.iniciarTimers();

            Livewire.on('qr-regenerado', () => {
                this.regenerando = false;
                this.qrError = false;
                this.segundos = 300;
                this.iniciarTimers();
                if (this.tipoIntegracion === 'checkout_pro') {
                    this.$nextTick(() => this.cargarQR());
                }
            });

            Livewire.on('qr-error', () => {
                this.regenerando = false;
                this.qrError = true;
            });
        },

        iniciarTimers() {
            this.detener();
            this.intervaloTimer = setInterval(() => {
                this.segundos--;
                if (this.segundos <= 0) {
                    this.timeout();
                }
            }, 1000);

            this.intervaloPoll = setInterval(() => {
                this.verificarPago();
            }, 4000);
        },

        cargarQR() {
            if (this.esMobile) return;
            const initPoint = @this.get('mpInitPoint');
            if (!initPoint) {
                this.qrError = true;
                return;
            }
            if (typeof qrcode !== 'undefined') {
                this.generarQR(initPoint);
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js';
            script.onload = () => this.generarQR(initPoint);
            script.onerror = () => { this.qrError = true; };
            document.head.appendChild(script);
        },

        generarQR(url) {
            const container = document.getElementById('qrcode');
            if (!container) return;
            try {
                const qr = qrcode(0, 'M');
                qr.addData(url);
                qr.make();
                container.innerHTML = qr.createSvgTag(5, 0);
                this.qrError = false;
            } catch (e) {
                console.error('Error generando QR:', e);
                this.qrError = true;
            }
        },

        regenerar() {
            this.regenerando = true;
            this.qrError = false;
            $wire.call('regenerarQR');
        },

        get minutosRestantes() {
            return String(Math.floor(this.segundos / 60)).padStart(2, '0');
        },
        get segundosRestantes() {
            return String(this.segundos % 60).padStart(2, '0');
        },

        async verificarPago() {
            try {
                const ref = @this.get('mpExternalReference');
                const clientId = @this.get('mpClientId');
                if (!ref || !clientId) return;

                if (this.tipoIntegracion === 'point') {
                    // Point: consultar estado del payment intent
                    const deviceId = @this.get('mpPointDeviceId');
                    const intentId = @this.get('mpPaymentIntentId');
                    if (!deviceId || !intentId) return;
                    const response = await fetch('/mp/point-status/' + clientId + '/' + encodeURIComponent(deviceId) + '/' + encodeURIComponent(intentId));
                    const data = await response.json();

                    if (data.state === 'FINISHED' || data.payment_status === 'approved') {
                        this.pagoAprobado = true;
                        this.detener();
                        $wire.call('verificarPagoMP');
                    } else if (data.state === 'CANCELED' || data.state === 'ERROR' || data.state === 'EXPIRED') {
                        this.pagoRechazado = true;
                        this.detener();
                    }
                } else {
                    // QR Presencial y Checkout Pro: consultar por external_reference
                    const response = await fetch('/mp/status/' + clientId + '/' + encodeURIComponent(ref));
                    const data = await response.json();
                    if (data.status === 'approved') {
                        this.pagoAprobado = true;
                        this.detener();
                        $wire.call('verificarPagoMP');
                    } else if (data.status === 'rejected' || data.status === 'cancelled') {
                        this.pagoRechazado = true;
                        this.detener();
                    }
                }
            } catch (e) {
                console.error('Error polling:', e);
            }
        },

        timeout() {
            this.detener();
            $wire.call('cancelarPagoMP');
        },
        detener() {
            clearInterval(this.intervaloTimer);
            clearInterval(this.intervaloPoll);
        },
        destroy() {
            this.detener();
        }
     }">

    <!-- Header compacto -->
    <div class="bg-gradient-to-r from-[#009ee3] to-[#00b1ea] px-6 py-3">
        <h1 class="text-xl font-bold text-white flex items-center">
            @if($mpTipoIntegracion === 'point')
                <i class="fas fa-credit-card mr-2"></i>Pago con Lector Point
            @else
                <i class="fas fa-qrcode mr-2"></i>Pago con Mercado Pago
            @endif
        </h1>
    </div>

    <div class="flex-1 flex items-center justify-center p-4">
        <div class="max-w-md w-full text-center">

            {{-- Estado: Esperando pago --}}
            <template x-if="!pagoAprobado && !pagoRechazado">
                <div>
                    <!-- Total -->
                    <div class="bg-gray-50 rounded-xl p-3 mb-4">
                        <p class="text-gray-500 text-xs mb-1">Total a pagar</p>
                        <p class="text-3xl font-bold text-gray-800">${{ number_format($this->getCartTotal(), 2, ',', '.') }}</p>
                    </div>

                    @if($mpTipoIntegracion === 'qr_presencial')
                        {{-- ===== QR PRESENCIAL ===== --}}
                        <div class="mb-4">
                            @if($mpPosQrImage)
                                <div class="bg-white rounded-xl shadow-md p-4 inline-block">
                                    <img src="{{ $mpPosQrImage }}" alt="QR Mercado Pago" class="w-48 h-48 mx-auto object-contain">
                                </div>
                                <p class="text-gray-600 mt-2 text-sm">
                                    <i class="fas fa-mobile-alt mr-1 text-[#009ee3]"></i>
                                    Escaneá el QR con la app de Mercado Pago
                                </p>
                            @else
                                <div class="bg-white rounded-xl shadow-md p-6 inline-block">
                                    <i class="fas fa-exclamation-triangle text-amber-500 text-3xl mb-2"></i>
                                    <p class="text-gray-600 text-sm">QR no disponible</p>
                                </div>
                            @endif
                        </div>

                    @elseif($mpTipoIntegracion === 'point')
                        {{-- ===== POINT / LECTOR ===== --}}
                        <div class="mb-4">
                            <div class="bg-white rounded-xl shadow-md p-6 inline-block">
                                <div class="w-24 h-24 mx-auto mb-3 relative">
                                    {{-- Icono de lector animado --}}
                                    <div class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-900 rounded-2xl flex items-center justify-center">
                                        <i class="fas fa-wifi text-white text-3xl animate-pulse"></i>
                                    </div>
                                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full animate-ping"></div>
                                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full"></div>
                                </div>
                                <p class="text-lg font-bold text-gray-800">Acercá tu tarjeta o celular</p>
                                <p class="text-sm text-gray-500 mt-1">al lector Point</p>
                            </div>
                        </div>

                    @else
                        {{-- ===== CHECKOUT PRO ===== --}}
                        {{-- QR (desktop/tablet) --}}
                        <div x-show="!esMobile" class="mb-4">
                            <div class="bg-white rounded-xl shadow-md p-4 inline-block">
                                {{-- QR cargando --}}
                                <div x-show="!qrError && !regenerando" id="qrcode" class="flex items-center justify-center" style="min-width: 180px; min-height: 180px;">
                                    <div class="animate-spin w-6 h-6 border-2 border-[#009ee3] border-t-transparent rounded-full"></div>
                                </div>

                                {{-- QR regenerando --}}
                                <div x-show="regenerando" class="flex flex-col items-center justify-center" style="min-width: 180px; min-height: 180px;">
                                    <div class="animate-spin w-8 h-8 border-3 border-[#009ee3] border-t-transparent rounded-full mb-2"></div>
                                    <p class="text-gray-500 text-xs">Generando nuevo QR...</p>
                                </div>

                                {{-- QR error --}}
                                <div x-show="qrError && !regenerando" class="flex flex-col items-center justify-center" style="min-width: 180px; min-height: 180px;">
                                    <i class="fas fa-exclamation-triangle text-amber-500 text-3xl mb-2"></i>
                                    <p class="text-gray-600 text-sm mb-3">No se pudo cargar el QR</p>
                                    <button @click="regenerar()"
                                            class="bg-[#009ee3] hover:bg-[#007eb5] text-white font-bold py-2 px-4 rounded-lg text-sm transition-all">
                                        <i class="fas fa-sync-alt mr-1"></i> Reintentar
                                    </button>
                                </div>
                            </div>
                            <p x-show="!qrError && !regenerando" class="text-gray-600 mt-2 text-sm">
                                <i class="fas fa-mobile-alt mr-1 text-[#009ee3]"></i>
                                Escaneá el QR con la app de Mercado Pago
                            </p>
                        </div>

                        {{-- Botón (móvil) --}}
                        <div x-show="esMobile" class="mb-4">
                            <a href="{{ $mpInitPoint }}"
                               target="_blank"
                               class="inline-flex items-center gap-2 bg-[#009ee3] hover:bg-[#007eb5] text-white font-bold py-3 px-6 rounded-xl text-lg transition-all shadow-lg">
                                <i class="fas fa-external-link-alt"></i>
                                Pagar con Mercado Pago
                            </a>
                        </div>
                    @endif

                    <!-- Esperando + Timer -->
                    <div class="flex items-center justify-center gap-2 text-gray-500 mb-3">
                        <div class="animate-spin w-4 h-4 border-2 border-[#009ee3] border-t-transparent rounded-full"></div>
                        <span class="text-xs">
                            @if($mpTipoIntegracion === 'point')
                                Esperando pago en el lector...
                            @else
                                Esperando confirmación del pago...
                            @endif
                        </span>
                    </div>

                    <div class="bg-blue-50 rounded-lg p-2 mb-4">
                        <p class="text-blue-700 text-xs">
                            <i class="fas fa-clock mr-1"></i>
                            Tiempo restante: <span class="font-bold font-mono" x-text="minutosRestantes + ':' + segundosRestantes"></span>
                        </p>
                    </div>

                    @if($mpTipoIntegracion !== 'point')
                        <!-- Regenerar QR -->
                        <button @click="regenerar()" x-show="!regenerando"
                                class="text-[#009ee3] hover:text-[#007eb5] text-xs font-medium transition mb-2">
                            <i class="fas fa-sync-alt mr-1"></i> Regenerar QR
                        </button>
                        <br>
                    @endif

                    <!-- Cancelar -->
                    <button wire:click="cancelarPagoMP" @click="detener()"
                            class="text-gray-400 hover:text-gray-600 text-xs transition">
                        <i class="fas fa-arrow-left mr-1"></i> Cancelar y volver
                    </button>

                    {{-- Botón de prueba (QUITAR EN PRODUCCIÓN) --}}
                    @if(config('app.debug'))
                    <div class="mt-4 pt-3 border-t border-dashed border-gray-300">
                        <button wire:click="simularPagoAprobado" @click="pagoAprobado = true; detener()"
                                class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-lg transition">
                            <i class="fas fa-flask mr-1"></i> Simular pago aprobado
                        </button>
                    </div>
                    @endif
                </div>
            </template>

            {{-- Estado: Pago aprobado --}}
            <template x-if="pagoAprobado">
                <div>
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-green-500 text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-1">Pago aprobado</h2>
                    <p class="text-gray-500">Procesando tu pedido...</p>
                    <div class="mt-3">
                        <div class="animate-spin w-6 h-6 border-2 border-green-500 border-t-transparent rounded-full mx-auto"></div>
                    </div>
                </div>
            </template>

            {{-- Estado: Pago rechazado --}}
            <template x-if="pagoRechazado">
                <div>
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-times text-red-500 text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-1">Pago no aprobado</h2>
                    <p class="text-gray-500 mb-4">El pago fue rechazado o cancelado.</p>
                    <button wire:click="cancelarPagoMP" @click="detener()"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-xl transition">
                        <i class="fas fa-arrow-left mr-2"></i> Volver
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
