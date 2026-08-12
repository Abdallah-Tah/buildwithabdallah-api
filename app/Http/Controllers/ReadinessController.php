<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (Throwable) {
            $checks['database'] = 'failed';
        }

        try {
            Cache::put('health-ready', true, 5);
            Cache::forget('health-ready');
            $checks['cache'] = 'ok';
        } catch (Throwable) {
            $checks['cache'] = 'failed';
        }

        $queueConfigured = is_string(config('queue.default')) && config('queue.connections.'.config('queue.default'));
        $checks['queue'] = $queueConfigured ? 'ok' : 'failed';
        $provider = (string) config('services.whatsapp.provider');
        $providerConfigured = match ($provider) {
            'meta' => filled(config('services.meta_whatsapp.access_token'))
                && filled(config('services.meta_whatsapp.phone_number_id'))
                && filled(config('services.meta_whatsapp.graph_api_version')),
            'sent' => filled(config('services.sent_dm.api_key'))
                && filled(config('services.sent_dm.base_url')),
            default => false,
        };
        $checks['whatsapp_provider'] = $providerConfigured ? 'ok' : 'failed';
        $ready = ! in_array('failed', $checks, true);

        return response()->json([
            'status' => $ready ? 'ready' : 'not_ready',
            'checks' => $checks,
            'whatsapp_provider' => $provider,
        ], $ready ? 200 : 503);
    }
}
