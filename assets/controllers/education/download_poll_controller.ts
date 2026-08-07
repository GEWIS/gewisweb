import { Controller } from '@hotwired/stimulus';

interface DownloadStatus {
    status: 'pending' | 'ready' | 'failed';
    url: string | null;
}

/**
 * Waits for a watermarked course document to be built. Polling backs off: the answer is nearly always ready on one of
 * the first few tries, and a page left open should not keep asking every half second.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['pending', 'ready', 'failed', 'link'];
    static values = {
        url: String,
    };

    declare readonly pendingTarget: HTMLElement;
    declare readonly readyTarget: HTMLElement;
    declare readonly failedTarget: HTMLElement;
    declare readonly linkTarget: HTMLAnchorElement;
    declare readonly urlValue: string;

    private timer = 0;
    private delay = 500;
    private stopped = false;

    connect(): void {
        void this.poll();
    }

    disconnect(): void {
        this.stopped = true;
        window.clearTimeout(this.timer);
    }

    private async poll(): Promise<void> {
        if (this.stopped) {
            return;
        }

        let status: DownloadStatus;
        try {
            const response = await fetch(this.urlValue, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                this.show(this.failedTarget);

                return;
            }

            status = (await response.json()) as DownloadStatus;
        } catch {
            // A dropped request is not a failed build; keep waiting and try again.
            this.scheduleNext();

            return;
        }

        if ('ready' === status.status && null !== status.url) {
            this.linkTarget.href = status.url;
            this.show(this.readyTarget);
            // Navigating to the file leaves this page where it is: the response is an attachment.
            window.location.href = status.url;

            return;
        }

        if ('failed' === status.status) {
            this.show(this.failedTarget);

            return;
        }

        this.scheduleNext();
    }

    private scheduleNext(): void {
        this.delay = Math.min(this.delay * 1.5, 5000);
        this.timer = window.setTimeout(() => void this.poll(), this.delay);
    }

    private show(target: HTMLElement): void {
        this.stopped = true;
        window.clearTimeout(this.timer);

        this.pendingTarget.classList.add('d-none');
        target.classList.remove('d-none');
    }
}
