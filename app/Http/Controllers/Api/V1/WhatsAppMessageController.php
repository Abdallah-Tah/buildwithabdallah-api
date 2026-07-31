<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendWhatsAppMessageRequest;
use App\Http\Resources\WhatsAppMessageResource;
use App\Messaging\CreateOutboundWhatsAppMessage;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppMessage;
use Illuminate\Http\JsonResponse;

class WhatsAppMessageController extends Controller
{
    public function store(SendWhatsAppMessageRequest $request, CreateOutboundWhatsAppMessage $create): JsonResponse
    {
        /** @var ConnectedApplication $application */
        $application = $request->attributes->get('connected_application');
        $message = $create->handle($application, $request->validated());

        return (new WhatsAppMessageResource($message))->response()->setStatusCode(202);
    }

    public function show(WhatsAppMessage $message): WhatsAppMessageResource
    {
        /** @var ConnectedApplication $application */
        $application = request()->attributes->get('connected_application');

        abort_unless($message->connected_application_id === $application->id, 404);

        return new WhatsAppMessageResource($message);
    }
}
