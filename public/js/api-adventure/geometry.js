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
        turnX: Math.min(width - 26, ports['central-api'].right + 40),
        returnX: 26,
        entryX: -60,
        portalX: ports.portal ? ports.portal.cx : width - 60,
        branchX: (id) => ports[id].cx,
        // Half the pipe, so the runner can stand on top of it rather than in it.
        pipeHalf: ports.products.h * 0.5,
    };
}
