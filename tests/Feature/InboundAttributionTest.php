<?php

use App\Jobs\DispatchApplicationEvent;
use App\Messaging\WhatsAppWebhookProcessor;
use App\Models\ApplicationEventDelivery;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Support\Facades\Queue;

// A contact who replies to a product's message has never picked anything from
// the product menu, so the router leaves the conversation unowned — and an
// unowned message is relayed to nobody. These cover the fallback that hands
// such a reply to whoever last messaged the contact.

function replyEvent(string $body, string $id): WhatsAppWebhookEvent
{
    return WhatsAppWebhookEvent::factory()->create([
        'payload_hash' => hash('sha256', $id),
        'raw_payload' => [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Tenant'], 'wa_id' => '12074097887']],
                        'messages' => [[
                            'from' => '12074097887',
                            'id' => $id,
                            'timestamp' => '1785450000',
                            'type' => 'text',
                            'text' => ['body' => $body],
                        ]],
                    ],
                ]],
            ]],
        ],
    ]);
}

/** The sender used by replyEvent(). */
function knownContact(): WhatsAppContact
{
    return WhatsAppContact::factory()->create([
        'wa_id_hash' => hash('sha256', '12074097887'),
        'wa_id_encrypted' => '12074097887',
        'phone_number_hash' => hash('sha256', '12074097887'),
        'phone_number_encrypted' => '12074097887',
    ]);
}

function outboundFrom(ConnectedApplication $application, WhatsAppContact $contact): WhatsAppMessage
{
    return WhatsAppMessage::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'connected_application_id' => $application->id,
        'direction' => 'outbound',
        'status' => 'sent',
    ]);
}

test('a reply with no product selection is attributed to the only application that messaged the contact', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $contact = knownContact();
    outboundFrom($application, $contact);

    app(WhatsAppWebhookProcessor::class)->process(
        replyEvent('Thanks, I accept', 'wamid.reply'),
    );

    $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.reply')->firstOrFail();

    expect($message->connected_application_id)->toBe($application->id)
        ->and($message->conversation->product_slug)->toBe('kirada')
        ->and($message->conversation->state->value)->toBe('active');
});

test('an attributed reply is relayed to the application', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    outboundFrom($application, knownContact());

    app(WhatsAppWebhookProcessor::class)->process(
        replyEvent('Thanks, I accept', 'wamid.relayed'),
    );

    $delivery = ApplicationEventDelivery::query()->where('event_type', 'whatsapp.message.received')->firstOrFail();

    expect($delivery->connected_application_id)->toBe($application->id)
        ->and(data_get($delivery->payload, 'data.text'))->toBe('Thanks, I accept');
    Queue::assertPushedOn('application-events', DispatchApplicationEvent::class);
});

test('a contact messaged by two applications is left unattributed rather than guessed', function () {
    Queue::fake();
    $contact = knownContact();
    outboundFrom(ConnectedApplication::factory()->create(['slug' => 'kirada']), $contact);
    outboundFrom(ConnectedApplication::factory()->create(['slug' => 'djib-payroll']), $contact);

    app(WhatsAppWebhookProcessor::class)->process(
        replyEvent('hello there', 'wamid.ambiguous'),
    );

    $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.ambiguous')->firstOrFail();

    expect($message->connected_application_id)->toBeNull()
        ->and($message->conversation->state->value)->toBe('awaiting_product_selection')
        ->and(ApplicationEventDelivery::count())->toBe(0);
});

test('a contact with no history is left unattributed', function () {
    Queue::fake();
    ConnectedApplication::factory()->create(['slug' => 'kirada']);

    app(WhatsAppWebhookProcessor::class)->process(
        replyEvent('who is this', 'wamid.stranger'),
    );

    expect(WhatsAppMessage::firstOrFail()->connected_application_id)->toBeNull()
        ->and(ApplicationEventDelivery::count())->toBe(0);
});

test('a disabled application is never attributed to', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada', 'enabled' => false]);
    outboundFrom($application, knownContact());

    app(WhatsAppWebhookProcessor::class)->process(
        replyEvent('still there?', 'wamid.disabled'),
    );

    expect(WhatsAppMessage::query()->where('provider_message_id', 'wamid.disabled')->firstOrFail()->connected_application_id)
        ->toBeNull();
});

test('an explicit product selection still wins over the fallback', function () {
    Queue::fake();
    $kirada = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $payroll = ConnectedApplication::factory()->create(['slug' => 'djib-payroll']);
    outboundFrom($kirada, knownContact());

    // "2" selects Djib Payroll from the menu; the contact's Kirada history
    // must not override a choice the sender made explicitly.
    app(WhatsAppWebhookProcessor::class)->process(
        replyEvent('2', 'wamid.explicit'),
    );

    $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.explicit')->firstOrFail();

    expect($message->connected_application_id)->toBe($payroll->id)
        ->and($message->conversation->product_slug)->toBe('djib-payroll');
});

test('a menu command is not immediately re-attributed by the fallback', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    outboundFrom($application, knownContact());

    app(WhatsAppWebhookProcessor::class)->process(replyEvent('1', 'wamid.routed'));
    app(WhatsAppWebhookProcessor::class)->process(replyEvent('menu', 'wamid.cleared'));

    $conversation = WhatsAppMessage::query()->where('provider_message_id', 'wamid.cleared')->firstOrFail()->conversation;

    expect($conversation->connected_application_id)->toBeNull()
        ->and($conversation->state->value)->toBe('awaiting_product_selection');
});
