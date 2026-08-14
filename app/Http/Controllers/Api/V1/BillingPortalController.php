<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Billing\CreatePortalSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBillingPortalRequest;
use App\Models\ConnectedApplication;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BillingPortalController extends Controller
{
    public function __invoke(CreateBillingPortalRequest $request, CreatePortalSession $create): JsonResponse
    {
        /** @var ConnectedApplication $application */
        $application = $request->attributes->get('connected_application');
        $session = $create->handle($application, $request->validated());

        return response()->json([
            'data' => ['id' => $session['id'], 'url' => $session['url']],
        ], Response::HTTP_CREATED);
    }
}
