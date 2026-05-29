import { Controller } from '@hotwired/stimulus';

/**
 * Reveals/hides the full activity description. The content is line-clamped by CSS; this only shows the toggle button
 * when the content actually overflows, and swaps its label between "Show more" and "Show less".
 */
export default class extends Controller {
    static targets = ['content', 'button'];
    static values = { more: String, less: String };

    connect() {
        if (!this.hasContentTarget || !this.hasButtonTarget) {
            return;
        }

        // Wait for layout so clientHeight/scrollHeight are accurate, then reveal the button only when clamped.
        requestAnimationFrame(() => {
            if (this.contentTarget.scrollHeight > this.contentTarget.clientHeight + 1) {
                this.buttonTarget.classList.remove('d-none');
            }
        });
    }

    toggle() {
        const expanded = this.contentTarget.classList.toggle('expanded');
        this.buttonTarget.textContent = expanded ? this.lessValue : this.moreValue;
    }
}
