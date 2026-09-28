import { fanfare, tick } from './audio';
import { reducedMotion, timeline } from './timeline';

const SPIN = 6600;

/**
 * The wheel. The server draws the slices; this only turns it so the pointer
 * stops on the slice of the winner it already chose.
 */
export default ({ slices, winner, attempt, landed }) => ({
    ...timeline,
    timers: [],
    rotation: 0,
    spinning: false,
    done: false,

    init() {
        const target = this.stopAt(slices, winner, attempt);

        if (landed) {
            this.rotation = target;
            this.done = true;

            return;
        }

        if (reducedMotion()) {
            this.rotation = target;
            this.later(900, () => this.stop());

            return;
        }

        this.later(80, () => {
            this.spinning = true;
            this.rotation = target;
        });

        // Pegs go by fast at first and slower as the wheel loses speed.
        for (let peg = 0; peg < 46; peg++) {
            this.later(100 + Math.pow(peg / 46, 2.1) * (SPIN - 500), tick);
        }

        this.later(SPIN + 200, () => this.stop());
    },

    stop() {
        fanfare();
        this.done = true;
        this.reachedTheEnd(attempt);
    },

    /**
     * The pointer is at the top (270° in SVG) and slices start at 0°: turning
     * the wheel by 270 − the middle of the slice brings it under the pointer,
     * after six full turns. The small offset is the same on every screen.
     */
    stopAt(slices, winner, attempt) {
        const slice = 360 / slices;
        const offset = (((attempt * 9301 + 49297) % 233280) / 233280 - 0.5) * slice * 0.45;

        return 360 * 6 + (270 - (winner + 0.5) * slice) + offset;
    },

    get style() {
        return {
            transform: `rotate(${this.rotation}deg)`,
            transition: this.spinning ? `transform ${SPIN}ms cubic-bezier(0.12, 0.45, 0.06, 1)` : 'none',
        };
    },
});
