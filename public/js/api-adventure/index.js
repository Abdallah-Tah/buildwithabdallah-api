/* ========================================================================
   API distribution adventure — entry point.

   Owns the clock and the lifecycle. The HTML layer (StageView) always runs;
   the WebGL layer is imported only once the section is actually on screen and
   only when motion is welcome, so a visitor who never scrolls this far, or
   who asked for reduced motion, never pays for Three.js at all.
   ======================================================================== */

import { Timeline } from './timeline.js';
import { StageView, serviceOrder } from './stages.js';

const root = document.querySelector('[data-adventure]');

if (root) {
    boot(root);
}

function boot(root) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const duration = Number(root.dataset.advDuration) || 21;
    const replayDelay = Number(root.dataset.advReplay) || 3;
    let order = serviceOrder(root);
    let timeline = new Timeline(duration, replayDelay, order);
    const view = new StageView(root);

    /** Set once the WebGL layer has loaded. Null means HTML-only, which is valid. */
    let scene = null;
    let raf = 0;
    let last = 0;
    let visible = false;
    let paused = false;

    const toggle = root.querySelector('[data-adv-toggle]');
    const toggleText = root.querySelector('[data-adv-toggle-text]');
    const replay = root.querySelector('[data-adv-replay]');

    /* ------------------------------------------------------------ motion off */
    if (reduceMotion.matches) {
        view.settle(timeline);
        root.dataset.advMode = 'static';
        if (toggle) toggle.hidden = true;
        if (replay) replay.hidden = true;

        return;
    }

    /* ---------------------------------------------------------------- loop */
    function frame(now) {
        raf = requestAnimationFrame(frame);

        // A tab that was backgrounded returns with a huge delta. Clamping keeps
        // the journey from teleporting to the end on the first frame back.
        const dt = Math.min((now - last) / 1000, 1 / 20);
        last = now;

        if (paused) return;

        timeline.advance(dt);
        view.render(timeline);
        scene?.render(timeline, dt);
    }

    function start() {
        if (raf) return;
        last = performance.now();
        raf = requestAnimationFrame(frame);
    }

    function stop() {
        cancelAnimationFrame(raf);
        raf = 0;
    }

    /* ---------------------------------------------------------- visibility */
    // Rendering off-screen is pure waste, and starting the journey before the
    // section is read means the visitor arrives halfway through it.
    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                visible = entry.isIntersecting;

                if (visible) {
                    loadScene();
                    start();
                } else {
                    stop();
                }
            }
        },
        // A fraction-of-the-element threshold cannot work here: stacked on a
        // phone the section is several viewports tall, so it is never 35%
        // visible and the journey would never start. The band asks the
        // opposite question — is the section meaningfully in view — which
        // holds at any height.
        { threshold: 0, rootMargin: '-10% 0px -10% 0px' },
    );

    observer.observe(root);

    // A hidden tab keeps firing rAF in some browsers; stop for real.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stop();
        else if (visible) start();
    });

    /* ------------------------------------------------------------ controls */
    toggle?.addEventListener('click', () => {
        paused = !paused;
        toggle.setAttribute('aria-pressed', String(paused));
        if (toggleText) toggleText.textContent = paused ? 'Play' : 'Pause';
    });

    replay?.addEventListener('click', () => {
        timeline.reset();
        view.reset();
        scene?.reset();

        if (paused) {
            paused = false;
            toggle?.setAttribute('aria-pressed', 'false');
            if (toggleText) toggleText.textContent = 'Pause';
        }
    });

    /* --------------------------------------------------------- webgl layer */
    let sceneRequested = false;

    async function loadScene() {
        if (sceneRequested) return;
        sceneRequested = true;

        const canvas = root.querySelector('[data-adv-canvas]');

        if (!canvas || !supportsWebgl()) return;

        try {
            const { AdventureScene } = await import('./scene.js');
            scene = new AdventureScene(canvas, view, order);
            root.dataset.advMode = 'webgl';
        } catch (error) {
            // The HTML layer already carries the architecture, so a failure
            // here degrades to the static diagram rather than to nothing.
            root.dataset.advMode = 'html';
            console.warn('API adventure: scene unavailable', error);
        }
    }

    // Resizing changes every anchor the curves were built from.
    let resizeTimer = 0;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            // Crossing the stacked/row breakpoint changes the order the
            // providers are reached, which is baked into the timeline.
            const next = serviceOrder(root);

            if (next.join() !== order.join()) {
                order = next;
                const elapsed = timeline.elapsed;
                timeline = new Timeline(duration, replayDelay, order);
                timeline.elapsed = elapsed;
                view.reset();
            }

            scene?.resize(order);
        }, 150);
    });

    // Switching to reduced motion mid-visit should be honoured immediately.
    reduceMotion.addEventListener('change', (event) => {
        if (!event.matches) return;

        stop();
        scene?.dispose();
        scene = null;
        view.settle(timeline);
        root.dataset.advMode = 'static';
    });
}

function supportsWebgl() {
    try {
        const probe = document.createElement('canvas');

        return Boolean(probe.getContext('webgl2') || probe.getContext('webgl'));
    } catch {
        return false;
    }
}
