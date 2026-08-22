/* ========================================================================
   The WebGL layer.

   One renderer, one orthographic camera mapped 1:1 to CSS pixels with +y
   down, so everything here shares the DOM's coordinate system and the route
   can be built straight from getBoundingClientRect.

   This layer is decorative by contract: scene.js is dynamically imported and
   every caller treats its absence as normal. It draws the energy in the
   pipes, the packets, the runner and the arrival — never any text.
   ======================================================================== */

import * as THREE from '../vendor/three.module.min.js';
import { buildRoute, buildBranch, makePipe, TONES } from './pipeline.js';
import { Packets, Burst } from './particles.js';
import { Runner } from './runner.js';

const SERVICES = ['ai', 'whatsapp', 'billing'];

export class AdventureScene {
    /**
     * @param {HTMLCanvasElement} canvas
     * @param {import('./stages.js').StageView} view supplies the DOM geometry
     */
    constructor(canvas, view, order) {
        this.canvas = canvas;
        this.view = view;
        this.order = order;

        this.renderer = new THREE.WebGLRenderer({
            canvas,
            alpha: true,
            antialias: true,
            powerPreference: 'low-power',
        });
        // Beyond 2x the cost doubles for a difference nobody can see.
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        this.scene = new THREE.Scene();
        this.camera = new THREE.OrthographicCamera(0, 1, 0, 1, -100, 100);

        this.runner = new Runner();
        this.packets = new Packets();
        this.burst = new Burst();

        this.scene.add(this.runner.mesh, this.packets.points, this.burst.points);

        /** @type {THREE.Mesh|null} */
        this.mainPipe = null;
        /** @type {Record<string, THREE.Mesh>} */
        this.branchPipes = {};
        this.route = null;
        this.burstFired = false;

        this.build();
    }

    /** (Re)build everything that depends on layout. */
    build() {
        const { ports, boxes, width, height } = this.view.anchors();

        // A layout that has not settled yet — retry on the next resize.
        if (!ports.products || !ports.ai || width < 2) return;

        this.disposeGeometry();

        this.width = width;
        this.height = height;
        this.renderer.setSize(width, height, false);

        // Screen space: x right, y down, origin top-left.
        this.camera.left = 0;
        this.camera.right = width;
        this.camera.top = 0;
        this.camera.bottom = height;
        this.camera.updateProjectionMatrix();

        this.route = buildRoute(ports, { width, height }, boxes, this.order);

        // Each stage's window becomes a coloured band, so the pipe keeps a
        // record of the journey: blue where it was connected, amber through
        // the gate, violet through the API, green out to the providers.
        const bands = Object.entries(this.route.windows)
            .sort((a, b) => a[1].to - b[1].to)
            .map(([id, w]) => ({ to: w.to, tone: TONES[id] ?? TONES.products }));

        this.mainPipe = makePipe(this.route.curve, bands, { radius: 3.4 });
        this.scene.add(this.mainPipe);

        // The stacked route runs through the providers directly, so there is
        // nothing to branch off.
        for (const id of this.route.stacked ? [] : SERVICES) {
            if (!ports[id]) continue;

            const pipe = makePipe(
                buildBranch(ports[id], this.route.fanY),
                [{ to: 1, tone: TONES[id] }],
                { radius: 2.8, segments: 40 },
            );
            this.branchPipes[id] = pipe;
            this.scene.add(pipe);
        }
    }

    /** @param {string[]} [order] the provider order for the new layout */
    resize(order) {
        if (order) this.order = order;
        this.build();
    }

    reset() {
        this.burstFired = false;
        this.burst.reset();
    }

    /**
     * @param {import('./timeline.js').Timeline} timeline
     * @param {number} dt
     */
    render(timeline, dt) {
        if (!this.route) {
            this.build();
            if (!this.route) return;
        }

        const beat = timeline.current;
        const window_ = this.route.windows[beat.id];
        const { local, state } = timeline.stageAt(beat.id);

        // Where the request is along the whole route, in curve space.
        const u = window_
            ? window_.from + (window_.to - window_.from) * (state === 'done' ? 1 : local)
            : timeline.progress;
        const head = Math.max(0, Math.min(1, u));

        const point = this.route.curve.getPointAt(head);
        const tone = TONES[beat.id] ?? TONES.products;

        // The main pipe lights behind the head, in the colour of the stage the
        // request is currently in — the dot turning amber at the gate and
        // violet inside the API is the same idea the CSS version had.
        this.mainPipe.material.uniforms.uHead.value = head;

        this.packets.setTone(tone);
        this.packets.follow(this.route.curve, head);

        // Each branch fills only while its own service beat is running, which
        // is what staggers the fan-out.
        for (const id of SERVICES) {
            const pipe = this.branchPipes[id];
            if (!pipe) continue;

            const s = timeline.stageAt(id);
            pipe.material.uniforms.uHead.value = s.state === 'done' ? 1 : s.state === 'active' ? s.local : 0;
        }

        this.runner.update(point, dt, this.runnerState(timeline, beat, local));

        if (timeline.complete && !this.burstFired) {
            this.burstFired = true;
            this.burst.fire(point.x, point.y);
        }

        if (!timeline.complete) {
            this.burstFired = false;
        }

        this.burst.update(dt);
        this.renderer.render(this.scene, this.camera);
    }

    /** The pose, derived from where in the route the request is. */
    runnerState(timeline, beat, local) {
        if (timeline.complete) return 'success';

        // Crossing a pipe body is a tuck; the gaps between them are a run, and
        // the elbow down into the fan lane reads as a jump.
        if (beat.id === 'signature' && local > 0.25 && local < 0.75) return 'pipe';
        if (beat.id === 'central-api' && local > 0.7) return 'jump';
        if (SERVICES.includes(beat.id)) return 'pipe';
        // The rail runs behind the signed-event card, so the request is
        // genuinely inside something here: tuck until it comes out the far
        // side heading for the portal.
        if (beat.id === 'webhook' && local > 0.3 && local < 0.72) return 'pipe';

        return 'run';
    }

    disposeGeometry() {
        if (this.mainPipe) {
            this.scene.remove(this.mainPipe);
            this.mainPipe.geometry.dispose();
            this.mainPipe.material.dispose();
            this.mainPipe = null;
        }

        for (const [id, pipe] of Object.entries(this.branchPipes)) {
            this.scene.remove(pipe);
            pipe.geometry.dispose();
            pipe.material.dispose();
            delete this.branchPipes[id];
        }
    }

    /** Everything allocated here is released; the canvas is left in the DOM. */
    dispose() {
        this.disposeGeometry();
        this.runner.dispose();
        this.packets.dispose();
        this.burst.dispose();
        this.scene.clear();
        this.renderer.dispose();
    }
}
