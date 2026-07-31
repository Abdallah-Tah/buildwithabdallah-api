<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConversationState;
use App\Http\Controllers\Controller;
use App\Http\Requests\RouteWhatsAppConversationRequest;
use App\Http\Resources\WhatsAppConversationResource;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppConversation;

class WhatsAppConversationController extends Controller
{
    public function show(WhatsAppConversation $conversation): WhatsAppConversationResource
    {
        /** @var ConnectedApplication $application */
        $application = request()->attributes->get('connected_application');

        abort_unless($conversation->connected_application_id === $application->id, 404);

        return new WhatsAppConversationResource($conversation);
    }

    public function route(RouteWhatsAppConversationRequest $request, WhatsAppConversation $conversation): WhatsAppConversationResource
    {
        /** @var ConnectedApplication $application */
        $application = $request->attributes->get('connected_application');

        if ($request->validated('product') !== $application->slug || ($conversation->connected_application_id && $conversation->connected_application_id !== $application->id)) {
            abort(403);
        }

        $conversation->update([
            'connected_application_id' => $application->id,
            'product_slug' => $request->validated('product'),
            'state' => ConversationState::Active,
            'routed_at' => now(),
        ]);

        return new WhatsAppConversationResource($conversation);
    }
}
