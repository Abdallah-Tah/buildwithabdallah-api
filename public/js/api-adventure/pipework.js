/* ========================================================================
   Lays the pipe artwork along the route.

   The runs are DOM elements tiling a repeatable pipe body; the fittings are
   images of real elbows and tees. Positioning happens here rather than in
   CSS because the coordinates come from measured machine positions, which is
   the only way the artwork and the request path stay on the same line.

   Nothing is stretched. A run's width changes, but the body tiles into it at
   its natural height, and every fitting keeps its own aspect ratio. Fittings
   are placed by their ports, so a run always meets a hole rather than a
   flange, and each run stops at the fitting's edge rather than under it.
   ======================================================================== */

import { SERVICES, fitting } from './geometry.js';

/** @param {HTMLElement} root @param {object} geo */
export function layoutPipework(root, geo) {
    const pipes = root.querySelector('.adv-pipes');

    if (!pipes) return;

    // Stacked layout drops the horizontal pipework entirely: the vertical run
    // is short enough that connectors would be more clutter than structure.
    pipes.hidden = geo.stacked;

    if (geo.stacked) return;

    /** A horizontal run between two x positions, centred on y. */
    const run = (selector, x, y, w) => {
        const el = pipes.querySelector(selector);

        if (!el) return;

        el.style.left = `${Math.round(x)}px`;
        el.style.top = `${Math.round(y)}px`;
        el.style.width = `${Math.max(0, Math.round(w))}px`;
        el.style.height = '';
    };

    const vertical = (selector, x, y, h) => {
        const el = pipes.querySelector(selector);

        if (!el) return;

        el.style.left = `${Math.round(x)}px`;
        el.style.top = `${Math.round(y)}px`;
        el.style.width = '';
        el.style.height = `${Math.max(0, Math.round(h))}px`;
        el.dataset.advVertical = 'true';
    };

    /**
     * A fitting whose ports land on (x, y).
     * @returns {object} its measured box, so the runs can stop at its edges.
     */
    const fit = (selector, name, x, y) => {
        const box = fitting(name, x, y);
        const el = pipes.querySelector(selector);

        if (el) {
            el.style.left = `${Math.round(box.cx)}px`;
            el.style.top = `${Math.round(box.cy)}px`;
            el.style.transform = `translate(-50%, -50%) rotate(${box.rotate}deg)`;
        }

        return box;
    };

    const { ports, mainY, fanY, serviceY, railY, turnX, returnX, portalX } = geo;

    // Row one: one run from off-screen left into the mouth of the corner
    // elbow, passing behind all three machines. The run stops at the elbow's
    // flange, so the opening reads as an opening rather than as more pipe.
    const drop = fit('.adv-mouth', 'drop', turnX, mainY);
    run('.adv-run--main', geo.entryX, mainY, drop.left - geo.entryX);

    // Down the turn column into the branch lane.
    const turn = fit('.adv-fit--turn', 'turn', turnX, fanY);
    vertical('.adv-run--descent', turnX, drop.bottom, turn.top - drop.bottom);

    // The lane, right to left, with a tee dropping into each machine.
    const back = fit('.adv-fit--return', 'return', returnX, fanY);
    run('.adv-run--fan', back.right, fanY, turn.left - back.right);

    SERVICES.forEach((id) => {
        const tee = fit(`[data-adv-tee="${id}"]`, 'tee', ports[id].cx, fanY);
        vertical(`[data-adv-branch="${id}"]`, ports[id].cx, tee.bottom, serviceY - tee.bottom);
    });

    // Down the left edge and along the final rail into the portal.
    vertical('.adv-run--tail', returnX, back.bottom, railY - back.bottom);
    run('.adv-run--final', returnX, railY, portalX - returnX);
}
