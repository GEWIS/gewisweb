import { Controller } from '@hotwired/stimulus';

/**
 * Copies the text content of the source target to the clipboard, briefly swapping the button label as feedback.
 */
export default class extends Controller {
    static targets = ['source', 'button'];
    static values = { done: String };

    declare readonly sourceTarget: HTMLElement;
    declare readonly hasButtonTarget: boolean;
    declare readonly buttonTarget: HTMLElement;

    declare readonly doneValue: string;

    async copy(): Promise<void> {
        await navigator.clipboard.writeText(this.sourceTarget.innerText.trim());

        if (!this.hasButtonTarget || '' === this.doneValue) {
            return;
        }

        const original = this.buttonTarget.textContent;
        this.buttonTarget.textContent = this.doneValue;
        setTimeout(() => {
            this.buttonTarget.textContent = original;
        }, 2000);
    }
}
