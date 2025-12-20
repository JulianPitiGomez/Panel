import './bootstrap';
import collapse from '@alpinejs/collapse';

// Livewire ya incluye Alpine.js, solo necesitamos registrar plugins adicionales
// antes de que Alpine se inicialice
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
});
