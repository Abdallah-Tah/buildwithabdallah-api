/* ========================================================================
   The runner, drawn from the generated sprite sheets.

   Frames arrive as one horizontal strip per state, every frame the same size
   with the feet on a shared baseline (see scripts/extract_map.py). That means
   animating is a texture offset, the character never changes size between
   frames, and it never bounces because a tight crop changed shape.

   States are set by the scene, never by this class: it knows how to draw a
   pose, not when the request is inside a pipe.
   ======================================================================== */

import * as THREE from '../vendor/three.module.min.js';
import { adventureAssets } from './assets.js';

const FRAME_RATE = 13;

/**
 * Plane height in stage pixels. The character occupies 150 of the 168px frame,
 * so the figure itself reads about 86px — large enough to find immediately
 * without competing with the machines it runs across.
 */
export const RUNNER_HEIGHT = 96;

export class Runner {
    constructor(loader) {
        /** @type {Record<string, {texture: THREE.Texture, frames: number}>} */
        this.states = {};

        for (const [state, entry] of Object.entries(adventureAssets.runner)) {
            const texture = loader.load(entry.src);
            texture.colorSpace = THREE.SRGBColorSpace;
            texture.minFilter = THREE.LinearFilter;
            texture.magFilter = THREE.LinearFilter;
            texture.generateMipmaps = false;
            // Show one frame of the strip at a time.
            texture.repeat.set(1 / entry.frames, 1);
            texture.wrapS = THREE.RepeatWrapping;

            this.states[state] = { texture, frames: entry.frames };
        }

        this.material = new THREE.MeshBasicMaterial({
            map: this.states.run.texture,
            transparent: true,
            depthWrite: false,
            // The camera puts +y downward, which reverses winding; a
            // single-sided plane would be culled entirely.
            side: THREE.DoubleSide,
        });

        this.mesh = new THREE.Mesh(new THREE.PlaneGeometry(RUNNER_HEIGHT, RUNNER_HEIGHT), this.material);
        this.mesh.renderOrder = 10;
        this.mesh.scale.y = -1;

        this.state = 'run';
        this.clock = 0;
        this.visible = true;
    }

    setState(state) {
        if (state === this.state || !this.states[state]) return;

        this.state = state;
        this.clock = 0;
        this.material.map = this.states[state].texture;
        this.material.needsUpdate = true;
    }

    /**
     * @param {THREE.Vector3} point where the request is on the route
     * @param {number} dt seconds since the previous frame
     */
    /** @param {number} lift half the pipe height, so the feet land on its top */
    update(point, dt, lift = 0) {
        this.clock += dt;

        const { texture, frames } = this.states[this.state];
        // Idle and success read better held; running has to cycle.
        const index = Math.floor(this.clock * FRAME_RATE) % frames;
        texture.offset.x = index / frames;

        // Feet on top of the pipe, not inside it.
        this.mesh.position.set(point.x, point.y - lift - RUNNER_HEIGHT * 0.4, 2);
        this.mesh.visible = this.visible;
    }

    dispose() {
        for (const { texture } of Object.values(this.states)) texture.dispose();
        this.material.dispose();
        this.mesh.geometry.dispose();
    }
}
