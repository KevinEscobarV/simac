/**
 * Sounds made with WebAudio: nothing to download. Browsers keep audio
 * blocked until someone interacts with the page, so the stage unlocks it on
 * the first click or key press.
 */
let context = null;

export function unlockAudio() {
    try {
        context ??= new (window.AudioContext || window.webkitAudioContext)();

        if (context.state === 'suspended') {
            context.resume();
        }
    } catch {
        context = null;
    }
}

function running() {
    return context !== null && context.state === 'running';
}

/** The click of a wheel peg or a name going by. */
export function tick() {
    if (!running()) {
        return;
    }

    const oscillator = context.createOscillator();
    const gain = context.createGain();

    oscillator.type = 'square';
    oscillator.frequency.value = 950;
    gain.gain.setValueAtTime(0.04, context.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.05);

    oscillator.connect(gain).connect(context.destination);
    oscillator.start();
    oscillator.stop(context.currentTime + 0.06);
}

/** A rising arpeggio for the winner: C, E, G, C. */
export function fanfare() {
    if (!running()) {
        return;
    }

    [523.25, 659.25, 783.99, 1046.5].forEach((frequency, index) => {
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        const start = context.currentTime + index * 0.13;

        oscillator.type = 'triangle';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.12, start + 0.03);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.55);

        oscillator.connect(gain).connect(context.destination);
        oscillator.start(start);
        oscillator.stop(start + 0.6);
    });
}
