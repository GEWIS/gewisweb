import { Controller } from '@hotwired/stimulus';

interface DirectoryResult {
    lidnr: number;
    fullName: string;
    generation: number;
    url: string;
}

/**
 * The member search: fetches matches while typing and renders them as profile links (the directory) or, in `select`
 * mode, as buttons that fill a hidden destination input (the authorization form). A monotonic token guards against a
 * slow response overwriting a newer one; rows are built through DOM APIs because names are user data.
 */
export default class extends Controller {
    static targets = ['input', 'results', 'hint', 'destination'];
    static values = {
        url: String,
        capped: String,
        mode: { type: String, default: 'links' },
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly resultsTarget: HTMLElement;
    declare readonly hasHintTarget: boolean;
    declare readonly hintTarget: HTMLElement;
    declare readonly hasDestinationTarget: boolean;
    declare readonly destinationTarget: HTMLInputElement;

    declare readonly urlValue: string;
    declare readonly cappedValue: string;
    declare readonly modeValue: string;

    private token = 0;
    private debounce: number | null = null;

    search(): void {
        if (null !== this.debounce) {
            clearTimeout(this.debounce);
        }

        this.debounce = window.setTimeout(() => void this.fetchResults(), 200);
    }

    private async fetchResults(): Promise<void> {
        const query = this.inputTarget.value.trim();
        const token = ++this.token;

        if (query.length < 2) {
            this.resultsTarget.replaceChildren();
            this.toggleHint(true);

            return;
        }

        const response = await fetch(`${this.urlValue}?q=${encodeURIComponent(query)}`, {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || token !== this.token) {
            return;
        }

        const results = (await response.json()) as DirectoryResult[];
        if (token !== this.token) {
            return;
        }

        this.render(results);
    }

    private render(results: DirectoryResult[]): void {
        this.toggleHint(false);

        const rows = 'select' === this.modeValue ? this.selectRows(results) : this.tableRows(results);

        if (32 <= results.length) {
            rows.push(this.cappedRow());
        }

        this.resultsTarget.replaceChildren(...rows);
    }

    private selectRows(results: DirectoryResult[]): HTMLElement[] {
        return results.map((result, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.addEventListener('click', () => this.select(result));
            button.className = `d-flex align-items-center gap-3 py-2 text-body w-100 text-start border-0 bg-transparent${0 === index ? '' : ' border-top'}`;

            const name = document.createElement('span');
            name.className = 'fw-semibold flex-grow-1';
            name.textContent = result.fullName;

            const generation = document.createElement('span');
            generation.className = 'small text-muted';
            generation.textContent = String(result.generation);

            button.append(name, generation);

            return button;
        });
    }

    private tableRows(results: DirectoryResult[]): HTMLElement[] {
        return results.map((result) => {
            const row = document.createElement('tr');
            row.style.cursor = 'pointer';
            row.addEventListener('click', () => window.location.assign(result.url));

            const lidnr = document.createElement('td');
            lidnr.className = 'text-muted';
            lidnr.textContent = String(result.lidnr);

            const name = document.createElement('td');
            const link = document.createElement('a');
            link.href = result.url;
            link.className = 'fw-semibold text-body';
            link.textContent = result.fullName;
            name.append(link);

            const generation = document.createElement('td');
            generation.className = 'text-muted';
            generation.textContent = String(result.generation);

            row.append(lidnr, name, generation);

            return row;
        });
    }

    private cappedRow(): HTMLElement {
        if ('select' === this.modeValue) {
            const capped = document.createElement('p');
            capped.className = 'small text-muted mt-2 mb-0';
            capped.textContent = this.cappedValue;

            return capped;
        }

        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 3;
        cell.className = 'small text-muted';
        cell.textContent = this.cappedValue;
        row.append(cell);

        return row;
    }

    private select(result: DirectoryResult): void {
        if (this.hasDestinationTarget) {
            this.destinationTarget.value = String(result.lidnr);
        }

        this.inputTarget.value = result.fullName;
        this.resultsTarget.replaceChildren();
    }

    private toggleHint(visible: boolean): void {
        if (!this.hasHintTarget) {
            return;
        }

        this.hintTarget.classList.toggle('d-none', !visible);
    }
}
