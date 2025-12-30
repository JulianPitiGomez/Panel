import './bootstrap';
import collapse from '@alpinejs/collapse';

// Livewire ya incluye Alpine.js, solo necesitamos registrar plugins adicionales
// antes de que Alpine se inicialice
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
});

// Manejo personalizado de sesión expirada en Livewire
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            // 419 = Token CSRF expirado (sesión expirada)
            // 401 = No autorizado
            if (status === 419 || status === 401) {
                preventDefault();

                // Detectar módulo actual desde la URL
                let module = 'panel';
                const path = window.location.pathname;

                if (path.includes('/mozos')) {
                    module = 'mozos';
                } else if (path.includes('/vendedores-empresas')) {
                    module = 'vendedores_empresas';
                } else if (path.includes('/vendedores')) {
                    module = 'vendedores';
                } else if (path.includes('/monitor')) {
                    module = 'monitor';
                } else if (path.includes('/panel')) {
                    module = 'panel';
                }

                // Redirigir a nuestra página personalizada
                window.location.href = `/session-expired?module=${module}`;
            }
        });
    });
});
