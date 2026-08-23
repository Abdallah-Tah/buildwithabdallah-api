/* ========================================================================
   Where the route runs, in stage pixels.

   One definition, consumed twice: the DOM pipework lays its artwork along
   these lines, and the WebGL route builds its curve from the same numbers.
   Two sources would drift the moment a breakpoint changed, and a packet
   travelling beside its pipe is worse than no packet at all.

   Everything is derived from measured element boxes, so the composition
   follows the CSS at any width.
   ======================================================================== */

export const SERVICES = ['ai', 'whatsapp', 'billing'];

/** Mirrors `--adv-pipe-h`. */
const PIPE_H = 40;

/**
 * The pipe fittings, measured off the artwork.
 *
 * Every fitting is rendered `FIT_H` tall (2.35x the pipe, per `.adv-fit`) and
 * keeps its aspect. `ax` is where its vertical port sits across the sprite's
 * width and `ay` where its horizontal port sits down its height — after the
 * rotation the CSS applies. Placing a fitting by its centre instead, as a
 * plain image would be, leaves the runs meeting it twenty or thirty pixels
 * wide of the holes they are supposed to enter.
 */
export const FIT_H = PIPE_H * 2.35;

export const FITTINGS = {
    // left <-> bottom: row one turns down into the branch lane.
    drop:   { w: 288, h: 238, ax: 0.7691, ay: 0.2815, rotate: 0 },
    // left <-> top: the descent turns into the lane heading back left.
    turn:   { w: 250, h: 242, ax: 0.7640, ay: 0.7335, rotate: 0 },
    // right <-> bottom: the lane turns down the left edge to the rail.
    return: { w: 272, h: 226, ax: 0.2298, ay: 0.2987, rotate: 0 },
    // Flipped, so its branch drops instead of rising.
    tee:    { w: 392, h: 222, ax: 0.5064, ay: 0.2815, rotate: 180 },
};

/** The rendered box of a fitting whose ports must land on (x, y). */
export function fitting(name, x, y) {
    const f = FITTINGS[name];
    const w = FIT_H * (f.w / f.h);

    return {
        w,
        h: FIT_H,
        rotate: f.rotate,
        cx: x - w * (f.ax - 0.5),
        cy: y - FIT_H * (f.ay - 0.5),
        left: x - w * f.ax,
        right: x + w * (1 - f.ax),
        top: y - FIT_H * f.ay,
        bottom: y + FIT_H * (1 - f.ay),
    };
}

/**
 * @param {{ports: Record<string, DOMRectLike>, boxes: Record<string, {top:number,bottom:number}>, width: number, height: number}} anchors
 * @returns {null|object} null when the layout has not settled yet
 */
export function geometry(anchors) {
    const { ports, boxes, width, height } = anchors;

    if (!ports.products || !ports.ai || !ports.webhook || width < 2) {
        return null;
    }

    const stacked = Math.abs(ports.signature.cy - ports.products.cy) > 20;

    // The upper stages are bare capsules, so their centre is the centreline.
    // The service machines are full sprites and their barrel sits higher.
    const centre = (port) => port.y + port.h * 0.5;
    const barrel = (port) => port.y + port.h * 0.44;

    if (stacked) {
        return { stacked: true, width, height, ports };
    }

    const mainY = centre(ports.products);
    // Far enough right that the elbow clears the last capsule, near enough
    // left that its outer curve stays inside the frame.
    const drop = FIT_H * (FITTINGS.drop.w / FITTINGS.drop.h);
    const turn = Math.min(
        width - drop * (1 - FITTINGS.drop.ax) - 8,
        ports['central-api'].right + 100,
    );
    const serviceY = barrel(ports.ai);
    const serviceTop = Math.min(...SERVICES.map((id) => boxes[id]?.top ?? ports[id].y));
    // The whole node, not the machine: the detail and status sit below the
    // artwork and the lane has to clear them.
    const mainBottom = Math.max(
        ...['products', 'signature', 'central-api'].map((id) => boxes[id]?.bottom ?? ports[id].bottom),
    );

    return {
        stacked: false,
        width,
        height,
        ports,
        mainY,
        serviceY,
        // The lane sits in the gap above the service machines.
        fanY: Math.max(mainBottom + 26, serviceTop - 40),
        railY: ports.webhook.y + ports.webhook.h * 0.5,
        // Turn column just inside the right edge, and the return leg just
        // inside the left.
        turnX: turn,
        returnX: 26,
        entryX: -60,
        portalX: ports.portal ? ports.portal.cx : width - 60,
        branchX: (id) => ports[id].cx,
        // Half the bare pipe run, so the runner stands on top of it rather
        // than in it. Mirrors `--adv-pipe-h`.
        pipeHalf: PIPE_H * 0.5,
        // The bore of that elbow: where the runner is swallowed. The flange
        // opening sits about a tenth of the way into the sprite.
        mouthX: turn - drop * (FITTINGS.drop.ax - 0.084),
    };
}
