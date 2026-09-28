/**
 * The projection stage: its Alpine components, registered before Livewire
 * starts Alpine.
 */
import confetti from './screen/confetti';
import drum from './screen/drum';
import reveal from './screen/reveal';
import stage from './screen/stage';
import wheel from './screen/wheel';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('stage', stage);
    window.Alpine.data('wheel', wheel);
    window.Alpine.data('drum', drum);
    window.Alpine.data('reveal', reveal);
    window.Alpine.data('confetti', confetti);
});
