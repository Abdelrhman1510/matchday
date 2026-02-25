<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\CafeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\SavedCafeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPlayerController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\API\V1\NotificationController;
use App\Http\Controllers\API\V1\HomeController;
use App\Http\Controllers\API\V1\ExploreController;
use App\Http\Controllers\API\V1\SearchController;
use App\Http\Controllers\API\V1\OfferController;
use App\Http\Controllers\CafeAdminController;
use App\Http\Controllers\SeatingAdminController;
use App\Http\Controllers\MatchAdminController;
use App\Http\Controllers\BookingAdminController;
use App\Http\Controllers\QrScanController;
use App\Http\Controllers\OccupancyController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AppConfigController;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Broadcasting auth for API token users (Sanctum)
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'data' => [
            'version' => config('app.api_version', 'v1'),
            'timestamp' => now()->toIso8601String(),
        ],
        'meta' => (object) [],
    ]);
})->name('api.health');

// Home Feed - Works both with and without auth
Route::middleware(['throttle:api'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/feed', [HomeController::class, 'feed'])->name('home.feed');
    Route::get('/home/trending', [HomeController::class, 'trending'])->name('home.trending');
});

// Explore - Public (enhanced if authenticated)
Route::middleware(['throttle:api'])->group(function () {
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
});

// Global Search - Public
Route::middleware(['throttle:api'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search');
});

// Offers - Public
Route::middleware(['throttle:api'])->prefix('offers')->name('offers.')->group(function () {
    Route::get('/', [OfferController::class, 'index'])->name('index');
    Route::get('/{id}', [OfferController::class, 'show'])->name('show');
});

// FAQs - Public
Route::middleware(['throttle:api'])->prefix('faqs')->group(function () {
    Route::get('/', [FaqController::class, 'index'])->name('faqs.index');
});

// Legal Pages - Public
Route::middleware(['throttle:api'])->prefix('pages')->group(function () {
    Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
});

// App Config - Public
Route::middleware(['throttle:api'])->prefix('app')->group(function () {
    Route::get('/version', [AppConfigController::class, 'version'])->name('app.version');
    Route::get('/config', [AppConfigController::class, 'config'])->name('app.config');
    Route::get('/currencies', [AppConfigController::class, 'currencies'])->name('app.currencies');
});

// Support - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('support')->name('support.')->group(function () {
    Route::post('/contact', [SupportController::class, 'contact'])->name('contact');
    Route::post('/report-issue', [SupportController::class, 'reportIssue'])->name('report-issue');
    Route::get('/my-tickets', [SupportController::class, 'myTickets'])->name('my-tickets');
});

// Public Teams API - 30 requests per minute
Route::middleware(['throttle:api'])->prefix('teams')->name('teams.')->group(function () {
    Route::get('/popular', [TeamController::class, 'popular'])->name('popular');
    Route::get('/search', [TeamController::class, 'search'])->name('search');
    Route::get('/{id}', [TeamController::class, 'show'])->name('show');
    Route::get('/', [TeamController::class, 'index'])->name('index');
});

// Cafes API - Mixed public and authenticated
Route::prefix('cafes')->name('cafes.')->group(function () {
    // Specific routes first (to avoid being caught by /{id})

    // Public specific routes
    Route::middleware(['throttle:api'])->group(function () {
        Route::get('/search', [CafeController::class, 'search'])->name('search');
    });

    // Nearby routes (public - no auth required)
    Route::middleware(['throttle:api'])->group(function () {
        Route::get('/nearby', [CafeController::class, 'nearby'])->name('nearby');
    });

    // Public generic routes (after specific routes)
    Route::middleware(['throttle:api'])->group(function () {
        Route::get('/{id}/branches', [CafeController::class, 'branches'])->name('branches');
        Route::get('/{id}', [CafeController::class, 'show'])->name('show');
        Route::get('/', [CafeController::class, 'index'])->name('index');
    });
});

// Public Branches API - 30 requests per minute
Route::middleware(['throttle:api'])->prefix('branches')->name('branches.')->group(function () {
    Route::get('/{id}/matches', [BranchController::class, 'matches'])->name('matches');
    Route::get('/{id}/reviews', [BranchController::class, 'reviews'])->name('reviews');
    Route::get('/{id}', [BranchController::class, 'show'])->name('show');
});

