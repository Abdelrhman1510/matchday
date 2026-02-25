@props(['title', 'subtitle' => null, 'chartId', 'type' => 'line'])

<div {{ $attributes->merge(['class' => 'bg-[#1e293b] border border-slate-700 rounded-xl p-6']) }}>
    <div class="mb-6">
        <h3 class="text-lg font-bold text-white uppercase tracking-wide">{{ $title }}</h3>
        @if($subtitle)
            <p class="text-sm text-slate-400 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    
    <div class="relative" style="height: 300px;">
        <canvas id="{{ $chartId }}"></canvas>
    </div>
    
    @if(isset($footer))
        <div class="mt-4 pt-4 border-t border-slate-700">
            {{ $footer }}
        </div>
    @endif
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js configuration for dark theme with neon accent
            Chart.defaults.color = '#94a3b8'; // slate-400
            Chart.defaults.borderColor = '#334155'; // slate-700
        });
    </script>
    @endpush
@endonce
