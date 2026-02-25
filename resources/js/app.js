import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Chart from 'chart.js/auto';

// Make Alpine and Chart available globally
window.Alpine = Alpine;
window.Chart = Chart;

// Start Livewire (this also starts Alpine with all Livewire directives registered)
Livewire.start();
