/**
 * What every animation shares: timers that die with it, the reduced motion
 * preference and the report that it reached the end. Each animation declares
 * its own `timers: []`, so no two share the list.
 */
export const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export const timeline = {
    later(milliseconds, callback) {
        this.timers.push(setTimeout(callback, milliseconds));
    },

    destroy() {
        this.timers.forEach(clearTimeout);
    },

    /** The stage reports it to the server, which makes the winner public. */
    reachedTheEnd(attempt) {
        this.$dispatch('animation-finished', { attempt });
    },
};
