<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Billing\CreateCheckoutSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBillingCheckoutRequest;
use App\Models\ConnectedApplication;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BillingCheckoutController extends Controller
{
    public function __invoke(CreateBillingCheckoutRequest $request, CreateCheckoutSession $create): JsonResponse
    {
        /** @var ConnectedApplication $application */
        $application = $request->attributes->get('connected_application');
        $session = $create->handle($application, $request->validated());

        return response()->json([
            'data' => [
                'id' => $session['id'],
                'url' => $session['url'],
                'customer_id' => $session['customer'] ?? null,
            ],
        ], Response::HTTP_CREATED);
    }
}
