@props(['selected' => 'last_7_days'])

<div {{ $attributes->merge(['class' => 'inline-block']) }}>
    <select class="px-4 py-2 bg-[#1e293b] border border-slate-600 rounded-lg text-white text-sm focus:outline-none focus:border-[#c8ff00] cursor-pointer">
        <option value="last_7_days" {{ $selected === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
        <option value="last_30_days" {{ $selected === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
        <option value="this_month" {{ $selected === 'this_month' ? 'selected' : '' }}>This Month</option>
        <option value="last_month" {{ $selected === 'last_month' ? 'selected' : '' }}>Last Month</option>
        <option value="this_year" {{ $selected === 'this_year' ? 'selected' : '' }}>This Year</option>
        <option value="custom" {{ $selected === 'custom' ? 'selected' : '' }}>Custom Range</option>
    </select>
</div>
