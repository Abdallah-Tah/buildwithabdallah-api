<?php

declare(strict_types=1);

it('renders the overview page', function (): void {
    $this->get(route('page.home'))
        ->assertOk()
        ->assertSee('One credential boundary for every product I ship.', false)
        ->assertSee('Central API');
});

it('renders the architecture page', function (): void {
    $this->get(route('page.about'))
        ->assertOk()
        ->assertSee('Why this service exists, and how it is built.', false)
        ->assertSee('Request lifecycle');
});

it('renders the documentation page', function (): void {
    $this->get(route('page.docs'))
        ->assertOk()
        ->assertSee('Central API documentation')
        ->assertSee('X-BWA-Signature')
        ->assertSee('IDEMPOTENCY_CONFLICT');
});

it('links every public page to the others', function (string $route): void {
    $this->get(route($route))
        ->assertOk()
        ->assertSee(route('page.docs'), false)
        ->assertSee(route('page.about'), false)
        ->assertSee('buildwithabdallah.com', false);
})->with(['page.home', 'page.about', 'page.docs']);

it('serves the public assets the layout references', function (string $path): void {
    expect(public_path($path))->toBeReadableFile();
})->with(['css/site.css', 'js/site.js', 'images/bwa-logo.jpeg', 'images/bwa-mark.svg']);
