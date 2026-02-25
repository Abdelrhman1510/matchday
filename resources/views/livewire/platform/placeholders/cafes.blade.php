<div class="animate-pulse">
    <!-- Header Skeleton -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col items-center lg:items-start">
            <div class="h-10 w-48 sm:w-64 bg-[#1e164e] rounded-lg animate-shimmer mb-2"></div>
            <div class="h-4 w-32 sm:w-48 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
    </div>

    <!-- Stat Cards Row Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @for($i = 0; $i < 4; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-32">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-[#1a0e40] rounded-xl h-12 w-12 animate-shimmer"></div>
                    <div class="h-4 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-8 w-16 bg-[#1a0e40] rounded animate-shimmer"></div>
                    <div class="h-3 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Filters Row Skeleton -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="h-10 bg-[#0a0524] border border-[#1e164e] rounded-lg animate-shimmer"></div>
            <div class="h-10 bg-[#0a0524] border border-[#1e164e] rounded-lg animate-shimmer"></div>
            <div class="h-10 bg-[#0a0524] border border-[#1e164e] rounded-lg animate-shimmer"></div>
            <div class="lg:col-span-2 h-10 bg-[#0a0524] border border-[#1e164e] rounded-lg animate-shimmer"></div>
        </div>
    </div>

    <!-- Cafes Grid Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @for($i = 0; $i < 6; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden h-[450px]">
                <div class="h-48 bg-[#1a0e40] animate-shimmer"></div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
                        <div class="space-y-2">
                            <div class="h-5 w-32 bg-[#1a0e40] rounded animate-shimmer"></div>
                            <div class="h-3 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-6 pb-6 border-b border-[#1e164e]">
                        <div class="space-y-2">
                            <div class="h-3 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                            <div class="h-6 w-10 bg-[#1a0e40] rounded animate-shimmer"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-3 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                            <div class="h-6 w-10 bg-[#1a0e40] rounded animate-shimmer"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-3 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                            <div class="h-6 w-10 bg-[#1a0e40] rounded animate-shimmer"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-10 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                        <div class="w-10 h-10 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                        <div class="w-10 h-10 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>