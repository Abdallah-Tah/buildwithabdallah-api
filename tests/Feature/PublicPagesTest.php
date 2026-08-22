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
})->with([
    'css/site.css',
    'js/site.js',
    'images/bwa-logo.jpeg',
    'images/bwa-mark.svg',
    'js/api-adventure/index.js',
    'js/api-adventure/timeline.js',
    'js/api-adventure/stages.js',
    'js/api-adventure/scene.js',
    'js/api-adventure/pipeline.js',
    'js/api-adventure/runner.js',
    'js/api-adventure/particles.js',
    'js/vendor/three.module.min.js',
    'js/vendor/three.core.min.js',
]);

/*
|--------------------------------------------------------------------------
| API distribution adventure
|--------------------------------------------------------------------------
|
| The canvas layer is decorative and may never load — no WebGL, reduced
| motion, a failed import. These cover the contract that makes that safe:
| the whole architecture is in the HTML before a single frame is drawn.
|
*/

it('states every stage of the request journey in the markup', function (): void {
    $response = $this->get(route('page.home'))->assertOk();

    foreach (config('api_adventure.stages') as $stage) {
        $response->assertSee($stage['title'])
            ->assertSee($stage['detail'], false)
            ->assertSee($stage['tip'], false);
    }
});

it('names every provider it fans out to, and where the event returns', function (): void {
    $response = $this->get(route('page.home'))->assertOk();

    foreach (config('api_adventure.services') as $service) {
        $response->assertSee($service['title'])->assertSee($service['detail']);
    }

    $response->assertSee(config('api_adventure.destination.title'))
        ->assertSee(config('api_adventure.note'));
});

it('starts every stage at its pending status rather than its finished one', function (): void {
    // The journey has not run yet on first paint, so a stage that already
    // reads "Delivered" would be claiming something that has not happened.
    $stages = array_merge(
        config('api_adventure.stages'),
        config('api_adventure.services'),
        [config('api_adventure.destination')],
    );

    $html = $this->get(route('page.home'))->assertOk()->getContent();

    foreach ($stages as $stage) {
        expect($html)->toContain('data-adv-states="'.implode('|', $stage['states']).'"');
    }
});

it('exposes the journey to assistive technology without the canvas', function (): void {
    $this->get(route('page.home'))
        ->assertOk()
        // The canvas carries no information, so it is hidden outright.
        ->assertSee('data-adv-canvas aria-hidden="true"', false)
        // Progress is a real progressbar, not a styled div.
        ->assertSee('role="progressbar"', false)
        ->assertSee('aria-valuenow="0"', false)
        // Each stage detail is reachable by keyboard through its button.
        ->assertSee('aria-describedby="adv-tip-signature"', false)
        ->assertSee('role="tooltip"', false);
});
