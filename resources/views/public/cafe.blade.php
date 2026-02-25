<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cafe->name }} - Matchday</title>
    <meta name="description" content="{{ $cafe->description }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="text-2xl font-bold text-blue-600">
                    <a href="/">Matchday</a>
                </div>
                <div class="flex gap-4">
                    <a href="/api/v1/cafes" class="text-gray-600 hover:text-gray-900">Browse Cafes</a>
                    <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Download App</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative h-96 bg-gradient-to-r from-blue-600 to-blue-800">
        @if($cafe->logo)
            <img src="{{ $cafe->logo }}" alt="{{ $cafe->name }}" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-50">
        @endif
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center text-white">
                @if($cafe->logo)
                    <img src="{{ $cafe->logo }}" alt="{{ $cafe->name }}" class="h-32 w-32 mx-auto mb-4 rounded-full shadow-lg border-4 border-white">
                @endif
                <h1 class="text-5xl font-bold mb-4">{{ $cafe->name }}</h1>
                <p class="text-xl text-blue-100 max-w-2xl mx-auto px-4">{{ $cafe->description }}</p>
                <div class="flex items-center justify-center gap-2 mt-4">
                    @if($cafe->avg_rating)
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <span class="font-semibold">{{ number_format($cafe->avg_rating, 1) }}</span>
                        </div>
                        <span class="text-blue-200">•</span>
                    @endif
                    @if($cafe->total_reviews)
                        <span>{{ $cafe->total_reviews }} reviews</span>
                    @endif
                    @if($cafe->city)
                        <span class="text-blue-200">•</span>
                        <span><i class="fas fa-map-marker-alt"></i> {{ $cafe->city }}</span>
                    @endif
                </div>
                @if($cafe->is_premium)
                    <span class="inline-block mt-4 bg-yellow-500 text-gray-900 px-4 py-2 rounded-full font-semibold">
                        <i class="fas fa-crown"></i> Premium Cafe
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Main Info -->
            <div class="lg:col-span-2 space-y-8">
                <!-- About -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-4">About</h2>
                    <p class="text-gray-700 leading-relaxed">{{ $cafe->description }}</p>
                    @if($cafe->phone)
                        <div class="mt-4 flex items-center gap-2">
                            <i class="fas fa-phone text-blue-600"></i>
                            <a href="tel:{{ $cafe->phone }}" class="text-blue-600 hover:underline">{{ $cafe->phone }}</a>
                        </div>
                    @endif
                </div>

                <!-- Branches -->
                @if($cafe->branches && $cafe->branches->count() > 0)
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-2xl font-bold mb-6">
                            <i class="fas fa-map-marker-alt text-blue-600"></i>
                            Branches ({{ $cafe->branches_count ?? $cafe->branches->count() }})
                        </h2>
                        <div class="space-y-6">
                            @foreach($cafe->branches as $branch)
                                <div class="border-l-4 border-blue-600 pl-4 py-2">
                                    <h3 class="font-bold text-lg">{{ $branch->name }}</h3>
                                    <p class="text-gray-600 mt-1">
                                        <i class="fas fa-location-dot"></i> {{ $branch->address }}
                                    </p>
                                    @if($branch->total_seats)
                                        <p class="text-sm text-gray-500 mt-2">
                                            <i class="fas fa-chair"></i> {{ $branch->total_seats }} seats available
                                        </p>
                                    @endif
                                    @if($branch->hours && $branch->hours->count() > 0)
                                        <div class="mt-3">
                                            <p class="text-sm font-semibold text-gray-700 mb-2">
                                                <i class="fas fa-clock"></i> Opening Hours
                                            </p>
                                            <div class="grid grid-cols-2 gap-2 text-sm">
                                                @foreach($branch->hours as $hour)
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 capitalize">{{ $hour->day_name }}:</span>
                                                        <span class="font-medium {{ $hour->is_open ? 'text-green-600' : 'text-red-600' }}">
                                                            @if($hour->is_open)
                                                                {{ \Carbon\Carbon::parse($hour->open_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($hour->close_time)->format('g:i A') }}
                                                            @else
                                                                Closed
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if($branch->latitude && $branch->longitude)
                                        <a href="https://www.google.com/maps?q={{ $branch->latitude }},{{ $branch->longitude }}" 
                                           target="_blank"
                                           class="inline-block mt-3 text-blue-600 hover:underline text-sm">
                                            <i class="fas fa-directions"></i> Get Directions
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Column - Sidebar -->
            <div class="space-y-6">
                <!-- Quick Info Card -->
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-4">
                    <h3 class="font-bold text-lg mb-4">Quick Info</h3>
                    <div class="space-y-3">
                        @if($cafe->city)
                            <div class="flex items-start gap-3">
                                <i class="fas fa-city text-blue-600 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Location</p>
                                    <p class="font-medium">{{ $cafe->city }}</p>
                                </div>
                            </div>
                        @endif
                        @if($cafe->avg_rating)
                            <div class="flex items-start gap-3">
                                <i class="fas fa-star text-yellow-400 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Rating</p>
                                    <p class="font-medium">{{ number_format($cafe->avg_rating, 1) }} / 5.0</p>
                                </div>
                            </div>
                        @endif
                        @if($cafe->subscription_plan)
                            <div class="flex items-start gap-3">
                                <i class="fas fa-gem text-blue-600 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Plan</p>
                                    <p class="font-medium capitalize">{{ $cafe->subscription_plan }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- CTA Buttons -->
                    <div class="mt-6 space-y-3">
                        <a href="#" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-mobile-screen"></i> Book via App
                        </a>
                        @if($cafe->phone)
                            <a href="tel:{{ $cafe->phone }}" class="block w-full border-2 border-blue-600 text-blue-600 text-center py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
                                <i class="fas fa-phone"></i> Call Now
                            </a>
                        @endif
                        <button onclick="shareThisPage()" class="block w-full border-2 border-gray-300 text-gray-700 text-center py-3 rounded-lg font-semibold hover:bg-gray-50 transition">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>
                </div>

                <!-- Download App Promo -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl shadow-md p-6 text-white">
                    <h3 class="font-bold text-lg mb-2">Get the Matchday App</h3>
                    <p class="text-sm text-blue-100 mb-4">Book your spot, earn rewards, and never miss a match!</p>
                    <div class="flex gap-2">
                        <a href="#" class="flex-1 bg-white text-blue-600 text-center py-2 rounded-lg font-semibold text-sm hover:bg-blue-50 transition">
                            <i class="fab fa-apple"></i> iOS
                        </a>
                        <a href="#" class="flex-1 bg-white text-blue-600 text-center py-2 rounded-lg font-semibold text-sm hover:bg-blue-50 transition">
                            <i class="fab fa-google-play"></i> Android
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">Matchday</h3>
                    <p class="text-sm">Your ultimate sports cafe booking platform</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Browse Cafes</a></li>
                        <li><a href="#" class="hover:text-white">Upcoming Matches</a></li>
                        <li><a href="#" class="hover:text-white">About Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Help Center</a></li>
                        <li><a href="#" class="hover:text-white">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white">FAQs</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                <p>&copy; {{ date('Y') }} Matchday. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function shareThisPage() {
            if (navigator.share) {
                navigator.share({
                    title: '{{ $cafe->name }}',
                    text: '{{ $cafe->description }}',
                    url: window.location.href
                }).catch(console.error);
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        }
    </script>
</body>
</html>
