<?php

/*
|--------------------------------------------------------------------------
| API Distribution Adventure
|--------------------------------------------------------------------------
|
| The stages of the animated request journey on the public home page. The
| view, the CSS and the Three.js timeline all read this list, so adding a
| product or a provider is a change here rather than in four places.
|
| `id`    is the DOM hook and the timeline key — keep it stable.
| `tone`  selects the palette: brand (connected), secure (signature),
|         process (central API), ok (delivered).
| `tip`   is the hover/focus detail. It must add architecture information,
|         never repeat the description.
|
*/

return [

    // Seconds for one full journey. The timeline divides this across the
    // stages by weight, so retiming the whole thing is one number.
    'duration' => 21.0,

    // How long the finished state holds before the journey replays.
    'replay_delay' => 3.0,

    'stages' => [
        [
            'id' => 'products',
            'art' => 'products-capsule.webp',
            'states' => ['Idle', 'Sending', 'Connected'],
            'title' => 'Connected Products',
            'detail' => 'Kirada · Djib Payroll · SMKit',
            'tone' => 'brand',
            'icon' => 'stack',
            'weight' => 1.0,
            'tip' => 'Applications authenticate and submit signed requests. No product ever holds a Meta token or a Stripe key.',
        ],
        [
            'id' => 'signature',
            'art' => 'signature-capsule.webp',
            'states' => ['Waiting', 'Verifying…', 'Verified'],
            'title' => 'Signature Gate',
            'detail' => 'X-BWA-Signature · 300s window · nonce burns on first use',
            'tone' => 'secure',
            'icon' => 'shield',
            'weight' => 1.2,
            'tip' => 'Rejects expired, replayed or invalid signatures before any work is queued.',
        ],
        [
            'id' => 'central-api',
            'art' => 'central-capsule.webp',
            'states' => ['Waiting', 'Validating', 'Queued'],
            'title' => 'Central API',
            'detail' => 'Validate → Persist → Enqueue',
            'tone' => 'process',
            'icon' => 'server',
            'weight' => 1.2,
            'tip' => 'Validates the payload, persists it, and dispatches asynchronous jobs. Nothing calls a provider inline.',
        ],
    ],

    // Fanned out from the central API, each on its own branch of the pipe.
    'services' => [
        [
            'id' => 'ai',
            'art' => 'ai.webp',
            'loot' => 'effects/crystal.webp',
            'states' => ['Waiting', 'Calling', 'Delivered'],
            'title' => 'AI Assist',
            'detail' => 'Model provider',
            'icon' => 'spark',
            'tip' => 'Bounded AI provider requests with a hard timeout and a token ceiling.',
        ],
        [
            'id' => 'whatsapp',
            'art' => 'whatsapp.webp',
            'loot' => 'effects/gem-green.webp',
            'states' => ['Waiting', 'Queued', 'Delivered'],
            'title' => 'WhatsApp',
            'detail' => 'Meta Cloud API',
            'icon' => 'chat',
            'tip' => 'Queued messaging through the Meta Cloud API, with delivery status relayed back to the product.',
        ],
        [
            'id' => 'billing',
            'art' => 'billing.webp',
            'loot' => 'effects/orb.webp',
            'states' => ['Waiting', 'Charging', 'Delivered'],
            'title' => 'Billing',
            'detail' => 'Stripe',
            'icon' => 'card',
            'tip' => 'Idempotent Stripe operations. A retried job never charges twice.',
        ],
    ],

    'destination' => [
        'id' => 'webhook',
        'states' => ['Pending', 'Signing', 'Delivered'],
        'title' => 'Signed Application Event',
        'detail' => 'Delivered back to the product webhook',
        'icon' => 'bolt',
        'tip' => 'The product receives a signed event it can verify with the same scheme it used to call in.',
    ],

    'legend' => [
        ['label' => 'Connected', 'tone' => 'brand'],
        ['label' => 'Secured', 'tone' => 'secure'],
        ['label' => 'Processing', 'tone' => 'process'],
        ['label' => 'Delivered', 'tone' => 'ok'],
    ],

    'note' => 'Every external call is queued, bounded and idempotent.',
];
