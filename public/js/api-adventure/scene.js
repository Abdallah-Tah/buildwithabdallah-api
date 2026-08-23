/* ========================================================================
   The WebGL layer.

   One renderer, one orthographic camera mapped 1:1 to CSS pixels with +y
   down, so everything shares the DOM's coordinate system and the route can be
   built straight from measured element boxes.

   This layer draws the request moving and the effects around it. It draws no
   text and no structure: the machines and pipes are DOM images, the labels
   and statuses are HTML. It is dynamically imported and every caller treats
   its absence as normal.
   ======================================================================== */

import * as THREE from '../vendor/three.module.min.js';
import { adventureAssets } from './assets.js';
import { geometry, SERVICES } from './geometry.js';
import { buildRoute, TONES } from './pipeline.js';
import { Packets, Burst } from './particles.js';
import { Runner, RUNNER_HEIGHT } from './runner.js';
import { layoutPipework } from './pipework.js';

const clamp = (v) => Math.max(0, Math.min(1, v));

/** A sprite that rides the route, tinted by the stage the request is in. */
class Orb {
    constructor(loader, size = 38) {
        const texture = loader.load(adventureAssets.effects.orbNeutral.src);
        texture.colorSpace = THREE.SRGBColorSpace;

        this.material = new THREE.MeshBasicMaterial({
            map: texture,
            transparent: true,
            depthWrite: false,
            side: THREE.DoubleSide,
            blending: THREE.AdditiveBlending,
        });
        this.texture = texture;
        this.mesh = new THREE.Mesh(new THREE.PlaneGeometry(size * 1.7, size), this.material);
        this.mesh.renderOrder = 9;
        this.mesh.scale.y = -1;
    }

    update(point, tone, alpha, pulse) {
        this.mesh.visible = alpha > 0.02;
        this.mesh.position.set(point.x, point.y, 1.5);
        this.material.color.setHex(tone);
        this.material.opacity = alpha;
        const s = 1 + Math.sin(pulse * 7) * 0.06;
        this.mesh.scale.set(s, -s, 1);
    }

    dispose() {
        this.texture.dispose();
        this.material.dispose();
        this.mesh.geometry.dispose();
    }
}

/** The expanding ring at the portal. */
class Shockwave {
    constructor(loader) {
        const texture = loader.load(adventureAssets.effects.ripple.src);
        texture.colorSpace = THREE.SRGBColorSpace;

        this.material = new THREE.MeshBasicMaterial({
            map: texture, transparent: true, depthWrite: false,
            side: THREE.DoubleSide, blending: THREE.AdditiveBlending, opacity: 0,
        });
        this.texture = texture;
        this.mesh = new THREE.Mesh(new THREE.PlaneGeometry(220, 150), this.material);
        this.mesh.renderOrder = 11;
        this.mesh.visible = false;
        this.life = -1;
    }

    fire(x, y) {
        this.mesh.position.set(x, y, 1);
        this.mesh.visible = true;
        this.life = 0;
    }

    update(dt) {
        if (this.life < 0) return;

        this.life += dt;

        if (this.life > 1.3) {
            this.life = -1;
            this.mesh.visible = false;

            return;
        }

        const t = this.life / 1.3;
        const scale = 0.4 + t * 1.6;
        this.mesh.scale.set(scale, -scale, 1);
        this.material.opacity = 1 - t;
    }

    reset() { this.life = -1; this.mesh.visible = false; }

    dispose() {
        this.texture.dispose();
        this.material.dispose();
        this.mesh.geometry.dispose();
    }
}

export class AdventureScene {
    constructor(canvas, view, order) {
        this.canvas = canvas;
        this.view = view;
        this.order = order;

        this.renderer = new THREE.WebGLRenderer({
            canvas, alpha: true, antialias: true, powerPreference: 'low-power',
        });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        this.scene = new THREE.Scene();
        this.camera = new THREE.OrthographicCamera(0, 1, 0, 1, -100, 100);

        const loader = new THREE.TextureLoader();
        this.runner = new Runner(loader);
        this.orb = new Orb(loader);
        this.packets = new Packets();
        this.burst = new Burst();
        this.shock = new Shockwave(loader);

        this.scene.add(
            this.runner.mesh, this.orb.mesh, this.packets.points,
            this.burst.points, this.shock.mesh,
        );

        this.route = null;
        this.geo = null;
        this.lift = 0;
        this.burstFired = false;
        this.clock = 0;

        this.robot = view.root.querySelector('[data-adv-robot]');
        this.robotIdle = this.robot?.src;
        this.robotWave = this.robot?.dataset.advRobotWave;

        this.build();
    }

    build() {
        const geo = geometry(this.view.anchors());

        if (!geo) return;

        this.geo = geo;
        this.renderer.setSize(geo.width, geo.height, false);
        this.camera.left = 0;
        this.camera.right = geo.width;
        this.camera.top = 0;
        this.camera.bottom = geo.height;
        this.camera.updateProjectionMatrix();

        // The artwork and the route come from the same geometry, in that order.
        layoutPipework(this.view.root, geo);
        this.route = buildRoute(geo, this.order);
    }

    resize(order) {
        if (order) this.order = order;
        this.build();
    }

    reset() {
        this.burstFired = false;
        this.burst.reset();
        this.shock.reset();
        this.setRobot(false);
    }

