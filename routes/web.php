<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Platform\PlatformAuthController;
use App\Http\Controllers\PublicCafeController;
use App\Livewire\Platform\DashboardPage;
use App\Livewire\Platform\CafesPage;
use App\Livewire\Platform\CafeDetailPage;
use App\Livewire\Platform\BookingsPage;
use App\Livewire\Platform\MatchesPage;
use App\Livewire\Platform\UsersPage;
use App\Livewire\Platform\SubscriptionsPage;
use App\Livewire\Platform\AnalyticsPage;
use App\Livewire\Platform\ReportsPage;
use App\Livewire\Platform\SettingsPage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| Since this is an API-only application, web routes are minimal.
|
*/

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Matchday API is running',
        'data' => [
            'api_version' => config('app.api_version', 'v1'),
            'api_base_url' => url('/api/v1'),
            'health_check' => url('/up'),
        ],
        'meta' => (object) [],
    ]);
});

// WebSocket tester (development only)
Route::get('/ws-test', function () {
    return response()->view('ws-test');
});

// WebSocket debug tester (with detailed logging)
Route::get('/ws-debug', function () {
    return response()->view('ws-debug');
});

// Public Cafe Pages
Route::get('/cafes/{id}', [PublicCafeController::class, 'show'])->name('public.cafes.show');

/*
|--------------------------------------------------------------------------
| Platform Owner Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::prefix('platform')->name('platform.')->group(function () {
    // Authentication routes — no 'guest' middleware here because Laravel's default
    // RedirectIfAuthenticated redirects to route('home') which resolves to the API
    // /api/v1/home endpoint. The showLogin() controller handles the authenticated
    // redirect to platform.dashboard itself.
    Route::get('/login', [PlatformAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PlatformAuthController::class, 'login']);

    // Logout route (authenticated only)
    Route::post('/logout', [PlatformAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    // Dashboard routes (authenticated + platform admin)
    Route::middleware(['auth', 'platform.admin'])->group(function () {
        Route::get('/', DashboardPage::class)->name('dashboard');
        Route::get('/cafes', CafesPage::class)->name('cafes');
        Route::get('/cafes/{cafe}', CafeDetailPage::class)->name('cafes.show');
        Route::get('/bookings', BookingsPage::class)->name('bookings');
        Route::get('/matches', MatchesPage::class)->name('matches');
        Route::get('/users', UsersPage::class)->name('users');
        Route::get('/subscriptions', SubscriptionsPage::class)->name('subscriptions');
        Route::get('/analytics', AnalyticsPage::class)->name('analytics');
        Route::get('/reports', ReportsPage::class)->name('reports');
        Route::get('/settings', SettingsPage::class)->name('settings');
        Route::get('/plans', \App\Livewire\Platform\PlanManagementPage::class)->name('plans');
    });
});
