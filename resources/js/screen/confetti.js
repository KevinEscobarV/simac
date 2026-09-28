import { reducedMotion } from './timeline';

// Gold weighs more than the rest: it is the color of the celebration.
const COLORS = ['#2f8963', '#f2b72f', '#e0685f', '#4a9be0', '#9b6be0', '#e8913c', '#f2b72f', '#f5c955', '#ffffff', '#f2b72f'];

const SHAPES = [
    { ratio: 0.55, radius: '1px' },
    { ratio: 1, radius: '50%' },
    { ratio: 3.2, radius: '1px', ribbon: true },
];

/**
 * Confetti for the winner: 150 pieces that fall once.
 */
export default () => ({
    pieces: [],

    init() {
        if (reducedMotion()) {
            return;
        }

        this.pieces = Array.from({ length: 150 }, (_, index) => {
            const shape = SHAPES[index % SHAPES.length];
            const width = shape.ribbon ? 4 + Math.random() * 3 : 7 + Math.random() * 8;
            const color = COLORS[Math.floor(Math.random() * COLORS.length)];

            return {
                fall: `left: ${Math.random() * 100}%; animation: confetti-fall ${3 + Math.random() * 2.6}s cubic-bezier(0.35, 0, 0.65, 1) ${Math.random() * 1.4}s forwards`,
                piece: `width: ${width}px; height: ${width * shape.ratio}px; background: ${color}; border-radius: ${shape.radius}; box-shadow: 0 0 6px ${color}55; animation: confetti-spin ${1.1 + Math.random() * 1.6}s linear infinite`,
            };
        });
    },
});
