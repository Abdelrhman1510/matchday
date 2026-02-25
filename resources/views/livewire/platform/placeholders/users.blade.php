<div class="animate-pulse space-y-6">
    <!-- Header Skeleton -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col items-center lg:items-start">
            <div class="h-10 w-48 sm:w-64 bg-[#1e164e] rounded-lg animate-shimmer mb-2"></div>
            <div class="h-4 w-32 sm:w-48 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-3">
            <div class="h-10 w-28 sm:w-32 bg-[#1e164e] rounded-lg animate-shimmer"></div>
            <div class="h-10 w-28 sm:w-32 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
    </div>

    <!-- Stat Cards Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @for($i = 0; $i < 3; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-48 flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div class="w-10 h-10 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
                    <div class="h-4 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div>
                    <div class="h-3 w-32 bg-[#1a0e40] rounded animate-shimmer mb-2"></div>
                    <div class="h-10 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div class="flex items-end gap-[3px] h-10 mt-4">
                    @for($j = 0; $j < 12; $j++)
                        <div class="flex-1 bg-[#1a0e40] rounded-sm" style="height: {{ rand(20, 100) }}%;"></div>
                    @endfor
                </div>
            </div>
        @endfor
    </div>

    <!-- Charts Row Skeleton -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @for($i = 0; $i < 2; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-[400px]">
                <div class="flex items-center justify-between mb-6">
                    <div class="space-y-2">
                        <div class="h-5 w-48 bg-[#1a0e40] rounded animate-shimmer"></div>
                        <div class="h-3 w-32 bg-[#1a0e40] rounded animate-shimmer"></div>
                    </div>
                    <div class="h-9 w-9 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
                </div>
                <div class="h-64 bg-[#1a0e40] rounded animate-shimmer"></div>
            </div>
        @endfor
    </div>

    <!-- Fan Segments Skeleton -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @for($i = 0; $i < 2; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden h-[450px]">
                <div class="h-52 bg-[#1a0e40] animate-shimmer"></div>
                <div class="p-5 space-y-6">
                    <div class="flex justify-between">
                        <div class="space-y-2">
                            <div class="h-3 w-16 bg-[#1a0e40] rounded"></div>
                            <div class="h-8 w-24 bg-[#1a0e40] rounded"></div>
                        </div>
                        <div class="space-y-2 text-right">
                            <div class="h-3 w-16 bg-[#1a0e40] rounded"></div>
                            <div class="h-8 w-12 bg-[#1a0e40] rounded ml-auto"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="h-3 w-full bg-[#1a0e40] rounded"></div>
                        <div class="h-3 w-full bg-[#1a0e40] rounded"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>