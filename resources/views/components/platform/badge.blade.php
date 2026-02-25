@props(['text', 'color' => 'blue'])

@php
$colorClasses = [
    'blue' => 'bg-blue-500/10 text-blue-400',
    'green' => 'bg-green-500/10 text-green-400',
    'yellow' => 'bg-yellow-500/10 text-yellow-400',
    'red' => 'bg-red-500/10 text-red-400',
    'slate' => 'bg-slate-500/10 text-slate-400',
    'neon' => 'bg-[#c8ff00]/10 text-[#c8ff00]',
    'premium' => 'bg-[#c8ff00]/10 text-[#c8ff00]',
    'standard' => 'bg-blue-500/10 text-blue-400',
    'basic' => 'bg-slate-500/10 text-slate-400',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide ' . ($colorClasses[$color] ?? $colorClasses['blue'])]) }}>
    {{ $text }}
</span>
