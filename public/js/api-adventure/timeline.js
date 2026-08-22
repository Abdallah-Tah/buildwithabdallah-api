/* ========================================================================
   The journey as a deterministic state machine.

   One clock, one ordered list of beats. Everything visible — stage lighting,
   status text, score, progress — is derived from elapsed time, so the same
   moment always looks the same and a paused frame is never mid-transition.
   No setTimeout anywhere: a dropped frame or a backgrounded tab resumes at
   the right place instead of drifting.
   ======================================================================== */

/**
 * Beats are declared as weights, not seconds, so retiming the whole journey
 * is one number in config and the proportions survive.
 */
const BEATS = [
    { id: 'products',    weight: 2.4, phase: 'Connected',  score: 1200 },
    { id: 'signature',   weight: 3.0, phase: 'Secured',    score: 2400 },
    { id: 'central-api', weight: 3.0, phase: 'Processing', score: 2400 },
    // The fan-out is staggered so the eye can follow one branch at a time.
    //
    // Order here is the order the route physically reaches them, not the order
    // they sit in the markup: the pipe leaves the central API on the right and
    // runs back leftward, so Billing is nearest. The three calls are parallel
    // in the real system, so any order is honest — but the runner and the
    // status that lights have to agree, or the request appears to jump back.
    { id: 'billing',     weight: 1.9, phase: 'Processing', score: 1600 },
    { id: 'whatsapp',    weight: 1.9, phase: 'Processing', score: 1600 },
    { id: 'ai',          weight: 1.9, phase: 'Processing', score: 1600 },
    { id: 'webhook',     weight: 3.2, phase: 'Delivered',  score: 2040 },
];

export const STAGE_IDS = BEATS.map((b) => b.id);

export const SERVICE_IDS = ['ai', 'whatsapp', 'billing'];

export class Timeline {
    /**
     * @param {number} duration seconds for one complete journey
     * @param {number} replayDelay seconds the finished state holds before replay
     * @param {string[]} [serviceOrder] the three provider beats, in the order
     *        the current layout reaches them. Wide screens run the fan lane
     *        right to left; stacked screens run top to bottom. The order has to
     *        follow the layout or a status lights while the request is
     *        somewhere else entirely.
     */
    constructor(duration, replayDelay, serviceOrder = null) {
        this.duration = duration;
        this.replayDelay = replayDelay;

        const beats = serviceOrder ? reorderServices(BEATS, serviceOrder) : BEATS;
        const total = beats.reduce((sum, b) => sum + b.weight, 0);
        let at = 0;

        /** Each beat resolved to an absolute [start, end] window in seconds. */
        this.beats = beats.map((beat) => {
            const span = (beat.weight / total) * duration;
            const resolved = { ...beat, start: at, end: at + span, span };
            at += span;

            return resolved;
        });

        this.totalScore = beats.reduce((sum, b) => sum + b.score, 0);
        this.reset();
    }

    reset() {
        this.elapsed = 0;
        this.finishedAt = null;
    }

    /** @param {number} dt seconds since the previous frame */
    advance(dt) {
        if (this.finishedAt !== null) {
            this.finishedAt += dt;

            if (this.finishedAt >= this.replayDelay) {
                this.reset();
            }

            return;
        }

        this.elapsed += dt;

        if (this.elapsed >= this.duration) {
            this.elapsed = this.duration;
            this.finishedAt = 0;
        }
    }

    get complete() {
        return this.finishedAt !== null;
    }

    /** 0..1 across the whole journey. */
    get progress() {
        return this.duration > 0 ? Math.min(this.elapsed / this.duration, 1) : 1;
    }

    /**
     * The state of one stage right now.
     *
     * `local` is 0..1 within the stage's own window, which is what the scene
     * uses to place the runner, so the caller never has to know the schedule.
     *
     * @returns {{state: 'pending'|'active'|'done', local: number}}
     */
    stageAt(id) {
        const beat = this.beats.find((b) => b.id === id);

        if (!beat) {
            return { state: 'pending', local: 0 };
        }

        if (this.elapsed < beat.start) {
            return { state: 'pending', local: 0 };
        }

        if (this.elapsed >= beat.end) {
            return { state: 'done', local: 1 };
        }

        return { state: 'active', local: (this.elapsed - beat.start) / beat.span };
    }

    /** The beat the runner is inside, or the last one once the journey ends. */
    get current() {
        return this.beats.find((b) => this.elapsed < b.end) ?? this.beats[this.beats.length - 1];
    }

    get phase() {
        return this.current.phase;
    }

    /** Score accrues continuously so the HUD counts up rather than jumping. */
    get score() {
        return Math.round(
            this.beats.reduce((sum, beat) => {
                if (this.elapsed >= beat.end) return sum + beat.score;
                if (this.elapsed <= beat.start) return sum;

                return sum + beat.score * ((this.elapsed - beat.start) / beat.span);
            }, 0),
        );
    }
}

/**
 * Rewrite the three provider beats into `order`, leaving every other beat and
 * every weight exactly where it was. Only the identities move, so the timing
 * of the journey is unchanged.
 */
function reorderServices(beats, order) {
    const slots = [];
    beats.forEach((b, i) => { if (SERVICE_IDS.includes(b.id)) slots.push(i); });

    const wanted = order.filter((id) => SERVICE_IDS.includes(id));

    if (wanted.length !== slots.length) return beats;

    const out = beats.slice();
    slots.forEach((slot, i) => {
        const source = beats.find((b) => b.id === wanted[i]);
        out[slot] = { ...beats[slot], id: source.id };
    });

    return out;
}
