import { Controller } from '@hotwired/stimulus';

/**
 * One request per URL however many instances are on the page: the front page mounts the controller twice (its own
 * panel and the footer), and both write the same answer.
 */
const requests = new Map<string, Promise<unknown>>();

function fetchInfimum(url: string): Promise<unknown> {
    let request = requests.get(url);
    if (undefined === request) {
        request = fetch(url, { credentials: 'same-origin' })
            .then(async (response) => ((await response.json()) as { content?: unknown }).content)
            .catch(() => null);
        requests.set(url, request);
    }

    return request;
}

/**
 * Fills in the infimum after the page has been drawn, and swaps it when a fresh one is pushed. It is fetched here
 * rather than rendered with the page because it comes from somebody else's server, and the footer it sits in is on
 * every page of this website.
 *
 * The rotation needs no connection of its own: the application's single EventSource re-dispatches anything it does not
 * handle itself as a `gewis:realtime:<type>` DOM event.
 *
 *   - the text:    data-infimum-target="quote"
 *   - the source:  data-infimum-url-value="<the infimum endpoint>"
 *   - the fallback: data-infimum-unavailable-value="<what to say when there is none>"
 */
export default class extends Controller {
    static targets = ['quote'];
    static values = {
        url: String,
        unavailable: String,
    };

    declare readonly quoteTargets: HTMLElement[];
    declare readonly urlValue: string;
    declare readonly unavailableValue: string;

    private readonly onRotate = (event: Event): void => {
        const detail = (event as CustomEvent<{ infimum?: unknown }>).detail;
        if ('string' !== typeof detail?.infimum || '' === detail.infimum) {
            return;
        }

        this.write(detail.infimum);
    };

    connect(): void {
        document.addEventListener('gewis:realtime:infimum.rotate', this.onRotate);
        void this.load();
    }

    disconnect(): void {
        document.removeEventListener('gewis:realtime:infimum.rotate', this.onRotate);
    }

    private async load(): Promise<void> {
        const content = await fetchInfimum(this.urlValue);

        this.write('string' === typeof content && '' !== content
            ? content
            : this.unavailableValue);
    }

    private write(infimum: string): void {
        this.quoteTargets.forEach((quote) => {
            quote.textContent = infimum;
        });
    }
}
