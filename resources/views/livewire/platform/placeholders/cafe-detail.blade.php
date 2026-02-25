<div class="animate-pulse space-y-6">
    <!-- Hero Section Skeleton -->
    <div class="relative rounded-xl overflow-hidden">
        <div class="h-52 bg-[#1e164e] animate-shimmer rounded-t-xl"></div>
        <div class="bg-[#0e0735] border border-[#1e164e] border-t-0 rounded-b-xl px-4 sm:px-6 py-5">
            <div class="flex flex-col lg:flex-row lg:items-end gap-4 sm:gap-5">
                <div
                    class="w-20 h-20 rounded-xl bg-[#1a0e40] animate-shimmer -mt-12 sm:-mt-10 relative z-10 shadow-xl mx-auto lg:mx-0">
                </div>
                <div class="flex-1 space-y-2 text-center lg:text-left">
                    <div class="h-8 w-48 bg-[#1a0e40] rounded animate-shimmer mx-auto lg:mx-0"></div>
                    <div class="h-4 w-64 bg-[#1a0e40] rounded animate-shimmer mx-auto lg:mx-0"></div>
                </div>
                <div class="flex items-center justify-center lg:justify-start gap-2">
                    <div class="h-10 w-24 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                    <div class="h-10 w-32 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview Skeleton -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @for($i = 0; $i < 4; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-5 h-32 flex flex-col justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                    <div class="h-3 w-20 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
                <div class="h-8 w-16 bg-[#1a0e40] rounded animate-shimmer"></div>
                <div class="h-3 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
            </div>
        @endfor
    </div>

    <!-- Charts Row Skeleton -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @for($i = 0; $i < 2; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 h-[350px]">
                <div class="flex items-center justify-between mb-5">
                    <div class="space-y-2">
                        <div class="h-4 w-32 bg-[#1a0e40] rounded animate-shimmer"></div>
                        <div class="h-3 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                    </div>
                    <div class="w-8 h-8 bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                </div>
                <div class="h-56 bg-[#1a0e40] rounded animate-shimmer"></div>
            </div>
        @endfor
    </div>

    <!-- Branches Section Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @for($i = 0; $i < 3; $i++)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden h-64">
                <div class="h-36 bg-[#1a0e40] animate-shimmer"></div>
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <div class="h-2 w-16 bg-[#1a0e40] rounded"></div>
                            <div class="h-6 w-12 bg-[#1a0e40] rounded"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-2 w-16 bg-[#1a0e40] rounded"></div>
                            <div class="h-6 w-12 bg-[#1a0e40] rounded"></div>
                        </div>
                    </div>
                    <div class="h-3 w-full bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
            </div>
        @endfor
    </div>
</div>