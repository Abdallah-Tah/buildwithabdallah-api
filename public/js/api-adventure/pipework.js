/* ========================================================================
   Lays the pipe artwork along the route.

   The runs are DOM elements tiling a repeatable pipe body; the fittings are
   images of real elbows and tees. Positioning happens here rather than in
   CSS because the coordinates come from measured machine positions, which is
   the only way the artwork and the request path stay on the same line.

   Nothing is stretched. A run's width changes, but the body tiles into it at
   its natural height, and every fitting keeps its own aspect ratio.
   ======================================================================== */

import { SERVICES } from './geometry.js';

/** @param {HTMLElement} root @param {object} geo */
export function layoutPipework(root, geo) {
    const pipes = root.querySelector('.adv-pipes');

    if (!pipes) return;

    // Stacked layout drops the horizontal pipework entirely: the vertical run
    // is short enough that connectors would be more clutter than structure.
    pipes.hidden = geo.stacked;

    if (geo.stacked) return;

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

    // Fittings are placed by their centre so a run can meet them at the seam.
    const fit = (selector, cx, cy, rotate = 0) => {
        const el = pipes.querySelector(selector);

        if (!el) return;

        el.style.left = `${Math.round(cx)}px`;
        el.style.top = `${Math.round(cy)}px`;
        el.style.transform = `translate(-50%, -50%) rotate(${rotate}deg)`;
    };

    const { ports, mainY, fanY, serviceY, railY, turnX, returnX, portalX } = geo;
    const fitHalf = 34;

    // Row one: one run from off-screen left to the turn column, passing behind
    // all three machines.
    run('.adv-run--main', geo.entryX, mainY, turnX - geo.entryX - fitHalf);
    fit('.adv-fit--drop', turnX, mainY, 0);
    vertical('.adv-run--descent', turnX, mainY + fitHalf, fanY - mainY - fitHalf * 2);
    fit('.adv-fit--turn', turnX, fanY, 0);

    // The fan lane, right to left, with a tee dropping into each machine.
    run('.adv-run--fan', returnX + fitHalf, fanY, turnX - returnX - fitHalf * 2);

    SERVICES.forEach((id, i) => {
        const x = ports[id].cx;
        fit(`[data-adv-tee="${id}"]`, x, fanY, 180);
        vertical(`[data-adv-branch="${id}"]`, x, fanY + fitHalf * 0.6, serviceY - fanY - fitHalf * 0.6);
    });

    // Down the left edge and along the final rail into the portal.
    fit('.adv-fit--return', returnX, fanY, 0);
    vertical('.adv-run--tail', returnX, fanY + fitHalf, railY - fanY - fitHalf * 1.6);
    run('.adv-run--final', returnX, railY, portalX - returnX);
}
