<div class="animate-pulse space-y-6">
    <!-- Header Skeleton -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col items-center lg:items-start">
            <div class="h-10 w-48 sm:w-64 bg-[#1e164e] rounded-lg animate-shimmer mb-2"></div>
            <div class="h-4 w-32 sm:w-48 bg-[#1e164e] rounded animate-shimmer"></div>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-3">
            <div class="h-10 w-10 bg-[#1e164e] rounded-lg animate-shimmer"></div>
            <div class="h-10 w-10 bg-[#1e164e] rounded-full animate-shimmer"></div>
        </div>
    </div>

    <!-- Commission Structure Skeleton -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="space-y-2">
                <div class="h-5 w-48 bg-[#1a0e40] rounded animate-shimmer"></div>
                <div class="h-3 w-64 bg-[#1a0e40] rounded animate-shimmer"></div>
            </div>
            <div class="w-9 h-9 bg-[#1a0e40] rounded-xl animate-shimmer"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            @for($i = 0; $i < 3; $i++)
                <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-4 h-24 flex flex-col justify-between">
                    <div class="h-2 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                    <div class="h-8 w-16 bg-[#1a0e40] rounded animate-shimmer"></div>
                </div>
            @endfor
        </div>
        <div class="space-y-4">
            <div class="h-1.5 w-full bg-[#1a0e40] rounded-lg animate-shimmer"></div>
            <div class="h-16 w-full bg-[#0a0524] border border-[#1e164e] rounded-lg animate-shimmer"></div>
        </div>
    </div>

    <!-- Subscription Plans Skeleton -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
        <div class="h-5 w-48 bg-[#1a0e40] rounded animate-shimmer mb-6"></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @for($i = 0; $i < 3; $i++)
                <div class="bg-[#0a0524] border border-[#1e164e] rounded-xl p-5 h-64 space-y-4 flex flex-col">
                    <div class="flex justify-between">
                        <div class="h-6 w-24 bg-[#1a0e40] rounded animate-shimmer"></div>
                        <div class="h-4 w-12 bg-[#1a0e40] rounded animate-shimmer"></div>
                    </div>
                    <div class="h-8 w-20 bg-[#1a0e40] rounded animate-shimmer"></div>
                    <div class="flex-1 space-y-2">
                        @for($j = 0; $j < 3; $j++)
                        <div class="h-3 w-full bg-[#1a0e40] rounded animate-shimmer"></div> @endfor
                    </div>
                    <div class="h-10 w-full bg-[#1a0e40] rounded-lg animate-shimmer"></div>
                </div>
            @endfor
        </div>
    </div>
</div>