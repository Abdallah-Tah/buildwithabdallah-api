/* ========================================================================
   The route, derived from the DOM.

   Every point here comes from a getBoundingClientRect on a pipe the CSS laid
   out, so the energy in the canvas follows the diagram at any width and the
   two layers cannot drift. Nothing in this file hard-codes a position.

   The camera is orthographic and set to screen pixels with +y pointing down,
   so these coordinates are DOM coordinates — no conversion anywhere.
   ======================================================================== */

import * as THREE from '../vendor/three.module.min.js';

/** Bright head that travels the pipe, with everything behind it left lit. */
const PIPE_VERT = `
    varying vec2 vUv;
    void main() {
        vUv = uv;
        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
    }
`;

const PIPE_FRAG = `
    #define SEGMENTS 8

    uniform float uHead;
    uniform float uBounds[SEGMENTS];
    uniform vec3  uTones[SEGMENTS];
    uniform int   uCount;
    uniform vec3  uPending;
    uniform float uAlpha;
    varying vec2 vUv;

    /* The colour of the stage that owns this stretch of pipe. Without this the
       whole traversed length would take the colour of wherever the request is
       now, and the amber gate would turn green the moment Stripe answered. */
    vec3 toneAt(float x) {
        for (int i = 0; i < SEGMENTS; i++) {
            if (i >= uCount) break;
            if (x <= uBounds[i]) return uTones[i];
        }

        return uTones[0];
    }

    void main() {
        float travelled = step(vUv.x, uHead);
        vec3 colour = mix(uPending, toneAt(vUv.x), travelled);

        float band = exp(-pow((vUv.x - uHead) * 34.0, 2.0));
        colour += band * 0.75;

        float ends = smoothstep(0.0, 0.012, vUv.x) * smoothstep(1.0, 0.988, vUv.x);

        gl_FragColor = vec4(colour, uAlpha * (0.26 + travelled * 0.28 + band * 0.55) * ends);
    }
`;

export const TONES = {
    products: 0x3d7fff,
    signature: 0xf5a524,
    'central-api': 0xa855f7,
    ai: 0x34d399,
    whatsapp: 0x34d399,
    billing: 0x34d399,
    webhook: 0x3d7fff,
};

/* The unlit pipe wall: dark enough to read as metal under additive blending. */
const PENDING_TONE = 0x1b2540;

/**
 * One continuous run from off-screen left to the portal, plus a drop into
 * each service. The master curve is what the runner rides; the branches are
 * what the service packets ride.
 */
export function buildRoute(ports, size, boxes = {}, order = ['billing', 'whatsapp', 'ai']) {
    // Stacked layout: the row has straightened into a vertical run, so the
    // route is a chain down the page rather than an S across it.
    if (Math.abs(ports.signature.cy - ports.products.cy) > 20) {
        return buildStackedRoute(ports, size, order);
    }

    const mainY = ports.products.cy;

    // Clear the label chips: the lane sits in the gap above the service row,
    // not at a fixed offset from the pipes, so a longer label cannot collide
    // with it.
    const serviceTop = Math.min(
        ...['ai', 'whatsapp', 'billing']
            .map((id) => boxes[id]?.top)
            .filter((v) => typeof v === 'number'),
        ports.ai.y - 44,
    );
    const fanY = Math.max(ports['central-api'].bottom + 26, serviceTop - 16);
    const railY = ports.webhook ? ports.webhook.cy : size.height - 60;
    const elbowX = Math.min(size.width - 26, ports['central-api'].right + 96);
    const fanEndX = branchX(ports.ai) - ports.ai.w * 0.62;
    const portalX = ports.portal ? ports.portal.cx : size.width - 60;

    // Marked waypoints: `at` names the stage whose window ends there, which is
    // how a timeline beat is mapped onto a stretch of curve further down.
    const waypoints = [
        { p: [-70, mainY] },
        { p: [ports.products.cx, mainY] },
        { p: [ports.products.right + 8, mainY], at: 'products' },
        { p: [ports.signature.cx, mainY] },
        { p: [ports.signature.right + 8, mainY], at: 'signature' },
        { p: [ports['central-api'].cx, mainY] },
        { p: [ports['central-api'].right + 10, mainY] },
        // Elbow out to the right, then down into the fan lane.
        { p: [elbowX, mainY] },
        { p: [elbowX, mainY + (fanY - mainY) * 0.55] },
        { p: [elbowX, fanY] },
        { p: [branchX(ports.billing), fanY], at: 'central-api' },
        { p: [branchX(ports.whatsapp), fanY], at: 'billing' },
        { p: [branchX(ports.ai), fanY], at: 'whatsapp' },
        { p: [fanEndX, fanY], at: 'ai' },
        // Down the left edge and along the final rail to the portal. The rail
        // carries extra collinear points on purpose: a spline through two
        // widely spaced ends bows away from the straight line between them,
        // and the bow would cut diagonally across the event card.
        { p: [22, fanY + (railY - fanY) * 0.34] },
        { p: [22, railY - 26] },
        { p: [46, railY] },
        { p: [size.width * 0.34, railY] },
        { p: [size.width * 0.62, railY] },
        { p: [portalX, railY], at: 'webhook' },
    ];

    const points = waypoints.map((w) => new THREE.Vector3(w.p[0], w.p[1], 0));
    const curve = new THREE.CatmullRomCurve3(points, false, 'centripetal', 0.12);

    // Chord lengths approximate arc length closely enough to split the curve
    // into per-stage windows, and cost nothing next to sampling the spline.
    const cumulative = [0];
    for (let i = 1; i < points.length; i++) {
        cumulative.push(cumulative[i - 1] + points[i].distanceTo(points[i - 1]));
    }
    const total = cumulative[cumulative.length - 1];

    /** @type {Record<string, {from: number, to: number}>} */
    const windows = {};
    let from = 0;

    waypoints.forEach((w, i) => {
        if (!w.at) return;
        const to = cumulative[i] / total;
        windows[w.at] = { from, to };
        from = to;
    });

    return { curve, windows, fanY, railY };
}

