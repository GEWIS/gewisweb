import { Controller } from '@hotwired/stimulus';

interface AlbumResult {
    id: number;
    label: string;
}

/**
 * Async destination picker for moving photos: type to search albums by name and pick one, which writes its id into the
 * hidden field the bulk form submits. This replaces a `<select>` of every album, so a set of thousands stays one query
 * per keystroke rather than a page-long list.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['input', 'destination', 'menu'];
    static values = {
        url: String,
        exclude: Number,
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly destinationTarget: HTMLInputElement;
    declare readonly menuTarget: HTMLElement;
    declare readonly urlValue: string;
    declare readonly excludeValue: number;

    private results: AlbumResult[] = [];
    private activeIndex = -1;
    private debounce = 0;
    // Bumped per request so a slow earlier fetch cannot overwrite a newer one's results.
    private token = 0;

    disconnect(): void {
        window.clearTimeout(this.debounce);
    }

    search(): void {
        // A destination is only valid once picked from the list; typing again clears the previous choice.
        this.destinationTarget.value = '';
        const query = this.inputTarget.value.trim();

        window.clearTimeout(this.debounce);
        if (query.length < 2) {
            this.close();

            return;
        }

        this.debounce = window.setTimeout((): void => void this.fetch(query), 200);
    }

    keydown(event: KeyboardEvent): void {
        if ('Escape' === event.key) {
            this.close();

            return;
        }

        if (!this.isOpen()) {
            return;
        }

        if ('ArrowDown' === event.key) {
            event.preventDefault();
            this.move(1);
        } else if ('ArrowUp' === event.key) {
            event.preventDefault();
            this.move(-1);
        } else if ('Enter' === event.key) {
            // Enter inside the open list picks a result rather than submitting the move with an empty destination;
            // fall back to the first (often only) match when nothing is highlighted yet.
            event.preventDefault();
            this.choose(-1 === this.activeIndex ? 0 : this.activeIndex);
        }
    }

    close(): void {
        this.menuTarget.classList.remove('show');
        this.menuTarget.replaceChildren();
        this.activeIndex = -1;
    }

    private async fetch(query: string): Promise<void> {
        const token = ++this.token;
        try {
            const response = await fetch(`${this.urlValue}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok || token !== this.token) {
                return;
            }

            const results = await response.json() as AlbumResult[];
            if (token !== this.token) {
                return;
            }

            this.results = results.filter((result) => result.id !== this.excludeValue);
            this.render();
        } catch {
            this.close();
        }
    }

    private render(): void {
        this.activeIndex = -1;
        this.menuTarget.replaceChildren();

        for (const [index, result] of this.results.entries()) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item';
            item.textContent = result.label;
            // mousedown, not click, so the choice registers before the input's blur closes the menu.
            item.addEventListener('mousedown', (event): void => {
                event.preventDefault();
                this.choose(index);
            });
            this.menuTarget.append(item);
        }

        this.menuTarget.classList.toggle('show', this.results.length > 0);
    }

    private move(delta: number): void {
        const items = this.menuTarget.children;
        if (0 === items.length) {
            return;
        }

        this.activeIndex = (this.activeIndex + delta + items.length) % items.length;
        for (const [index, item] of Array.from(items).entries()) {
            item.classList.toggle('active', index === this.activeIndex);
        }
    }

    private choose(index: number): void {
        const result = this.results[index];
        if (undefined === result) {
            return;
        }

        this.destinationTarget.value = String(result.id);
        this.inputTarget.value = result.label;
        this.close();
    }

    private isOpen(): boolean {
        return this.menuTarget.classList.contains('show');
    }
}
