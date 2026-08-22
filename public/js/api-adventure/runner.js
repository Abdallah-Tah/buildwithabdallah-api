/* ========================================================================
   The request, drawn as a small original mascot.

   Frames are painted to offscreen canvases once at boot and reused as
   textures, so the run cycle costs a texture swap per frame rather than any
   per-frame drawing. The character is deliberately built from plain shapes:
   swapping in a real sprite sheet later means replacing makeFrames() and
   nothing else.
   ======================================================================== */

import * as THREE from '../vendor/three.module.min.js';

const SIZE = 104;          // texture is square; the sprite is scaled on the plane
const FRAME_RATE = 11;    // run cycle frames per second

/** States the runner can be in. Each maps to a slice of the frame list. */
export const STATES = {
    idle: [0],
    run: [1, 2, 3, 4],
    jump: [5],
    pipe: [6],
    success: [7],
};

export class Runner {
    constructor() {
        this.frames = makeFrames().map((canvas) => {
            const texture = new THREE.CanvasTexture(canvas);
            texture.colorSpace = THREE.SRGBColorSpace;
            texture.minFilter = THREE.LinearFilter;
            // The camera puts +y downward, which flips the rendered image, so
            // the texture is flipped back rather than the mascot arriving
            // upside down.
            texture.flipY = false;

            return texture;
        });

        this.material = new THREE.MeshBasicMaterial({
            map: this.frames[1],
            transparent: true,
            depthWrite: false,
            // That same y-flip reverses triangle winding, so a single-sided
            // plane is culled as a back face and never appears at all.
            side: THREE.DoubleSide,
        });

        this.mesh = new THREE.Mesh(new THREE.PlaneGeometry(68, 68), this.material);
        this.mesh.renderOrder = 10;

        this.state = 'run';
        this.clock = 0;
        this.opacity = 1;
    }

    /**
     * @param {THREE.Vector3} point where on the route the request currently is
     * @param {number} dt seconds since the last frame
     * @param {string} state one of STATES
     */
    update(point, dt, state) {
        this.state = state in STATES ? state : 'run';
        this.clock += dt;

        const frames = STATES[this.state];
        const index = frames[Math.floor(this.clock * FRAME_RATE) % frames.length];

        if (this.material.map !== this.frames[index]) {
            this.material.map = this.frames[index];
            this.material.needsUpdate = true;
        }

        // A small vertical bob on the run cycle, and none while inside a pipe.
        const bob = this.state === 'run' ? Math.sin(this.clock * FRAME_RATE * Math.PI) * 2.2 : 0;

        // Sits on the pipe rather than above it: any higher and it collides
        // with the label chip on the stage it is crossing.
        this.mesh.position.set(point.x, point.y - 24 + bob, 1);
        this.mesh.material.opacity = this.opacity;
    }

    dispose() {
        this.frames.forEach((t) => t.dispose());
        this.material.dispose();
        this.mesh.geometry.dispose();
    }
}

/**
 * Eight frames: idle, four run frames, jump, in-pipe and success.
 *
 * Everything is drawn from arcs and rounded rectangles — no external asset,
 * nothing traced from an existing character.
 */
function makeFrames() {
    return [
        (c) => drawPose(c, { lean: 0, armA: -0.3, armB: 0.3, legA: 0.1, legB: -0.1, lift: 0 }),
        (c) => drawPose(c, { lean: 0.16, armA: -1.1, armB: 0.9, legA: 0.9, legB: -0.7, lift: 0 }),
        (c) => drawPose(c, { lean: 0.2, armA: -0.4, armB: 0.4, legA: 0.2, legB: -0.2, lift: 3 }),
        (c) => drawPose(c, { lean: 0.16, armA: 0.9, armB: -1.1, legA: -0.7, legB: 0.9, lift: 0 }),
        (c) => drawPose(c, { lean: 0.2, armA: 0.4, armB: -0.4, legA: -0.2, legB: 0.2, lift: 3 }),
        (c) => drawPose(c, { lean: 0.28, armA: -1.4, armB: -1.2, legA: 0.8, legB: 0.4, lift: 7 }),
        (c) => drawPose(c, { lean: 0.5, armA: -1.5, armB: -1.5, legA: 0.3, legB: 0.3, lift: 2, tuck: true }),
        (c) => drawPose(c, { lean: -0.1, armA: -2.3, armB: -2.3, legA: 0.1, legB: -0.1, lift: 4, cheer: true }),
    ].map((paint) => {
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = SIZE;
        paint(canvas.getContext('2d'));

        return canvas;
    });
}

