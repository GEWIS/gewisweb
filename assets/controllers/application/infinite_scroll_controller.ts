import { Controller } from '@hotwired/stimulus';

/**
 * Autoloads the next batch of a live-component list when a sentinel scrolls into view, by clicking the (always
 * rendered) "Load more" button.
 *
 * Without `IntersectionObserver` the button stays usable on its own. A short cooldown prevents firing a burst of
 * requests while the sentinel remains visible.
 */
export default class extends Controller {
    static targets = ['sentinel', 'button'];

    declare readonly hasSentinelTarget: boolean;
    declare readonly sentinelTarget: HTMLElement;
    declare readonly hasButtonTarget: boolean;
    declare readonly buttonTarget: HTMLElement;

    private cooldown = false;
    private observer: IntersectionObserver | null = null;

    connect(): void {
        if (!('IntersectionObserver' in window) || !this.hasSentinelTarget) {
            return;
        }

        this.cooldown = false;
        this.observer = new IntersectionObserver((entries) => {
            const visible = entries.some((entry) => entry.isIntersecting);
            if (!visible || this.cooldown || !this.hasButtonTarget) {
                return;
            }

            this.cooldown = true;
            this.buttonTarget.click();
            window.setTimeout(() => {
                this.cooldown = false;
            }, 800);
        });
        this.observer.observe(this.sentinelTarget);
    }

    disconnect(): void {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}