// Branch Reviews - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('branches')->name('branches.')->group(function () {
    Route::post('/{id}/reviews', [BranchController::class, 'createReview'])->name('create-review');
});

// Public Matches API - 30 requests per minute
Route::middleware(['throttle:api'])->prefix('matches')->name('matches.')->group(function () {
    Route::get('/live', [MatchController::class, 'live'])->name('live');
    Route::get('/upcoming', [MatchController::class, 'upcoming'])->name('upcoming');
    Route::get('/popular', [MatchController::class, 'popular'])->name('popular');

    // Saved matches - auth required, must be before /{id} wildcard
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/saved', [MatchController::class, 'saved'])->name('saved');
        Route::post('/{id}/save', [MatchController::class, 'toggleSave'])->name('toggle-save');
    });

    Route::get('/{id}/seating', [MatchController::class, 'seating'])->name('seating');
    Route::get('/{id}', [MatchController::class, 'show'])->name('show');
    Route::get('/', [MatchController::class, 'index'])->name('index');
});

// Bookings API - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('bookings')->name('bookings.')->group(function () {
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/{id}', [BookingController::class, 'show'])->name('show');
    Route::put('/{id}', [BookingController::class, 'update'])->name('update');
    Route::post('/{id}/cancel', [BookingController::class, 'cancel'])->name('cancel');
    Route::put('/{id}/cancel', [BookingController::class, 'cancel'])->name('cancel.put');
    Route::get('/{id}/pass', [BookingController::class, 'pass'])->name('pass');
    Route::post('/{id}/share', [BookingController::class, 'share'])->name('share');
    Route::post('/{id}/add-to-calendar', [BookingController::class, 'addToCalendar'])->name('add-to-calendar');

    // Booking Players
    Route::get('/{id}/players', [BookingPlayerController::class, 'index'])->name('players.index');
    Route::post('/{id}/players', [BookingPlayerController::class, 'store'])->name('players.store');
    Route::delete('/{id}/players/{playerId}', [BookingPlayerController::class, 'destroy'])->name('players.destroy');

    // Payment for booking
    Route::post('/{id}/payment', [PaymentController::class, 'processBookingPayment'])->name('payment');

    // Rebook match
    Route::post('/{id}/rebook', [BookingController::class, 'rebook'])->name('rebook');

    // Enter fan room
    Route::post('/{id}/enter-fan-room', [BookingController::class, 'enterFanRoom'])->name('enter-fan-room');
});

// Payment Methods API - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('payment-methods')->name('payment-methods.')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
    Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
    Route::put('/{id}', [PaymentMethodController::class, 'update'])->name('update');
    Route::delete('/{id}', [PaymentMethodController::class, 'destroy'])->name('destroy');
    Route::put('/{id}/set-primary', [PaymentMethodController::class, 'setPrimary'])->name('set-primary');
});

// Payments API - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('payments')->name('payments.')->group(function () {
    Route::post('/process', [PaymentController::class, 'process'])->name('process');
    Route::get('/history', [PaymentController::class, 'history'])->name('history');
    Route::post('/{id}/refund', [PaymentController::class, 'refund'])->name('refund');
});

// Saved Cafes - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('saved-cafes')->name('saved-cafes.')->group(function () {
    Route::get('/', [SavedCafeController::class, 'index'])->name('index');
    Route::post('/{cafeId}', [SavedCafeController::class, 'store'])->name('store');
    Route::delete('/{cafeId}', [SavedCafeController::class, 'destroy'])->name('destroy');
});

// Saved Cafes alternative routes (tests use these patterns)
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/cafes/{cafeId}/save', [SavedCafeController::class, 'store']);
    Route::delete('/cafes/{cafeId}/unsave', [SavedCafeController::class, 'destroy']);
    Route::get('/profile/saved-cafes', [SavedCafeController::class, 'index']);
});

// Guest auth routes - 5 requests per minute for login/register
Route::middleware(['throttle:auth'])->prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register/cafe-owner', [AuthController::class, 'registerCafeOwner'])->name('register.cafe-owner');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login/google', [AuthController::class, 'loginWithGoogle'])->name('login.google');
    Route::post('/login/apple', [AuthController::class, 'loginWithApple'])->name('login.apple');
    Route::post('/staff/accept-invite/{token}', [AuthController::class, 'acceptStaffInvite'])->name('staff.accept-invite');
});

