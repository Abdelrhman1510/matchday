<?php

namespace App\Http\Controllers;

use App\Models\CommissionOverride;
use App\Models\SettingsAuditLog;
use App\Models\SubscriptionPlan;
use App\Services\Wallet\FeeSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformFeesController extends Controller
{
    public function __construct(private FeeSettingsService $fees) {}

    /** GET config/fees: what the app needs to preview the booking fee. */
    public function publicConfig(): JsonResponse
    {
        return $this->ok($this->fees->publicConfig());
    }

    public function show(): JsonResponse
    {
        return $this->ok($this->fees->all());
    }

    public function update(Request $request): JsonResponse
    {
        return $this->ok($this->fees->update($request->all(), $request->user()));
    }

    public function history(Request $request): JsonResponse
    {
        $page = SettingsAuditLog::with('user:id,name')->orderByDesc('id')->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $page->items(),
            'message' => 'OK',
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
        ]);
    }

    public function overridesIndex(): JsonResponse
    {
        return $this->ok(CommissionOverride::orderBy('scope')->orderBy('scope_id')->get());
    }

    public function overridesStore(Request $request): JsonResponse
    {
        $data = $this->validateOverride($request);

        return $this->ok(CommissionOverride::create($data), 201);
    }

    public function overridesUpdate(Request $request, int $id): JsonResponse
    {
        $override = CommissionOverride::findOrFail($id);
        $override->update($this->validateOverride($request, $override));

        return $this->ok($override->fresh());
    }

    public function overridesDestroy(int $id): JsonResponse
    {
        CommissionOverride::findOrFail($id)->delete();

        return $this->ok(null);
    }

    private function validateOverride(Request $request, ?CommissionOverride $existing = null): array
    {
        $scope = $request->input('scope');
        $table = $scope === 'plan' ? 'subscription_plans' : 'cafes';

        $data = $request->validate([
            'scope' => ['required', 'in:plan,cafe'],
            'scope_id' => [
                'required', 'integer', Rule::exists($table, 'id'),
                Rule::unique('commission_overrides', 'scope_id')->where('scope', $scope)->ignore($existing?->id),
            ],
            'type' => ['required', 'in:percent,fixed_per_booking,fixed_per_guest'],
            'value' => ['required', 'numeric', 'min:0', Rule::when($request->input('type') === 'percent', ['max:100'])],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['min'] = (float) ($data['min'] ?? 0);
        $data['max'] = (float) ($data['max'] ?? 0);
        if ($data['min'] > 0 && $data['max'] > 0 && $data['max'] < $data['min']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['max' => [__('wallet.settings.max_gte_min')]]);
        }

        return $data;
    }

    public function plansIndex(): JsonResponse
    {
        return $this->ok(SubscriptionPlan::orderBy('sort_order')->orderBy('price')->get());
    }

    public function plansStore(Request $request): JsonResponse
    {
        $data = $this->validatePlan($request, true);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        $data['currency'] = 'SAR';
        $plan = SubscriptionPlan::create($data);
        $this->syncMostPopular($plan);
        Cache::forget('subscription_plans_active');

        return $this->ok($plan->fresh(), 201);
    }

    /** A price change applies to new and renewing subscriptions, never the current period. */
    public function plansUpdate(Request $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update($this->validatePlan($request, false));
        $this->syncMostPopular($plan);
        Cache::forget('subscription_plans_active');

        return $this->ok($plan->fresh());
    }

    public function plansDestroy(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        // Plans with subscribers stay on record; hiding them keeps those cafes intact.
        if (\App\Models\CafeSubscription::where('plan_id', $plan->id)->exists()) {
            $plan->update(['is_active' => false]);
        } else {
            $plan->delete();
        }
        Cache::forget('subscription_plans_active');

        return $this->ok(null);
    }

    private function validatePlan(Request $request, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => [$required, 'numeric', 'min:0'],
            'features' => ['sometimes', 'nullable', 'array'],
            'features_ar' => ['sometimes', 'nullable', 'array'],
            'max_bookings' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_most_popular' => ['sometimes', 'boolean'],
        ]);
    }

    private function syncMostPopular(SubscriptionPlan $plan): void
    {
        if ($plan->is_most_popular) {
            SubscriptionPlan::whereKeyNot($plan->id)->update(['is_most_popular' => false]);
        }
    }

    private function ok($data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => 'OK'], $status);
    }
}
