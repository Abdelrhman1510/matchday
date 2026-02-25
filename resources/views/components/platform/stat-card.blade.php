@props(['title', 'value', 'change' => null, 'changeDirection' => 'up', 'icon', 'color' => 'blue'])

@php
$colorClasses = [
    'blue' => 'bg-blue-500/10 text-blue-500',
    'green' => 'bg-green-500/10 text-green-500',
    'yellow' => 'bg-yellow-500/10 text-yellow-500',
    'red' => 'bg-red-500/10 text-red-500',
    'neon' => 'bg-[#c8ff00]/10 text-[#c8ff00]',
];
@endphp

<div {{ $attributes->merge(['class' => 'bg-[#1e293b] border border-slate-700 rounded-xl p-6']) }}>
    <div class="flex items-start justify-between mb-4">
        <div class="p-3 {{ $colorClasses[$color] }} rounded-lg">
            {{ $icon }}
        </div>
        @if($change)
            <div class="flex items-center gap-1 text-sm {{ $changeDirection === 'up' ? 'text-green-500' : 'text-red-500' }}">
                @if($changeDirection === 'up')
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                @endif
                <span class="font-semibold">{{ $change }}%</span>
            </div>
        @endif
    </div>
    <div class="space-y-1">
        <p class="text-3xl font-bold text-white">{{ $value }}</p>
        <p class="text-sm text-slate-400 uppercase tracking-wider">{{ $title }}</p>
    </div>
    @if(isset($miniChart))
        <div class="mt-4">
            {{ $miniChart }}
        </div>
    @endif
</div>
