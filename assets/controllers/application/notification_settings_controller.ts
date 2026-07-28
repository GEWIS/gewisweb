import { Controller } from '@hotwired/stimulus';

/**
 * Interactivity for the notification settings table: a topic's delivery frequency is greyed out while its email switch
 * is off, and turning on "pause everything" disables and dims the whole table. This is purely cosmetic -- the server
 * ignores a disabled topic's frequency and mutes all email while paused, so the form still submits correctly without
 * this controller.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['email', 'frequency', 'pause', 'dim'];
    static classes = ['paused'];

    declare readonly emailTargets: HTMLInputElement[];
    declare readonly frequencyTargets: HTMLSelectElement[];
    declare readonly hasPauseTarget: boolean;
    declare readonly pauseTarget: HTMLInputElement;
    declare readonly hasDimTarget: boolean;
    declare readonly dimTarget: HTMLElement;
    declare readonly pausedClasses: string[];

    connect(): void {
        this.sync();
    }

    sync(): void {
        const paused = this.hasPauseTarget && this.pauseTarget.checked;

        if (this.hasDimTarget) {
            this.pausedClasses.forEach((className) => this.dimTarget.classList.toggle(className, paused));
        }

        this.emailTargets.forEach((email, index) => {
            email.disabled = paused;

            const frequency = this.frequencyTargets[index];
            if (undefined !== frequency) {
                frequency.disabled = paused || !email.checked;
            }
        });
    }
}
