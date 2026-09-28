import { fanfare, tick } from './audio';
import { reducedMotion, timeline } from './timeline';

const LETTER = 85;

/**
 * 3, 2, 1… and the name appears letter by letter.
 */
export default ({ name, attempt }) => ({
    ...timeline,
    timers: [],
    count: 3,
    letters: 0,

    init() {
        if (reducedMotion()) {
            this.count = 0;
            this.letters = name.length;
            this.later(900, () => this.stop());

            return;
        }

        [3, 2, 1].forEach((count, second) => {
            this.later(second * 1000, () => {
                this.count = count;
                tick();
            });
        });

        this.later(3000, () => (this.count = 0));

        for (let letter = 1; letter <= name.length; letter++) {
            this.later(3050 + letter * LETTER, () => {
                this.letters = letter;

                if (name[letter - 1] !== ' ') {
                    tick();
                }
            });
        }

        this.later(3050 + name.length * LETTER + 400, () => this.stop());
    },

    stop() {
        fanfare();
        this.reachedTheEnd(attempt);
    },

    get shown() {
        return name.slice(0, this.letters);
    },

    get complete() {
        return this.letters >= name.length;
    },
});
