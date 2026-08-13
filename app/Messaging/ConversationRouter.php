<?php

namespace App\Messaging;

use App\Enums\ConversationState;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Str;

class ConversationRouter
{
    /**
     * True when the sender explicitly asked to go back to the product menu.
     *
     * Callers need this to tell a deliberate un-routing apart from a
     * conversation that was simply never routed.
     */
    public function isMenuCommand(?string $selection): bool
    {
        return in_array($this->normalize($selection), config('bwa_products.menu_commands', []), true);
    }

    public function route(WhatsAppConversation $conversation, ?string $selection): void
    {
        $selection = $this->normalize($selection);

        if (in_array($selection, config('bwa_products.menu_commands', []), true)) {
            $conversation->update([
                'connected_application_id' => null,
                'product_slug' => null,
                'state' => ConversationState::AwaitingProductSelection,
                'routed_at' => null,
            ]);

            return;
        }

        foreach (config('bwa_products.products', []) as $slug => $product) {
            if ($selection !== $product['selection'] && ! in_array($selection, $product['aliases'], true)) {
                continue;
            }

            $application = isset($product['application_slug'])
                ? ConnectedApplication::query()->where('slug', $product['application_slug'])->where('enabled', true)->first()
                : null;

            $conversation->update([
                'connected_application_id' => $application?->id,
                'product_slug' => $slug,
                'state' => ConversationState::Active,
                'routed_at' => now(),
            ]);

            return;
        }

        if ($conversation->state !== ConversationState::Active) {
            $conversation->update(['state' => ConversationState::AwaitingProductSelection]);
        }
    }

    private function normalize(?string $selection): string
    {
        return Str::of((string) $selection)->squish()->lower()->toString();
    }
}
