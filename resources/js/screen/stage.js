import { unlockAudio } from './audio';

const BEAT = 15000;
const IDLE_CONTROLS = 3000;

/**
 * The stage itself: full screen (button or F key), controls and cursor that
 * hide when nobody touches the computer, the sound browsers only allow after
 * a gesture, the heartbeat that lets the console count this screen, and a
 * notice when the live updates are lost (the stage then asks the server
 * every few seconds, so it keeps up anyway).
 */
export default () => ({
    controls: true,
    sound: false,
    connected: true,
    hideControls: null,

    init() {
        this.showControls();
        window.addEventListener('mousemove', () => this.showControls());
        window.addEventListener('pointerdown', () => this.enableSound());
        window.addEventListener('keydown', (event) => {
            this.enableSound();

            if (event.key === 'f' || event.key === 'F') {
                this.toggleFullscreen();
            }
        });

        const screen = this.screenId();
        this.$wire.beat(screen);
        setInterval(() => this.$wire.beat(screen), BEAT);

        this.followConnection();
    },

    showControls() {
        this.controls = true;
        clearTimeout(this.hideControls);
        this.hideControls = setTimeout(() => (this.controls = false), IDLE_CONTROLS);
    },

    enableSound() {
        unlockAudio();
        this.sound = true;
    },

    toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen?.();
        } else {
            document.documentElement.requestFullscreen?.().catch(() => {});
        }
    },

    /** One id per tab: a reload does not count as another screen. */
    screenId() {
        const fresh = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 10);

        try {
            const id = sessionStorage.getItem('simac.screen') ?? fresh();
            sessionStorage.setItem('simac.screen', id);

            return id;
        } catch {
            return fresh();
        }
    },

    followConnection() {
        const connection = window.Echo?.connector?.pusher?.connection;

        if (connection) {
            let wasConnected = connection.state === 'connected';

            connection.bind('state_change', ({ current }) => {
                if (current === 'connected') {
                    wasConnected = true;
                    this.connected = true;
                } else if (wasConnected || ['unavailable', 'failed'].includes(current)) {
                    this.connected = false;
                }
            });
        } else {
            this.connected = false;
        }

        setInterval(() => {
            if (!this.connected) {
                this.$wire.$refresh();
            }
        }, 5000);
    },
});
