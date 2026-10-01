import { fanfare, tick } from './audio';
import { reducedMotion, timeline } from './timeline';

const SPIN = 6600;

/**
 * The wheel. The server draws the slices and works out where it stops, with
 * the winner it already chose under the pointer; this only turns it there.
 */
export default ({ target, attempt, landed }) => ({
    ...timeline,
    timers: [],
    rotation: 0,
    spinning: false,
    done: false,

    init() {
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

    get style() {
        return {
            transform: `rotate(${this.rotation}deg)`,
            transition: this.spinning ? `transform ${SPIN}ms cubic-bezier(0.12, 0.45, 0.06, 1)` : 'none',
        };
    },
});
