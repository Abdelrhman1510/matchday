<div class="animate-pulse space-y-6">
    <!-- Header Skeleton -->
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

    <!-- Stat Cards Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @for($i = 0; $i < 3; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-40 flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div class="w-10 h-10 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
                    <div class="h-4 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-10 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                    <div class="h-3 w-32 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Most Watched Matches Skeleton -->
    <div class="mt-8">
        <div class="flex items-center justify-between mb-6">
            <div class="h-7 w-64 bg-[#1e164e] rounded animate-shimmer"></div>
            <div class="h-5 w-24 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @for($i = 0; $i < 3; $i++)
                <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden h-72">
                    <div class="h-48 bg-[#1a0e40] animate-shimmer"></div>
                    <div class="p-4 space-y-4">
                        <div class="h-4 w-full bg-[#1a0e40] rounded animate-shimmer"></div>
                        <div class="flex justify-between">
                            <div class="h-8 w-16 bg-[#1a0e40] rounded animate-shimmer"></div>
                            <div class="h-8 w-16 bg-[#1a0e40] rounded animate-shimmer"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <!-- Charts Row Skeleton -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @for($i = 0; $i < 2; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-[400px]">
                <div class="flex items-center justify-between mb-6">
                    <div class="space-y-2">
                        <div class="h-5 w-48 bg-[#1a0e40] rounded animate-shimmer"></div>
                        <div class="h-3 w-64 bg-[#1a0e40] rounded animate-shimmer"></div>
                    </div>
                    <div class="h-9 w-9 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
                </div>
                <div class="h-64 bg-[#1a0e40] rounded animate-shimmer"></div>
            </div>
        @endfor
    </div>
</div>