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

    // The machines are tall images; the pipe belongs at the barrel, not at
    // the centre of the whole sprite.
    const barrel = (port) => port.y + port.h * 0.44;

    if (stacked) {
        return { stacked: true, width, height, ports };
    }

    const mainY = barrel(ports.products);
    const serviceY = barrel(ports.ai);
    const serviceTop = Math.min(...SERVICES.map((id) => boxes[id]?.top ?? ports[id].y));

    return {
        stacked: false,
        width,
        height,
        ports,
        mainY,
        serviceY,
        // The lane sits in the gap above the service machines.
        fanY: Math.max(ports['central-api'].bottom - 8, serviceTop - 46),
        railY: ports.webhook.y + ports.webhook.h * 0.5,
        // Turn column just inside the right edge, and the return leg just
        // inside the left.
        turnX: Math.min(width - 26, ports['central-api'].right + 40),
        returnX: 26,
        entryX: -60,
        portalX: ports.portal ? ports.portal.cx : width - 60,
        branchX: (id) => ports[id].cx,
    };
}
