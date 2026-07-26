import { Controller } from '@hotwired/stimulus';

declare global {
    interface Window {
        bootstrap: {
            Toast: {
                getOrCreateInstance(element: Element): { show(): void };
            };
        };
    }
}

interface Envelope {
    type: string;
    [key: string]: unknown;
}

interface LocalisedText {
    en?: string;
    nl?: string;
}

/**
 * The application's single Server-Sent Events connection, mounted once by the base layout with the hub URL carrying
 * every topic the visitor may subscribe to. Messages are routed on their `type`: system commands act on the browser
 * (sign out, reload) and a toast is rendered for the user. Any other type is re-emitted as a `gewis:realtime:<type>`
 * DOM event so a feature can react without opening a second connection.
 *
 *   <div data-controller="notifications"
 *        data-notifications-hub-url-value="{{ mercure(topics, { subscribe: topics }) }}"
 *        data-notifications-locale-value="{{ app.request.locale }}"></div>
 */
export default class extends Controller<HTMLElement> {
    static values = {
        hubUrl: String,
        locale: String,
    };

    declare readonly hubUrlValue: string;
    declare readonly localeValue: string;

    private source: EventSource | null = null;

    connect(): void {
        if ('' === this.hubUrlValue) {
            return;
        }

        this.open();
    }

    disconnect(): void {
        this.source?.close();
        this.source = null;
    }

    private open(): void {
        this.source = new EventSource(this.hubUrlValue, { withCredentials: true });
        this.source.onmessage = (event: MessageEvent): void => {
            this.onMessage(event);
        };
        this.source.onerror = (): void => {
            this.onError();
        };
    }

    private onMessage(event: MessageEvent): void {
        let data: Envelope;
        try {
            data = JSON.parse(event.data) as Envelope;
        } catch {
            return;
        }

        switch (data.type) {
            case 'session.invalidate':
                this.source?.close();
                this.source = null;
                window.location.assign('string' === typeof data.redirect ? data.redirect : window.location.href);

                return;
            case 'force_reload':
                window.location.reload();

                return;
            case 'toast':
                this.renderToast(data);

                return;
            default:
                document.dispatchEvent(new CustomEvent(`gewis:realtime:${data.type}`, { detail: data }));
        }
    }

    private onError(): void {
        // CONNECTING means the browser is retrying on its own (and Mercure replays via Last-Event-ID). CLOSED means it
        // gave up, which for us almost always means the authorization cookie expired; reload to mint a fresh one. A
        // CLOSED EventSource fires this once and never reconnects, so we act on the first one, throttled to at most once
        // a minute so a hub outage cannot become a reload storm.
        if (this.source?.readyState !== EventSource.CLOSED) {
            return;
        }

        const last = Number(sessionStorage.getItem('gewis:realtime:reloaded') ?? '0');
        if (Date.now() - last < 60000) {
            return;
        }

        sessionStorage.setItem('gewis:realtime:reloaded', String(Date.now()));
        window.location.reload();
    }

    private renderToast(data: Envelope): void {
        const container = document.querySelector('#flash-toast-container');
        const template = document.querySelector<HTMLTemplateElement>('#realtime-toast-template');
        if (null === container || null === template || undefined === window.bootstrap) {
            return;
        }

        const text = this.localise(data.message as LocalisedText);
        if ('' === text) {
            return;
        }

        const toast = template.content.firstElementChild?.cloneNode(true);
        if (!(toast instanceof HTMLElement)) {
            return;
        }

        // The dot is GEWIS red by default (in the template); only a genuine warning/danger/success level overrides it
        // with a semantic colour, keeping ordinary notifications on-brand rather than Bootstrap's info blue.
        const level = 'string' === typeof data.level ? data.level : 'info';
        const indicator = toast.querySelector('.realtime-toast-indicator');
        if (indicator instanceof HTMLElement && 'info' !== level) {
            indicator.classList.add(`bg-${level}`);
        }

        const title = toast.querySelector('.realtime-toast-title');
        if (title instanceof HTMLElement) {
            const heading = data.title === undefined ? '' : this.localise(data.title as LocalisedText);
            title.textContent = '' !== heading ? heading : 'GEWIS';
        }

        const body = toast.querySelector('.toast-body');
        if (body instanceof HTMLElement) {
            body.textContent = text;
            this.appendLink(body, data);
        }

        container.append(toast);
        window.bootstrap.Toast.getOrCreateInstance(toast).show();
        toast.addEventListener('hidden.bs.toast', (): void => toast.remove());
    }

    private appendLink(body: HTMLElement, data: Envelope): void {
        const link = data.link as { href?: LocalisedText; label?: LocalisedText } | undefined;
        if (undefined === link || undefined === link.href || undefined === link.label) {
            return;
        }

        // The href comes in per language for the same reason the label does: point the reader at the page in their
        // own language rather than whichever one happened to be active when the notification was published.
        const href = this.localise(link.href);
        const label = this.localise(link.label);
        if ('' === href || '' === label) {
            return;
        }

        const anchor = document.createElement('a');
        anchor.href = href;
        anchor.className = 'd-block mt-1';
        anchor.textContent = label;
        body.append(anchor);
    }

    private localise(text: LocalisedText): string {
        return text[this.localeValue as keyof LocalisedText] ?? text.en ?? '';
    }
}