    setRobot(waving) {
        if (!this.robot || !this.robotWave) return;

        const src = waving ? this.robotWave : this.robotIdle;

        if (!this.robot.src.endsWith(src.split('/').pop())) {
            this.robot.src = src;
        }
    }

    render(timeline, dt) {
        if (!this.route) {
            this.build();
            if (!this.route) return;
        }

        this.clock += dt;

        const beat = timeline.current;
        const window_ = this.route.windows[beat.id];
        const { local, state } = timeline.stageAt(beat.id);
        const head = Math.max(0, Math.min(1, window_
            ? window_.from + (window_.to - window_.from) * (state === 'done' ? 1 : local)
            : timeline.progress));

        const point = this.route.curve.getPointAt(head);
        const tone = TONES[beat.id] ?? TONES.products;

        // The top pipe and the final rail are floor: the character runs them.
        // Everything between is inside the plumbing, where the request is a
        // packet. The two are joined by the mouth rather than by a visibility
        // swap — the runner is seen going in, and seen coming back out.
        const { zone, fade } = this.footing(point, beat);
        const onFloor = zone !== 'pipe' && !timeline.complete;

        this.runner.visible = onFloor;
        this.runner.fade = fade;
        this.runner.setState(this.runnerState(timeline, beat, local, zone, fade));
        // The surface under the feet changes as the character steps up onto a
        // module and back down onto bare pipe; easing it keeps that a step
        // rather than a jolt.
        const lift = this.liftAt(point);
        this.lift += (lift - this.lift) * Math.min(1, dt * 14);
        this.runner.update(point, dt, this.lift);

        const orbAlpha = timeline.complete ? 1 : 1 - fade;
        this.orb.update(point, tone, onFloor ? orbAlpha : 1, this.clock);
        this.packets.setTone(tone);
        this.packets.follow(this.route.curve, head);
        // The wake stays lit on both surfaces; it is the pipe carrying charge,
        // not a stand-in for the character.
        this.packets.visible = !timeline.complete;

        if (timeline.complete && !this.burstFired) {
            this.burstFired = true;
            this.burst.fire(point.x, point.y);
            this.shock.fire(point.x, point.y);
        }

        if (!timeline.complete) this.burstFired = false;

        // The robot acknowledges its own delivery, then settles.
        this.setRobot(timeline.stageAt('ai').state === 'active');

        this.burst.update(dt);
        this.shock.update(dt);
        this.renderer.render(this.scene, this.camera);
    }

    /**
     * Which surface the request is on, and how much of the character is still
     * outside the plumbing.
     *
     * @returns {{zone: 'main'|'rail'|'pipe', fade: number}} fade is 1 in the
     *          open and 0 fully inside a pipe.
     */
    footing(point, beat) {
        const g = this.geo;

        if (!g || g.stacked) {
            return { zone: this.overMachine(point) ? 'main' : 'pipe', fade: 1 };
        }

        const LANE = 30;
        const SWALLOW = 96;
        // Row one: floor from off-screen left all the way into the mouth.
        if (Math.abs(point.y - g.mainY) < LANE && point.x <= g.mouthX + 6) {
            return { zone: 'main', fade: clamp((g.mouthX - point.x) / SWALLOW) };
        }

        // The final rail: back out of the left elbow and on to the portal.
        if (Math.abs(point.y - g.railY) < LANE && point.x >= g.returnX - 6) {
            return { zone: 'rail', fade: clamp((point.x - g.returnX) / SWALLOW) };
        }

        return { zone: 'pipe', fade: 0 };
    }

    /**
     * How far above the route the surface underfoot is: the top of the module
     * being crossed, or the top of the bare pipe between them.
     */
    liftAt(point) {
        const ports = this.geo?.ports ?? {};

        for (const port of Object.values(ports)) {
            if (typeof port.w !== 'number') continue;

            if (point.x > port.x && point.x < port.right
                && point.y > port.y - 6 && point.y < port.bottom + 6) {
                return point.y - port.y;
            }
        }

        return this.geo?.pipeHalf ?? 0;
    }

    /** True when the request is over a machine rather than inside a pipe. */
    overMachine(point) {
        const ports = this.geo?.ports ?? {};

        for (const port of Object.values(ports)) {
            if (typeof port.w !== 'number') continue;

            if (point.x > port.x + port.w * 0.12 && point.x < port.right - port.w * 0.12
                && point.y > port.y - RUNNER_HEIGHT && point.y < port.bottom) {
                return true;
            }
        }

        return false;
    }

    runnerState(timeline, beat, local, zone, fade) {
        if (timeline.complete) return 'success';
        if (zone === 'pipe') return 'enterPipe';
        // Going in, and coming back out. Both are poses the sheet has.
        if (fade < 0.999) return zone === 'main' ? 'enterPipe' : 'exitPipe';
        // A jump just before the portal, so the arrival reads as one.
        if (beat.id === 'webhook' && local > 0.86) return 'jump';
        if (beat.id === 'signature' && local > 0.3 && local < 0.7) return 'idle';
        if (SERVICES.includes(beat.id)) return 'idle';

        return 'run';
    }

    dispose() {
        this.runner.dispose();
        this.orb.dispose();
        this.packets.dispose();
        this.burst.dispose();
        this.shock.dispose();
        this.scene.clear();
        this.renderer.dispose();
    }
}
