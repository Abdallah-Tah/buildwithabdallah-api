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
    'js/api-adventure/assets.js',
    'js/api-adventure/geometry.js',
    'js/api-adventure/pipework.js',
]);

it('ships every sprite the asset manifest promises', function (): void {
    // The manifest is generated from the sprite sheets. If an entry survives a
    // re-run but its file does not, the page renders an empty machine and the
    // only symptom is a gap where the artwork should be.
    $manifest = json_decode(file_get_contents(public_path('images/api-adventure/manifest.json')), true);

    foreach ($manifest as $group => $entries) {
        if (! is_array($entries)) {
            continue;
        }

        foreach ($entries as $key => $entry) {
            expect(public_path(ltrim($entry['src'], '/')))
                ->toBeReadableFile("{$group}.{$key} is in the manifest but not on disk");
        }
    }
});

it('knows where the blank sign board sits on every service station', function (): void {
    // Only the provider stations use their sign board — the upper three are
    // bare capsules inserted into the pipe and carry a floating chip instead.
    // Without a measured position the label lands somewhere arbitrary on the
    // machine, which is how it looked before the measurement existed.
    $manifest = json_decode(file_get_contents(public_path('images/api-adventure/manifest.json')), true);

    foreach (config('api_adventure.services') as $service) {
        $sign = $manifest['stations'][basename($service['art'], '.webp')]['sign'] ?? null;

        expect($sign)->toBeArray("no sign box measured for {$service['id']}")
            ->and($sign['top'])->toBeGreaterThan(0.0)->toBeLessThan(0.6)
            ->and($sign['width'])->toBeGreaterThan(0.2);
    }
});

it('uses a capsule crop for every stage that sits inside the pipe', function (): void {
    // The upper stages must not ship their platform artwork: the base is what
    // made that row read as three objects standing near a line rather than as
    // one continuous pipeline.
    foreach (config('api_adventure.stages') as $stage) {
        expect($stage['art'])->toEndWith('-capsule.webp')
            ->and(public_path('images/api-adventure/stations/'.$stage['art']))->toBeReadableFile();
    }
});

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

it('places every pipe fitting on the real dimensions of its artwork', function (): void {
    // geometry.js anchors each fitting by its ports, and those fractions are
    // only meaningful against the sprite they were measured from. Regenerating
    // the artwork at a different size would silently move every corner of the
    // level, so the two are pinned together here.
    $source = file_get_contents(public_path('js/api-adventure/geometry.js'));
    $art = [
        'drop' => 'elbow-left-down',
        'turn' => 'elbow-left-up',
        'return' => 'elbow-up-right',
        'tee' => 'tee-up',
    ];

    foreach ($art as $fitting => $sprite) {
        expect($source)->toMatch('/'.$fitting.':\s*{[^}]*}/');

        preg_match('/'.$fitting.':\s*{\s*w:\s*(\d+),\s*h:\s*(\d+)/', $source, $declared);

        [, $width, $height] = array_map('intval', $declared);
        [$actual, $actualHeight] = getimagesize(public_path("images/api-adventure/pipes/{$sprite}.webp"));

        expect([$width, $height])->toBe([$actual, $actualHeight]);
    }
});

it('turns each corner with an elbow that has ports on those two sides', function (): void {
    // A left-down elbow at a corner that needs left-up leaves the pipe meeting
    // a closed flange, which is exactly how the lane used to read.
    $html = $this->get(route('page.home'))->assertOk()->getContent();

    expect($html)
        // Row one turns down into the lane.
        ->toContain('adv-mouth-rim')
        ->toMatch('/adv-mouth-rim" src="[^"]*elbow-left-down/')
        // The descent turns left along the lane.
        ->toMatch('/adv-fit--turn" src="[^"]*elbow-left-up/')
        // The lane turns down the left edge to the rail.
        ->toMatch('/adv-fit--return" src="[^"]*elbow-up-right/');
});
