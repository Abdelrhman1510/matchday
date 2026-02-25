<div class="animate-pulse">
    <!-- Page Header Skeleton -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col items-center lg:items-start">
            <div class="h-10 w-48 sm:w-64 bg-[#1e164e] rounded-lg animate-shimmer mb-2"></div>
            <div class="h-4 w-32 sm:w-48 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-3 sm:gap-4">
            <div class="h-10 w-28 sm:w-32 bg-[#1e164e] rounded animate-shimmer"></div>
            <div class="h-10 w-28 sm:w-32 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
    </div>

    <!-- Stat Cards Row Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @for($i = 0; $i < 4; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-36 flex flex-col justify-between">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-[#1a0e40] rounded-xl h-12 w-12 animate-shimmer"></div>
                    <div class="h-4 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-3 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                    <div class="h-8 w-16 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Charts Row Skeleton -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        @for($i = 0; $i < 2; $i++)
            <div class="bg-[#1e293b] border border-[#1e164e] rounded-xl p-6 h-[350px]">
                <div class="flex items-center justify-between mb-6">
                    <div class="h-6 w-48 bg-[#1a0e40] rounded animate-shimmer"></div>
                    <div class="flex gap-2">
                        <div class="h-6 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                        <div class="h-6 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                    </div>
                </div>
                <div class="h-48 w-full bg-[#1a0e40] rounded animate-shimmer mt-4"></div>
            </div>
        @endfor
    </div>

    <!-- Bottom Row Skeleton -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        @for($i = 0; $i < 3; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-[400px]">
                <div class="flex items-center gap-2 mb-6">
                    <div class="h-10 w-10 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
                    <div class="h-6 w-32 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div class="space-y-4">
                    @for($j = 0; $j < 5; $j++)
                        <div class="h-16 w-full bg-[#12082b] rounded-xl border border-[#1e164e]/50 animate-shimmer"></div>
                    @endfor
                </div>
            </div>
        @endfor
    </div>

    <!-- Recent Matches Table Skeleton -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden mt-6">
        <div class="p-6 border-b border-[#1e164e] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="h-7 w-48 bg-[#1a0e40] rounded animate-shimmer"></div>
            <div class="h-10 w-full sm:w-64 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
        </div>
        <div class="p-6 space-y-4">
            <div class="h-10 w-full bg-[#12082b] rounded animate-shimmer"></div>
            @for($i = 0; $i < 5; $i++)
                <div class="h-12 w-full bg-[#12082b] rounded animate-shimmer"></div>
            @endfor
        </div>
    </div>
</div>