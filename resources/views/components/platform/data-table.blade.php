@props(['headers', 'rows', 'searchable' => false, 'filterable' => false])

<div {{ $attributes->merge(['class' => 'bg-[#1e293b] border border-slate-700 rounded-xl overflow-hidden']) }}>
    @if($searchable || $filterable)
        <div class="p-4 border-b border-slate-700 flex items-center gap-4">
            @if($searchable)
                <input 
                    type="text" 
                    placeholder="Search..."
                    class="flex-1 px-4 py-2 bg-[#0f172a] border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-[#c8ff00]"
                >
            @endif
            @if($filterable)
                {{ $filters ?? '' }}
            @endif
        </div>
    @endif
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#0c1425]">
                <tr>
                    @foreach($headers as $header)
                        <th class="px-6 py-4 text-left text-xs font-bold text-[#c8ff00] uppercase tracking-wider">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
