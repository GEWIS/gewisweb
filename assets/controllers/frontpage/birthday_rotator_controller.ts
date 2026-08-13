import { Controller } from '@hotwired/stimulus';

/**
 * Rotates between the photos of the members whose birthday it is, cross-fading from one to the next so the panel is
 * about all of them rather than about whoever happens to have been tagged most.
 *
 * Every image is rendered by the server and all but the first are hidden, so a reader without JavaScript still sees
 * one of them. With nothing to rotate between (no photos, or only one) this does nothing at all.
 *
 *   - the images: data-birthday-rotator-target="photo"
 */
export default class extends Controller {
    static targets = ['photo'];
    static values = {
        interval: { type: Number, default: 10000 },
    };

    declare readonly photoTargets: HTMLElement[];
    declare readonly intervalValue: number;

    private index = 0;
    private timer?: ReturnType<typeof setInterval>;

    connect(): void {
        if (this.photoTargets.length < 2) {
            return;
        }

        this.timer = setInterval(() => this.advance(), this.intervalValue);
    }

    disconnect(): void {
        if (this.timer === undefined) {
            return;
        }

        clearInterval(this.timer);
        this.timer = undefined;
    }

    private advance(): void {
        const current = this.photoTargets[this.index];
        this.index = (this.index + 1) % this.photoTargets.length;
        const next = this.photoTargets[this.index];

        next.classList.add('is-visible');
        current.classList.remove('is-visible');
    }
}