// Password reset routes - 3 requests per minute
Route::middleware(['throttle:password-reset'])->prefix('auth')->name('auth.')->group(function () {
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
});

// Authenticated routes - 60 requests per minute
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Other authenticated routes
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => $request->user(),
            'meta' => (object) [],
        ]);
    })->name('api.user');

    require __DIR__ . '/api/v1/routes.php';
});

// Auth endpoints - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');
    Route::post('/resend-verification', [AuthController::class, 'resendVerificationOtp'])
        ->middleware('throttle:resend-otp')
        ->name('auth.resend-verification');
});

// Profile endpoints - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
    // File upload with stricter rate limit
    Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
        ->middleware('throttle:uploads')
        ->name('profile.avatar');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/change-password', [ProfileController::class, 'updatePassword'])->name('profile.change-password');
    Route::put('/locale', [ProfileController::class, 'updateLocale'])->name('profile.locale');
    Route::put('/device-token', [ProfileController::class, 'updateDeviceToken'])->name('profile.device-token');
    Route::put('/favorite-team', [ProfileController::class, 'updateFavoriteTeam'])->name('profile.favorite-team');
    Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/activity', [ProfileController::class, 'activity'])->name('profile.activity');
});

// Loyalty endpoints
Route::get('/loyalty/tiers', [LoyaltyController::class, 'tiers'])->name('loyalty.tiers'); // Public endpoint

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('loyalty')->group(function () {
    Route::get('/card', [LoyaltyController::class, 'card'])->name('loyalty.card');
    Route::get('/transactions', [LoyaltyController::class, 'transactions'])->name('loyalty.transactions');
    Route::get('/progress', [LoyaltyController::class, 'progress'])->name('loyalty.progress');
    Route::post('/award', [LoyaltyController::class, 'award'])->name('loyalty.award');
    Route::post('/redeem', [LoyaltyController::class, 'redeem'])->name('loyalty.redeem');
});

// Notification endpoints
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::put('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::get('/settings', [NotificationController::class, 'getSettings'])->name('settings.get');
    Route::put('/settings', [NotificationController::class, 'updateSettings'])->name('settings.update');
});

// Achievement endpoints
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('achievements')->group(function () {
    Route::get('/', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('/unlocked', [AchievementController::class, 'unlocked'])->name('achievements.unlocked');
    Route::get('/progress', [AchievementController::class, 'progress'])->name('achievements.progress');
    Route::get('/my', [AchievementController::class, 'my'])->name('achievements.my');
    Route::post('/{id}/unlock', [AchievementController::class, 'unlock'])->name('achievements.unlock');
});

// Chat endpoints - Requires authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('chat')->name('chat.')->group(function () {
    // List user's chat rooms
    Route::get('/rooms', [ChatController::class, 'listUserRooms'])->name('rooms.list');

    // Get/create public chat room (general)
    Route::get('/rooms/public', [ChatController::class, 'getOrCreatePublicRoomGeneral'])->name('rooms.public.general');

    // Get cafe-specific chat room by cafe ID
    Route::get('/rooms/cafe/{cafeId}', [ChatController::class, 'getCafeRoomByCafe'])->name('rooms.cafe.bycafe');

    // Get match-specific chat room
    Route::get('/rooms/match/{matchId}', [ChatController::class, 'getMatchRoom'])->name('rooms.match');

    // Get public chat room for a match (legacy)
    Route::get('/rooms/{matchId}', [ChatController::class, 'getPublicRoom'])->name('rooms.public')
        ->where('matchId', '[0-9]+');

    // Get cafe-specific chat room for a match and branch
    Route::get('/rooms/{matchId}/branch/{branchId}', [ChatController::class, 'getCafeRoom'])->name('rooms.cafe');

    // Get messages for a chat room
    Route::get('/rooms/{roomId}/messages', [ChatController::class, 'getMessages'])->name('messages.index');

    // Send message (with throttle middleware - 10 messages per minute)
    Route::post('/rooms/{roomId}/messages', [ChatController::class, 'sendMessage'])
        ->name('messages.send');

    // Send reaction
    Route::post('/rooms/{roomId}/reaction', [ChatController::class, 'sendReaction'])->name('reaction.send');

    // Get viewers count
    Route::get('/rooms/{roomId}/viewers', [ChatController::class, 'getViewersCount'])->name('viewers.count');

    // Get online users
    Route::get('/rooms/{roomId}/online-users', [ChatController::class, 'getOnlineUsers'])->name('users.online');
});

