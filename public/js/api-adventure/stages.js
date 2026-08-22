/* ========================================================================
   The HTML half of the adventure.

   Everything a visitor needs to understand the architecture is applied here:
   which stage is lit, what its status says, how far the journey has run. The
   canvas layer is decorative on top of this, so if WebGL is unavailable or
   the user prefers reduced motion, the section still tells the whole story.
   ======================================================================== */

const PENDING = 0;
const ACTIVE = 1;
const DONE = 2;

export class StageView {
    /** @param {HTMLElement} root the [data-adventure] element */
    constructor(root) {
        this.root = root;
        this.stage = root.querySelector('.adv-stage');

        /** @type {Map<string, {el: HTMLElement, status: HTMLElement|null, labels: string[], applied: number}>} */
        this.nodes = new Map();

        root.querySelectorAll('[data-adv-node]').forEach((el) => {
            const status = el.querySelector('[data-adv-status] b');
            const labels = (el.querySelector('[data-adv-status]')?.dataset.advStates ?? '').split('|');

            this.nodes.set(el.dataset.advNode, { el, status, labels, applied: -1 });
        });

        this.scoreEl = root.querySelector('[data-adv-score]');
        this.timerEl = root.querySelector('[data-adv-timer]');
        this.barEl = root.querySelector('[data-adv-bar]');
        this.barFill = root.querySelector('[data-adv-bar-fill]');
        this.progressEl = root.querySelector('[data-adv-progress]');
        this.journeyEl = root.querySelector('[data-adv-journey]');

        this.journeyPhases = ['Connected', 'Secured', 'Processing', 'Delivered'];
        this.lastScore = -1;
        this.lastPercent = -1;
        this.lastPhase = null;
    }

    /**
     * Positions of every pipe, in CSS pixels relative to the stage box.
     *
     * The scene derives its curves from these rather than from hard-coded
     * numbers, so the route follows the CSS at any width and the two layers
     * cannot drift apart.
     */
    anchors() {
        const base = this.stage.getBoundingClientRect();
        const out = {};

        this.root.querySelectorAll('[data-adv-port]').forEach((el) => {
            const r = el.getBoundingClientRect();

            out[el.dataset.advPort] = {
                x: r.left - base.left,
                y: r.top - base.top,
                w: r.width,
                h: r.height,
                right: r.right - base.left,
                bottom: r.bottom - base.top,
                cx: r.left - base.left + r.width / 2,
                cy: r.top - base.top + r.height / 2,
            };
        });

        // Node boxes include the label chip above each pipe, which the fan
        // lane has to clear.
        const boxes = {};

        this.root.querySelectorAll('[data-adv-node]').forEach((el) => {
            const r = el.getBoundingClientRect();
            boxes[el.dataset.advNode] = { top: r.top - base.top, bottom: r.bottom - base.top };
        });

        const portal = this.root.querySelector('[data-adv-portal]');

        if (portal) {
            const r = portal.getBoundingClientRect();
            out.portal = {
                cx: r.left - base.left + r.width / 2,
                cy: r.top - base.top + r.height / 2,
                w: r.width,
                h: r.height,
            };
        }

        return { ports: out, boxes, width: base.width, height: base.height };
    }

    /** @param {import('./timeline.js').Timeline} timeline */
    render(timeline) {
        for (const [id, node] of this.nodes) {
            const { state } = timeline.stageAt(id);
            const next = state === 'done' ? DONE : state === 'active' ? ACTIVE : PENDING;

            // Writing only on change keeps this off the layout path on the
            // frames where nothing about the stage has actually moved.
            if (node.applied === next) continue;
            node.applied = next;

            node.el.classList.toggle('is-active', next === ACTIVE);
            node.el.classList.toggle('is-done', next === DONE);

            if (node.status && node.labels[next]) {
                node.status.textContent = node.labels[next];
            }
        }

        const score = timeline.score;

        if (score !== this.lastScore) {
            this.lastScore = score;
            if (this.scoreEl) this.scoreEl.textContent = score.toLocaleString('en-US');
        }

        const percent = Math.round(timeline.progress * 100);

        if (percent !== this.lastPercent) {
            this.lastPercent = percent;
            if (this.barFill) this.barFill.style.width = `${percent}%`;
            if (this.barEl) this.barEl.setAttribute('aria-valuenow', String(percent));
            if (this.barEl) this.barEl.classList.toggle('is-done', percent === 100);
            if (this.progressEl) this.progressEl.textContent = `${percent}%`;
            if (this.timerEl) this.timerEl.textContent = formatClock(timeline.elapsed);
        }

        const phase = timeline.phase;

        if (phase !== this.lastPhase) {
            this.lastPhase = phase;
            this.paintJourney(phase);
        }
    }

    /** The journey line reads as a breadcrumb, with the live phase emphasised. */
    paintJourney(phase) {
        if (!this.journeyEl) return;

        this.journeyEl.innerHTML = this.journeyPhases
            .map((p) => (p === phase ? `<b>${p}</b>` : p))
            .join(' &rarr; ');
    }

    /** Put every stage back to pending without waiting for a frame. */
    reset() {
        for (const node of this.nodes.values()) {
            node.applied = -1;
            node.el.classList.remove('is-active', 'is-done');
            if (node.status && node.labels[PENDING]) node.status.textContent = node.labels[PENDING];
        }

        this.lastScore = this.lastPercent = -1;
        this.lastPhase = null;
    }

    /**
     * The end state, for visitors who will never see the animation: every
     * stage delivered, progress full. Used for reduced motion.
     */
    settle(timeline) {
        for (const [id, node] of this.nodes) {
            void id;
            node.applied = DONE;
            node.el.classList.remove('is-active');
            node.el.classList.add('is-done');
            if (node.status && node.labels[DONE]) node.status.textContent = node.labels[DONE];
        }

        if (this.scoreEl) this.scoreEl.textContent = timeline.totalScore.toLocaleString('en-US');
        if (this.barFill) this.barFill.style.width = '100%';
        if (this.barEl) {
            this.barEl.setAttribute('aria-valuenow', '100');
            this.barEl.classList.add('is-done');
        }
        if (this.progressEl) this.progressEl.textContent = '100%';
        if (this.timerEl) this.timerEl.textContent = formatClock(timeline.duration);
        this.paintJourney('Delivered');
    }
}

function formatClock(seconds) {
    const s = Math.max(0, Math.floor(seconds));

    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
}

/**
 * The order the current layout reaches the three providers.
 *
 * Wide: they sit in a row and the fan lane arrives from the right, so the
 * rightmost is first. Stacked: they run top to bottom, so document order is
 * the order. Read from the DOM rather than from a breakpoint constant, so the
 * two never disagree.
 *
 * @param {HTMLElement} root
 * @returns {string[]}
 */
export function serviceOrder(root) {
    const nodes = [...root.querySelectorAll('[data-adv-row="service"]')].map((el) => ({
        id: el.dataset.advNode,
        rect: el.getBoundingClientRect(),
    }));

    if (nodes.length < 2) return nodes.map((n) => n.id);

    const stacked = Math.abs(nodes[1].rect.top - nodes[0].rect.top) > 20;

    return stacked
        ? nodes.sort((a, b) => a.rect.top - b.rect.top).map((n) => n.id)
        : nodes.sort((a, b) => b.rect.left - a.rect.left).map((n) => n.id);
}
