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

/**
 * The route the request travels, built from the shared geometry so it lies
 * exactly on the pipe artwork rather than near it.
 *
 * @param {object} geo from geometry()
 * @param {string[]} order the providers in the order this layout reaches them
 */
export function buildRoute(geo, order) {
    if (geo.stacked) {
        return buildStackedRoute(geo, order);
    }

    const { ports, mainY, fanY, serviceY, railY, turnX, returnX, entryX, portalX } = geo;

    const waypoints = [
        { p: [entryX, mainY] },
        { p: [ports.products.cx, mainY] },
        { p: [ports.products.right - 6, mainY], at: 'products' },
        { p: [ports.signature.cx, mainY] },
        { p: [ports.signature.right - 6, mainY], at: 'signature' },
        { p: [ports['central-api'].cx, mainY] },
        { p: [turnX, mainY] },
        { p: [turnX, mainY + (fanY - mainY) * 0.55] },
        { p: [turnX, fanY] },
    ];

    // Down the lane, reaching each provider in the order the layout meets it.
    order.forEach((id, i) => {
        waypoints.push({ p: [ports[id].cx, fanY], at: i === 0 ? 'central-api' : order[i - 1] });
    });
    waypoints.push({ p: [returnX + 10, fanY], at: order[order.length - 1] });

    // Left edge down to the rail, then straight into the portal. The rail
    // carries extra collinear points because a spline through two distant
    // ends bows away from the line between them.
    waypoints.push(
        { p: [returnX, fanY + (railY - fanY) * 0.4] },
        { p: [returnX, railY - 20] },
        { p: [returnX + 30, railY] },
        { p: [geo.width * 0.38, railY] },
        { p: [geo.width * 0.66, railY] },
        { p: [portalX, railY], at: 'webhook' },
    );

    return finish(waypoints);
}

/** The stacked layout: one chain down the page through every machine. */
function buildStackedRoute(geo, order) {
    const { ports, width } = geo;
    const sequence = ['products', 'signature', 'central-api', ...order, 'webhook'];
    const waypoints = [];

    sequence.forEach((id, i) => {
        const port = ports[id];

        if (!port) return;

        const weave = i % 2 === 0 ? -width * 0.15 : width * 0.15;

        if (i === 0) waypoints.push({ p: [width / 2, -50] });
        else waypoints.push({ p: [width / 2 + weave, port.y - 18] });

        // Offset from centre: the sign board is centred on the machine, and a
        // runner riding the middle would stand on its own label.
        waypoints.push({ p: [port.cx + port.w * 0.3, port.y + port.h * 0.46], at: id });
    });

    return { ...finish(waypoints), stacked: true };
}

/** Turn marked waypoints into a curve plus a per-stage window along it. */
function finish(waypoints) {
    const points = waypoints.map((w) => new THREE.Vector3(w.p[0], w.p[1], 0));
    const curve = new THREE.CatmullRomCurve3(points, false, 'centripetal', 0.12);

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

    return { curve, windows };
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
