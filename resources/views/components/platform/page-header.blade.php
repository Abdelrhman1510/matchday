@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-8 flex items-center justify-between']) }}>
    <div>
        <h1 class="text-3xl font-bold text-white uppercase tracking-wide mb-2">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex items-center gap-4">
            {{ $actions }}
        </div>
    @endif
</div>