// ===================================
// SUBSCRIPTION PLANS (Public)
// ===================================
Route::middleware(['throttle:api'])->group(function () {
    Route::get('/subscription/plans', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('subscription.plans');
});

// ===================================
// ADMIN ROUTES (Aliases for tests - maps /admin/... to same controllers as /cafe-admin/...)
// ===================================
Route::middleware(['auth:sanctum', 'cafe.owner'])->prefix('admin')->name('admin.')->group(function () {
    // Cafe CRUD
    Route::post('/cafes', [CafeAdminController::class, 'createCafe'])->name('cafes.create');
    Route::get('/cafes', [CafeAdminController::class, 'listCafes'])->name('cafes.index');
    Route::put('/cafes/{cafeId}', [CafeAdminController::class, 'updateCafe'])->name('cafes.update');
    Route::post('/cafes/{cafeId}/logo', [CafeAdminController::class, 'uploadLogo'])->name('cafes.logo');
    Route::get('/cafes/{cafeId}/onboarding', [CafeAdminController::class, 'getOnboardingStatus'])->name('cafes.onboarding');

    // Branch CRUD (nested under cafe)
    Route::post('/cafes/{cafeId}/branches', [CafeAdminController::class, 'createBranch'])->middleware('subscription')->name('cafes.branches.create');
    Route::get('/cafes/{cafeId}/branches', [CafeAdminController::class, 'listBranches'])->name('cafes.branches.index');

    // Branch CRUD (direct)
    Route::get('/branches/{id}', [CafeAdminController::class, 'getBranch'])->name('branches.show');
    Route::put('/branches/{id}', [CafeAdminController::class, 'updateBranch'])->name('branches.update');
    Route::delete('/branches/{id}', [CafeAdminController::class, 'deleteBranch'])->name('branches.delete');
    Route::post('/branches/{id}/hours', [CafeAdminController::class, 'updateBranchHours'])->name('branches.hours');
    Route::post('/branches/{id}/amenities', [CafeAdminController::class, 'addAmenitiesBulk'])->name('branches.amenities');
    Route::get('/branches/{id}/overview', [CafeAdminController::class, 'getBranchOverview'])->name('branches.overview');
    Route::get('/branches/{id}/setup-progress', [CafeAdminController::class, 'getBranchSetupProgress'])->name('branches.setup');

    // Seating (nested under branch)
    Route::post('/branches/{branchId}/seating-sections', [SeatingAdminController::class, 'createSection'])->name('branches.sections.create');
    Route::get('/branches/{branchId}/seating-layout', [SeatingAdminController::class, 'seatingLayout'])->name('branches.seating-layout');

    // Seating (direct)
    Route::post('/seating-sections/{id}/seats/bulk', [SeatingAdminController::class, 'bulkAddSeats'])->name('sections.seats.bulk');
    Route::delete('/seating-sections/{id}', [SeatingAdminController::class, 'deleteSection'])->name('sections.delete');
    Route::put('/seats/{id}', [SeatingAdminController::class, 'updateSeat'])->name('seats.update');
    Route::put('/seats/{id}/toggle-availability', [SeatingAdminController::class, 'toggleAvailability'])->name('seats.toggle');

    // Match Management (nested under branch)
    Route::post('/branches/{branchId}/matches', [MatchAdminController::class, 'store'])->name('branches.matches.create');
    Route::get('/branches/{branchId}/matches', [MatchAdminController::class, 'index'])->name('branches.matches.index');

    // Match Management (direct)
    Route::put('/matches/{id}', [MatchAdminController::class, 'update'])->name('matches.update');
    Route::put('/matches/{id}/publish', [MatchAdminController::class, 'publish'])->name('matches.publish');
    Route::put('/matches/{id}/score', [MatchAdminController::class, 'updateScore'])->name('matches.score');
    Route::put('/matches/{id}/start', [MatchAdminController::class, 'startMatch'])->name('matches.start');
    Route::put('/matches/{id}/end', [MatchAdminController::class, 'endMatch'])->name('matches.end');
    Route::put('/matches/{id}/cancel', [MatchAdminController::class, 'cancelMatch'])->name('matches.cancel');

    // Booking Management (nested under branch)
    Route::get('/branches/{branchId}/bookings', [BookingAdminController::class, 'index'])->name('branches.bookings.index');
    Route::get('/branches/{branchId}/bookings/today', [BookingAdminController::class, 'todaySummary'])->name('branches.bookings.today');
    Route::get('/branches/{branchId}/bookings/export', [BookingAdminController::class, 'exportReport'])->name('branches.bookings.export');

    // Booking Management (direct)
    Route::get('/bookings/{id}', [BookingAdminController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{id}/check-in', [BookingAdminController::class, 'checkIn'])->name('bookings.checkin');
    Route::post('/bookings/scan-qr', [QrScanController::class, 'scan'])->name('bookings.scan-qr');

    // Staff Management (nested under branch)
    Route::post('/branches/{branchId}/staff/invite', [\App\Http\Controllers\StaffController::class, 'inviteStaff'])->name('branches.staff.invite');
    Route::get('/branches/{branchId}/staff', [\App\Http\Controllers\StaffController::class, 'listBranchStaff'])->name('branches.staff.index');
    Route::put('/branches/{branchId}/staff/{staffId}/permissions', [\App\Http\Controllers\StaffController::class, 'updatePermissions'])->name('branches.staff.permissions');
    Route::delete('/branches/{branchId}/staff/{staffId}', [\App\Http\Controllers\StaffController::class, 'removeStaff'])->name('branches.staff.remove');

    // Offer Management (nested under branch)
    Route::post('/branches/{branchId}/offers', [\App\Http\Controllers\OfferAdminController::class, 'storeForBranch'])->name('branches.offers.create');
    Route::get('/branches/{branchId}/offers', [\App\Http\Controllers\OfferAdminController::class, 'listForBranch'])->name('branches.offers.index');

    // Offer Management (direct)
    Route::put('/offers/{id}', [\App\Http\Controllers\OfferAdminController::class, 'updateBranch'])->name('admin.offers.update');
    Route::delete('/offers/{id}', [\App\Http\Controllers\OfferAdminController::class, 'deleteBranch'])->name('admin.offers.delete');
    Route::post('/offers/{id}/image', [\App\Http\Controllers\OfferAdminController::class, 'uploadImageBranch'])->name('admin.offers.image');
    Route::put('/offers/{id}/toggle-status', [\App\Http\Controllers\OfferAdminController::class, 'toggleStatus'])->name('admin.offers.toggle');

    // Subscription Management (nested under cafe)
    Route::get('/cafes/{cafeId}/subscription', [\App\Http\Controllers\SubscriptionController::class, 'current'])->name('cafes.subscription.current');
    Route::post('/cafes/{cafeId}/subscription/upgrade', [\App\Http\Controllers\SubscriptionController::class, 'upgrade'])->name('cafes.subscription.upgrade');
    Route::post('/cafes/{cafeId}/subscription/downgrade', [\App\Http\Controllers\SubscriptionController::class, 'downgrade'])->name('cafes.subscription.downgrade');
    Route::post('/cafes/{cafeId}/subscription/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cafes.subscription.cancel');
    Route::post('/cafes/{cafeId}/subscription/resume', [\App\Http\Controllers\SubscriptionController::class, 'resume'])->name('cafes.subscription.resume');
    Route::get('/cafes/{cafeId}/subscription/billing-history', [\App\Http\Controllers\SubscriptionController::class, 'billingHistory'])->name('cafes.subscription.billing');
    Route::put('/cafes/{cafeId}/subscription/payment-method', [\App\Http\Controllers\BillingController::class, 'updatePaymentMethod'])->name('cafes.subscription.payment');

    // Subscription plans list
    Route::get('/subscription/plans', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('subscription.plans');

    // Analytics (nested under branch)
    Route::get('/branches/{branchId}/analytics/dashboard', [\App\Http\Controllers\AnalyticsController::class, 'overview'])->name('branches.analytics.dashboard');
    Route::get('/branches/{branchId}/analytics/revenue', [\App\Http\Controllers\AnalyticsController::class, 'revenue'])->name('branches.analytics.revenue');
    Route::get('/branches/{branchId}/analytics/bookings', [\App\Http\Controllers\AnalyticsController::class, 'bookings'])->name('branches.analytics.bookings');
    Route::get('/branches/{branchId}/analytics/charts/revenue', [\App\Http\Controllers\AnalyticsController::class, 'chartData'])->name('branches.analytics.charts');
    Route::get('/branches/{branchId}/analytics/top-matches', [\App\Http\Controllers\AnalyticsController::class, 'topMatches'])->name('branches.analytics.top-matches');
    Route::get('/branches/{branchId}/analytics/occupancy', [\App\Http\Controllers\AnalyticsController::class, 'occupancy'])->name('branches.analytics.occupancy');
    Route::post('/branches/{branchId}/analytics/export', [\App\Http\Controllers\AnalyticsController::class, 'exportReport'])->name('branches.analytics.export');
});

// Staff invitation acceptance (authenticated, but no cafe.owner required)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/staff/invitations/accept', [\App\Http\Controllers\StaffController::class, 'acceptInvitation'])->name('staff.invitations.accept');
});

// Subscription webhook (no auth)
Route::post('/webhooks/subscription/payment-failed', [\App\Http\Controllers\SubscriptionController::class, 'handlePaymentFailed'])->name('webhooks.subscription.payment-failed');

// ===================================
// CAFE OWNER MANAGEMENT
// ===================================
Route::middleware(['auth:sanctum', 'cafe.owner'])->prefix('cafe-admin')->name('cafe-admin.')->group(function () {

    // CAFE MANAGEMENT (Endpoints 1-5)
    Route::post('/cafe', [CafeAdminController::class, 'createCafe'])->name('cafe.create');
    Route::put('/cafe', [CafeAdminController::class, 'updateCafe'])->name('cafe.update');
    Route::post('/cafe/logo', [CafeAdminController::class, 'uploadLogo'])
        ->middleware('throttle:uploads')
        ->name('cafe.logo');
    Route::get('/cafe', [CafeAdminController::class, 'getMyCafe'])->name('cafe.show');
    Route::get('/onboarding-status', [CafeAdminController::class, 'getOnboardingStatus'])->name('onboarding.status');

    // BRANCH CRUD (Endpoints 6-14)
    Route::get('/branches', [CafeAdminController::class, 'listBranches'])->name('branches.index');
    Route::post('/branches', [CafeAdminController::class, 'createBranch'])->middleware('subscription')->name('branches.create');
    Route::put('/branches/{id}/hours', [CafeAdminController::class, 'updateBranchHours'])->name('branches.hours');
    Route::post('/branches/{id}/amenities/bulk', [CafeAdminController::class, 'addAmenitiesBulk'])->name('branches.amenities.bulk');
    Route::get('/branches/{id}', [CafeAdminController::class, 'getBranch'])->name('branches.show');
    Route::put('/branches/{id}', [CafeAdminController::class, 'updateBranch'])->name('branches.update');
    Route::delete('/branches/{id}', [CafeAdminController::class, 'deleteBranch'])->name('branches.delete');
    Route::put('/branches/{id}/status', [CafeAdminController::class, 'toggleBranchStatus'])->name('branches.status');
    Route::get('/branches/{id}/setup-progress', [CafeAdminController::class, 'getBranchSetupProgress'])->name('branches.setup-progress');

    // BRANCH OVERVIEW (Endpoint 15)
    Route::get('/branches/{id}/overview', [CafeAdminController::class, 'getBranchOverview'])->name('branches.overview');

    // SWITCH BRANCH (Endpoint 17)
    Route::put('/current-branch', [CafeAdminController::class, 'switchCurrentBranch'])->name('current-branch.switch');

    // AMENITIES (Endpoints 18-20)
    Route::get('/branches/{id}/amenities', [CafeAdminController::class, 'listAmenities'])->name('branches.amenities.list');
    Route::post('/branches/{id}/amenities', [CafeAdminController::class, 'addAmenity'])->name('branches.amenities.add');
    Route::delete('/amenities/{id}', [CafeAdminController::class, 'removeAmenity'])->name('amenities.remove');

    // ===================================
    // SEATING MANAGEMENT (Endpoints 21-29)
    // ===================================
    Route::get('/branches/{id}/sections', [SeatingAdminController::class, 'listSections'])->name('sections.list');
    Route::post('/branches/{id}/sections', [SeatingAdminController::class, 'createSection'])->name('sections.create');
    Route::post('/branches/{id}/sections/bulk', [SeatingAdminController::class, 'bulkCreateSections'])->name('sections.bulk');
    Route::put('/sections/{id}', [SeatingAdminController::class, 'updateSection'])->name('sections.update');
    Route::delete('/sections/{id}', [SeatingAdminController::class, 'deleteSection'])->name('sections.delete');
    Route::get('/sections/{id}/seats', [SeatingAdminController::class, 'listSeats'])->name('sections.seats.list');
    Route::post('/sections/{id}/seats', [SeatingAdminController::class, 'bulkAddSeats'])->name('sections.seats.add');
    Route::put('/seats/{id}', [SeatingAdminController::class, 'updateSeat'])->name('seats.update');
    Route::delete('/seats/{id}', [SeatingAdminController::class, 'deleteSeat'])->name('seats.delete');

    // ===================================
    // MATCH MANAGEMENT (Endpoints 30-38)
    // ===================================
    Route::get('/matches', [MatchAdminController::class, 'index'])->name('matches.index');
    Route::post('/matches', [MatchAdminController::class, 'store'])->name('matches.store');
    Route::get('/matches/{id}', [MatchAdminController::class, 'show'])->name('matches.show');
    Route::put('/matches/{id}', [MatchAdminController::class, 'update'])->name('matches.update');
    Route::delete('/matches/{id}', [MatchAdminController::class, 'destroy'])->name('matches.destroy');
    Route::post('/matches/{id}/publish', [MatchAdminController::class, 'publish'])->name('matches.publish');
    Route::put('/matches/{id}/score', [MatchAdminController::class, 'updateScore'])->name('matches.score');
    Route::put('/matches/{id}/status', [MatchAdminController::class, 'updateStatus'])->name('matches.status');
    Route::post('/matches/{id}/reminder', [MatchAdminController::class, 'sendReminder'])->name('matches.reminder');

    // ===================================
    // BOOKING MANAGEMENT (Endpoints 39-43)
    // ===================================
    Route::get('/bookings/today-summary', [BookingAdminController::class, 'todaySummary'])->name('bookings.today-summary');
    Route::get('/bookings', [BookingAdminController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{id}', [BookingAdminController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{id}/check-in', [BookingAdminController::class, 'checkIn'])->name('bookings.check-in');
    Route::post('/bookings/{id}/cancel', [BookingAdminController::class, 'cancel'])->name('bookings.cancel');

    // ===================================
    // QR SCAN (Endpoints 44-47)
    // ===================================
    Route::post('/scan-qr', [QrScanController::class, 'scan'])->name('scan-qr.scan');
    Route::post('/scan-qr/upload', [QrScanController::class, 'upload'])
        ->middleware('throttle:uploads')
        ->name('scan-qr.upload');
    Route::get('/scan-qr/recent', [QrScanController::class, 'recent'])->name('scan-qr.recent');
    Route::get('/scan-qr/stats', [QrScanController::class, 'stats'])->name('scan-qr.stats');

    // ===================================
    // OCCUPANCY TRACKING (Endpoints 48-51)
    // ===================================
    Route::middleware(['subscription:has_occupancy_tracking'])->group(function () {
        Route::get('/occupancy', [OccupancyController::class, 'index'])->name('occupancy.dashboard');
        Route::put('/occupancy/capacity', [OccupancyController::class, 'updateCapacity'])->name('occupancy.capacity');
        Route::get('/occupancy/peak-times', [OccupancyController::class, 'peakTimes'])->name('occupancy.peak-times');
        Route::get('/occupancy/sections', [OccupancyController::class, 'sections'])->name('occupancy.sections');
    });

    // ===================================
    // STAFF MANAGEMENT (Endpoints 52-59)
    // ===================================
    Route::get('/staff', [\App\Http\Controllers\StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [\App\Http\Controllers\StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{id}', [\App\Http\Controllers\StaffController::class, 'show'])->name('staff.show');
    Route::put('/staff/{id}', [\App\Http\Controllers\StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{id}', [\App\Http\Controllers\StaffController::class, 'destroy'])->name('staff.destroy');
    Route::post('/staff/{id}/resend-invite', [\App\Http\Controllers\StaffController::class, 'resendInvite'])->name('staff.resend-invite');
    Route::get('/roles-permissions', [\App\Http\Controllers\StaffController::class, 'rolesPermissions'])->name('roles-permissions');

    // ===================================
    // OFFERS MANAGEMENT (Endpoints 60-66)
    // ===================================
    Route::get('/offers', [\App\Http\Controllers\OfferAdminController::class, 'index'])->name('offers.index');
    Route::post('/offers', [\App\Http\Controllers\OfferAdminController::class, 'store'])->name('offers.store');
    Route::get('/offers/{id}', [\App\Http\Controllers\OfferAdminController::class, 'show'])->name('offers.show');
    Route::put('/offers/{id}', [\App\Http\Controllers\OfferAdminController::class, 'update'])->name('offers.update');
    Route::delete('/offers/{id}', [\App\Http\Controllers\OfferAdminController::class, 'destroy'])->name('offers.destroy');
    Route::post('/offers/{id}/upload-image', [\App\Http\Controllers\OfferAdminController::class, 'uploadImage'])
        ->middleware('throttle:uploads')
        ->name('offers.upload-image');
    Route::put('/offers/{id}/status', [\App\Http\Controllers\OfferAdminController::class, 'updateStatus'])->name('offers.status');

    // ===================================
    // DASHBOARD (Endpoints 67-69)
    // ===================================
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/upcoming-matches', [\App\Http\Controllers\DashboardController::class, 'upcomingMatches'])->name('dashboard.upcoming-matches');
    Route::get('/dashboard/recent-bookings', [\App\Http\Controllers\DashboardController::class, 'recentBookings'])->name('dashboard.recent-bookings');

    // ===================================
    // ANALYTICS (Endpoints 70-76)
    // ===================================
    Route::middleware(['subscription:has_analytics'])->group(function () {
        Route::get('/analytics/overview', [\App\Http\Controllers\AnalyticsController::class, 'overview'])->name('analytics.overview');
        Route::get('/analytics/bookings', [\App\Http\Controllers\AnalyticsController::class, 'bookings'])->name('analytics.bookings');
        Route::get('/analytics/revenue', [\App\Http\Controllers\AnalyticsController::class, 'revenue'])->name('analytics.revenue');
        Route::get('/analytics/peak-hours', [\App\Http\Controllers\AnalyticsController::class, 'peakHours'])->name('analytics.peak-hours');
        Route::get('/analytics/customers', [\App\Http\Controllers\AnalyticsController::class, 'customers'])->name('analytics.customers');
        Route::get('/analytics/matches', [\App\Http\Controllers\AnalyticsController::class, 'matches'])->name('analytics.matches');
        Route::get('/analytics/subscription', [\App\Http\Controllers\AnalyticsController::class, 'subscription'])->name('analytics.subscription');
    });

    // ===================================
    // SUBSCRIPTION MANAGEMENT (Endpoints 77-81)
    // ===================================
    Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'current'])->name('subscription.current');
    Route::post('/subscription/upgrade', [\App\Http\Controllers\SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
    Route::post('/subscription/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::put('/subscription/auto-renew', [\App\Http\Controllers\SubscriptionController::class, 'toggleAutoRenew'])->name('subscription.auto-renew');

    // ===================================
    // BILLING MANAGEMENT (Endpoints 82-85)
    // ===================================
    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/summary', [\App\Http\Controllers\BillingController::class, 'summary'])->name('billing.summary');
    Route::get('/billing/export', [\App\Http\Controllers\BillingController::class, 'export'])->name('billing.export');
    Route::put('/billing/payment-method', [\App\Http\Controllers\BillingController::class, 'updatePaymentMethod'])->name('billing.payment-method');

    // ===================================
    // SUBSCRIPTION USAGE (Enforcement)
    // ===================================
    Route::get('/subscription/usage', function (Request $request) {
        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json(['success' => false, 'message' => 'No cafe found'], 404);
        }
        $enforcement = app(\App\Services\SubscriptionEnforcementService::class);
        return response()->json([
            'success' => true,
            'data' => $enforcement->getUsageSummary($cafe),
        ]);
    })->name('subscription.usage');
});