/**
 * The same journey, read top to bottom.
 *
 * Each pipe is entered from above and left from below, with a small alternating
 * horizontal offset so consecutive drops are visibly separate segments rather
 * than one straight line down the middle.
 */
function buildStackedRoute(ports, size, order) {
    const sequence = ['products', 'signature', 'central-api', ...order, 'webhook'];
    const waypoints = [];
    const x = size.width / 2;

    sequence.forEach((id, i) => {
        const port = ports[id];

        if (!port) return;

        const weave = i % 2 === 0 ? -size.width * 0.16 : size.width * 0.16;

        if (i === 0) waypoints.push({ p: [x, -50] });
        else waypoints.push({ p: [x + weave, port.y - 22] });

        waypoints.push({ p: [port.cx, port.cy], at: id });
    });

    const points = waypoints.map((w) => new THREE.Vector3(w.p[0], w.p[1], 0));
    const curve = new THREE.CatmullRomCurve3(points, false, 'centripetal', 0.14);

    const cumulative = [0];
    for (let i = 1; i < points.length; i++) {
        cumulative.push(cumulative[i - 1] + points[i].distanceTo(points[i - 1]));
    }
    const total = cumulative[cumulative.length - 1] || 1;

    const windows = {};
    let from = 0;

    waypoints.forEach((w, i) => {
        if (!w.at) return;
        const to = cumulative[i] / total;
        windows[w.at] = { from, to };
        from = to;
    });

    // No separate lane to branch off, so the drops are part of the main run.
    return { curve, windows, fanY: null, railY: null, stacked: true };
}

/**
 * Where a branch leaves the lane and enters its pipe. Off-centre because the
 * label chip is centred, and a drop through the middle would strike it.
 */
export function branchX(port) {
    return port.cx + port.w * 0.3;
}

/** A short vertical drop from the fan lane into one service pipe. */
export function buildBranch(port, fanY) {
    const x = branchX(port);

    return new THREE.CatmullRomCurve3([
        new THREE.Vector3(x, fanY, 0),
        new THREE.Vector3(x, fanY + (port.cy - fanY) * 0.5, 0),
        new THREE.Vector3(x, port.cy, 0),
    ], false, 'centripetal', 0.2);
}

/**
 * A glowing tube along a curve. Radius is in screen pixels because the camera
 * is pixel-scaled, so this is effectively a thick line that antialiases well.
 */
/**
 * @param {Array<{to: number, tone: number}>} bands stretches of the tube and
 *        the colour each keeps once the request has passed through it. A
 *        single-colour pipe passes one band ending at 1.
 */
export function makePipe(curve, bands, { radius = 3.2, segments = 220, alpha = 1 } = {}) {
    const geometry = new THREE.TubeGeometry(curve, segments, radius, 8, false);
    const bounds = new Array(8).fill(1);
    const tones = Array.from({ length: 8 }, () => new THREE.Color(0xffffff));

    bands.slice(0, 8).forEach((band, i) => {
        bounds[i] = band.to;
        tones[i].setHex(band.tone);
    });

    const material = new THREE.ShaderMaterial({
        uniforms: {
            uHead: { value: 0 },
            uBounds: { value: bounds },
            uTones: { value: tones },
            uCount: { value: Math.min(bands.length, 8) },
            uPending: { value: new THREE.Color(PENDING_TONE) },
            uAlpha: { value: alpha },
        },
        vertexShader: PIPE_VERT,
        fragmentShader: PIPE_FRAG,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });

    return new THREE.Mesh(geometry, material);
}