const SKIN = '#f2caa4';
const HAIR = '#161d2e';
const COAT = '#357bf0';
const COAT_DARK = '#2560c4';
const PANTS = '#48547a';
const SHOE = '#f2f6ff';
const SCARF = '#5f9bff';

/**
 * Proportions are deliberately chunky — at 64 CSS pixels a realistic figure
 * turns to mush, so the head is oversized and the limbs are thick enough to
 * stay legible while they move.
 */
function drawPose(ctx, { lean, armA, armB, legA, legB, lift, tuck = false, cheer = false }) {
    const cx = SIZE / 2;
    const cy = SIZE / 2 + 10 - lift;
    const legLength = tuck ? 13 : 19;

    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate(lean * 0.16);
    ctx.lineCap = 'round';

    // The mascot is always over a lit pipe, so it needs its own separation.
    ctx.shadowColor = 'rgba(4, 8, 18, .85)';
    ctx.shadowBlur = 5;

    // Scarf, streaming behind the direction of travel.
    ctx.fillStyle = SCARF;
    ctx.beginPath();
    ctx.moveTo(-6, -16);
    ctx.quadraticCurveTo(-19, -19 - lean * 5, -26, -9 + lean * 10);
    ctx.quadraticCurveTo(-16, -10, -6, -8);
    ctx.closePath();
    ctx.fill();

    // Back leg and arm first so the torso overlaps them.
    limb(ctx, 0, 8, legB, legLength, PANTS, 8);
    foot(ctx, 8, legB, legLength);
    limb(ctx, 0, -6, armB, 16, COAT_DARK, 7);

    // Torso.
    ctx.fillStyle = COAT;
    roundRect(ctx, -11, -16, 22, 26, 9);
    ctx.fill();
    ctx.fillStyle = COAT_DARK;
    roundRect(ctx, -11, 2, 22, 8, 5);
    ctx.fill();

    // Front leg and arm.
    limb(ctx, 0, 8, legA, legLength, PANTS, 8);
    foot(ctx, 8, legA, legLength);
    limb(ctx, 0, -6, armA, 16, COAT, 7);

    // Head.
    ctx.fillStyle = SKIN;
    ctx.beginPath();
    ctx.arc(2, -26, 11, 0, Math.PI * 2);
    ctx.fill();

    // Hair: a cap over the crown plus a sweep at the back.
    ctx.fillStyle = HAIR;
    ctx.beginPath();
    ctx.arc(2, -28, 11.2, Math.PI * 0.98, Math.PI * 2.08);
    ctx.fill();
    ctx.beginPath();
    ctx.ellipse(-7, -26, 4.5, 6.5, -0.35, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#101627';
    ctx.beginPath();
    ctx.arc(8, -25, 1.9, 0, Math.PI * 2);
    ctx.fill();

    // A grin on the success pose only.
    if (cheer) {
        ctx.strokeStyle = '#101627';
        ctx.lineWidth = 1.7;
        ctx.beginPath();
        ctx.arc(6, -21, 3.6, 0.2, Math.PI - 0.2);
        ctx.stroke();
    }

    ctx.restore();
}

function limb(ctx, ox, oy, angle, length, colour, width) {
    ctx.save();
    ctx.translate(ox, oy);
    ctx.rotate(angle);
    ctx.strokeStyle = colour;
    ctx.lineWidth = width;
    ctx.beginPath();
    ctx.moveTo(0, 0);
    ctx.lineTo(0, length);
    ctx.stroke();
    ctx.restore();
}

function foot(ctx, oy, angle, length) {
    ctx.save();
    ctx.translate(Math.sin(-angle) * length, oy + Math.cos(angle) * length);
    ctx.rotate(angle * 0.3);
    ctx.fillStyle = SHOE;
    roundRect(ctx, -6, -2.5, 12, 5.5, 2.6);
    ctx.fill();
    ctx.restore();
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}
