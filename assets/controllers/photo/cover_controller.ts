import { Controller } from '@hotwired/stimulus';

/**
 * Live album cover on the manage view. Regeneration runs on a worker, so the new cover is not known when the request
 * returns; the worker publishes the fresh (signed) URL to a private Mercure topic and this controller swaps it in.
 * Regenerating is an in-page fetch (no reload) so the EventSource stays open the whole time -- there is no
 * reconnection gap in which a fast worker's push could be missed. The subscribe URL (with its authorization cookie)
 * is minted server-side.
 *
 *   <div data-controller="photo-cover" data-photo-cover-hub-url-value="{{ mercure(topic, { subscribe: [topic] }) }}">
 *       <img data-photo-cover-target="image" ...>
 *       <div data-photo-cover-target="placeholder"> ... </div>
 *       <form data-action="photo-cover#regenerate"> ... </form>
 *   </div>
 */
export default class extends Controller<HTMLElement> {
    static values = {
        hubUrl: String,
    };

    static targets = ['image', 'placeholder', 'button', 'icon', 'error'];

    declare readonly hubUrlValue: string;
    declare readonly hasImageTarget: boolean;
    declare readonly imageTarget: HTMLImageElement;
    declare readonly hasPlaceholderTarget: boolean;
    declare readonly placeholderTarget: HTMLElement;
    declare readonly hasButtonTarget: boolean;
    declare readonly buttonTarget: HTMLButtonElement;
    declare readonly hasIconTarget: boolean;
    declare readonly iconTarget: HTMLElement;
    declare readonly hasErrorTarget: boolean;
    declare readonly errorTarget: HTMLElement;

    private source: EventSource | null = null;
    private timeout: number | null = null;

    connect(): void {
        if ('' === this.hubUrlValue) {
            return;
        }

        this.source = new EventSource(this.hubUrlValue, { withCredentials: true });
        this.source.onmessage = (event: MessageEvent): void => {
            this.onMessage(event);
        };
    }

    disconnect(): void {
        this.source?.close();
        this.source = null;
        this.clearTimeout();
    }

    async regenerate(event: Event): Promise<void> {
        event.preventDefault();
        const form = event.currentTarget as HTMLFormElement;

        this.toggleError(false);
        this.setBusy(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                this.setBusy(false);

                return;
            }
        } catch {
            this.setBusy(false);

            return;
        }

        // The push clears the busy state; this only fires if generation produced no cover (nothing is published).
        this.timeout = window.setTimeout((): void => this.setBusy(false), 60000);
    }

    private onMessage(event: MessageEvent): void {
        this.clearTimeout();
        this.setBusy(false);

        let data: { status?: string; url?: string };
        try {
            data = JSON.parse(event.data) as { status?: string; url?: string };
        } catch {
            return;
        }

        // Anything other than a ready cover (e.g. an album without photos) surfaces the error notice.
        if ('ready' !== data.status || undefined === data.url || '' === data.url || !this.hasImageTarget) {
            this.toggleError(true);

            return;
        }

        this.imageTarget.src = data.url;
        this.imageTarget.classList.remove('d-none');
        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.remove();
        }
    }

    private setBusy(busy: boolean): void {
        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = busy;
        }

        if (this.hasIconTarget) {
            this.iconTarget.classList.toggle('fa-spin', busy);
        }
    }

    private toggleError(shown: boolean): void {
        if (this.hasErrorTarget) {
            this.errorTarget.classList.toggle('d-none', !shown);
        }
    }

    private clearTimeout(): void {
        if (null !== this.timeout) {
            window.clearTimeout(this.timeout);
            this.timeout = null;
        }
    }
}
