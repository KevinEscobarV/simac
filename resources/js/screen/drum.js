import { fanfare, tick } from './audio';
import { reducedMotion, timeline } from './timeline';

const STEPS = 44;

/**
 * The name drum: names go by, fast and then slower, until the winner's.
 */
export default ({ names, winner, attempt, landed }) => ({
    ...timeline,
    timers: [],
    index: winner,
    stopped: false,

    init() {
        if (landed || names.length === 0) {
            this.stopped = true;

            return;
        }

        if (reducedMotion()) {
            this.later(900, () => this.stop());

            return;
        }

        let step = 0;
        let index = (attempt * 7) % names.length;
        this.index = index;

        const next = () => {
            step++;

            if (step >= STEPS) {
                this.stop();

                return;
            }

            index = (index + 1) % names.length;
            this.index = index;
            tick();
            this.later(45 + Math.pow(step / STEPS, 3) * 560, next);
        };

        this.later(500, next);
    },

    stop() {
        this.index = winner;
        this.stopped = true;
        fanfare();
        this.reachedTheEnd(attempt);
    },

    /** The name the given number of rows away from the middle one. */
    at(offset) {
        const count = names.length;

        return names[(((this.index + offset) % count) + count) % count];
    },
});
