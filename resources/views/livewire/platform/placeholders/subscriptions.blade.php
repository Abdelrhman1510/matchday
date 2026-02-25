<div class="animate-pulse space-y-6">
    <!-- Header Skeleton -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col items-center lg:items-start">
            <div class="h-10 w-48 sm:w-64 bg-[#1e164e] rounded-lg animate-shimmer mb-2"></div>
            <div class="h-4 w-32 sm:w-48 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-3">
            <div class="h-10 w-28 sm:w-32 bg-[#1e164e] rounded-lg animate-shimmer"></div>
            <div class="h-10 w-28 sm:w-32 bg-[#1e164e] rounded-lg animate-shimmer"></div>
        </div>
    </div>

    <!-- Stat Cards Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @for($i = 0; $i < 3; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-40 flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div class="w-10 h-10 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
                    <div class="h-4 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div>
                    <div class="h-10 w-24 bg-[#1a0e40] rounded animate-shimmer mb-2"></div>
                    <div class="h-3 w-32 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Revenue Trend Chart Skeleton -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-[400px]">
        <div class="flex items-center justify-between mb-6">
            <div class="space-y-2">
                <div class="h-5 w-48 bg-[#1a0e40] rounded animate-shimmer"></div>
                <div class="h-3 w-64 bg-[#1a0e40] rounded animate-shimmer"></div>
            </div>
            <div class="flex gap-2">
                <div class="h-8 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                <div class="h-8 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                <div class="h-8 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
            </div>
        </div>
        <div class="h-64 bg-[#1a0e40] rounded animate-shimmer"></div>
    </div>

    <!-- Subscription Plans Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @for($i = 0; $i < 3; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden h-[450px]">
                <div class="h-40 bg-[#1a0e40] animate-shimmer"></div>
                <div class="p-5 space-y-4">
                    <div class="flex justify-between items-center">
                        <div class="h-6 w-20 bg-[#1a0e40] rounded"></div>
                        <div class="h-8 w-16 bg-[#1a0e40] rounded"></div>
                    </div>
                    <div class="h-3 w-full bg-[#1a0e40] rounded"></div>
                    <div class="space-y-2">
                        @for($j = 0; $j < 4; $j++)
                            <div class="flex items-center gap-2">
                                <div class="h-4 w-4 bg-[#1a0e40] rounded-full"></div>
                                <div class="h-3 w-full bg-[#1a0e40] rounded"></div>
                            </div>
                        @endfor
                    </div>
                    <div class="pt-4 border-t border-[#1e164e] flex justify-between items-center">
                        <div class="space-y-1">
                            <div class="h-2 w-16 bg-[#1a0e40] rounded"></div>
                            <div class="h-6 w-10 bg-[#1a0e40] rounded"></div>
                        </div>
                        <div class="flex gap-2">
                            <div class="h-8 w-8 bg-[#1a0e40] rounded"></div>
                            <div class="h-8 w-8 bg-[#1a0e40] rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>