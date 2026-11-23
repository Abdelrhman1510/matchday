<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMoyasarWebhookEvent;
use App\Models\MoyasarWebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /webhooks/payment/moyasar. Stores the event, answers 200, and does the work
 * after the response, because Moyasar drops events from slow or failing endpoints.
 */
class MoyasarBookingWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.moyasar.webhook_secret');
        $provided = $request->input('secret_token');

        if ($secret === '' || !is_string($provided) || !hash_equals($secret, $provided)) {
            Log::warning('Moyasar webhook rejected: bad secret');
            return response()->json(['success' => false], 401);
        }

        if (app()->environment('production') && $request->input('live') === false) {
            Log::warning('Moyasar webhook rejected: test event sent to production');
            return response()->json(['success' => false], 403);
        }

        $eventId = $request->input('id');
        $type = $request->input('type');
        if (!is_string($eventId) || $eventId === '' || !is_string($type)) {
            return response()->json(['success' => true, 'message' => 'ignored']);
        }

        $payload = $request->except('secret_token'); // the secret must not sit in the database

        try {
            $event = MoyasarWebhookEvent::firstOrCreate(
                ['event_id' => $eventId],
                ['type' => $type, 'live' => (bool) $request->input('live'), 'payload' => $payload],
            );
        } catch (UniqueConstraintViolationException) {
            return response()->json(['success' => true]); // concurrent duplicate delivery
        }

        if ($event->wasRecentlyCreated) {
            if ($type === 'webhook_verification') {
                $event->update(['processed_at' => now()]); // the code is shown on the Fees page
            } else {
                ProcessMoyasarWebhookEvent::dispatchAfterResponse($event->id);
            }
        }

        return response()->json(['success' => true]);
    }
}
