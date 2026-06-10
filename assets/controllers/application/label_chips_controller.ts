import { Controller } from '@hotwired/stimulus';

interface LabelOption {
    input: HTMLInputElement;
    row: HTMLElement;
    text: string;
}

/**
 * Turn a plain checkbox group into a chip control: the selected options render as dismissible badges and a typeahead
 * adds the not-yet-selected ones. Nothing is shown until an option is selected. Reused by the activity create/edit form
 * (a Symfony EntityType `multiple + expanded`) and the activity-overview label filter (live-component checkboxes).
 *
 * It is purely presentational: it only hides the native checkboxes and toggles them (firing `input`/`change`), so the
 * form's EntityType binding and the live component's `data-model` keep working untouched. Without JavaScript the native
 * checkboxes simply remain visible.
 *
 * Wrap an existing `.form-check` checkbox group:
 *
 * ```
 * <div data-controller="label-chips"
 *     data-label-chips-placeholder-value="Search labels..."
 *     data-label-chips-remove-label-value="Remove"
 *     data-label-chips-no-results-value="No matching labels">
 *     ...
 * </div>
 * ```
 */
export default class extends Controller {
    static values = {
        placeholder: String,
        removeLabel: String,
        noResults: String,
    };

    declare readonly placeholderValue: string;
    declare readonly removeLabelValue: string;
    declare readonly noResultsValue: string;

    private options: LabelOption[] = [];
    private matches: LabelOption[] = [];
    private activeIndex = -1;
    private blurTimer = 0;
    private chips!: HTMLDivElement;
    private input!: HTMLInputElement;
    private menu!: HTMLUListElement;

    connect(): void {
        // The native checkboxes are the source of truth; read them, hide their rows, and drive them from the chips.
        this.options = Array.from(this.element.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')).map((input) => ({
            input,
            row: (input.closest('.form-check') ?? input.parentElement) as HTMLElement,
            text: this.labelTextFor(input),
        }));
        this.options.forEach((option) => { option.row.hidden = true; });

        this.matches = [];
        this.activeIndex = -1;
        this.buildScaffolding();
        this.refresh();
    }

    disconnect(): void {
        window.clearTimeout(this.blurTimer);
    }

    labelTextFor(input: HTMLInputElement): string {
        const label = input.labels?.[0] ?? this.element.querySelector(`label[for="${input.id}"]`);

        return (label?.textContent ?? '').trim();
    }

    buildScaffolding(): void {
        this.chips = document.createElement('div');
        this.chips.className = 'd-flex flex-wrap gap-2 mb-2';

        const combo = document.createElement('div');
        combo.className = 'position-relative';

        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'form-control';
        this.input.setAttribute('role', 'combobox');
        this.input.setAttribute('autocomplete', 'off');
        this.input.setAttribute('aria-expanded', 'false');
        this.input.placeholder = this.placeholderValue;
        this.input.addEventListener('input', () => this.openAndFilter());
        this.input.addEventListener('focus', () => this.openAndFilter());
        this.input.addEventListener('keydown', (event) => this.onKeydown(event));
        this.input.addEventListener('blur', () => { this.blurTimer = window.setTimeout(() => this.close(), 150); });

        this.menu = document.createElement('ul');
        this.menu.className = 'dropdown-menu w-100';
        this.menu.setAttribute('role', 'listbox');
        this.menu.style.top = '100%';
        this.menu.style.left = '0';

        combo.append(this.input, this.menu);
        // Inject the chips + typeahead above the now-hidden native checkboxes.
        this.element.prepend(this.chips, combo);
    }

    refresh(): void {
        this.renderChips();
        this.renderMenu();
    }

    renderChips(): void {
        this.chips.replaceChildren();
        this.options.filter((option) => option.input.checked).forEach((option) => {
            const chip = document.createElement('span');
            chip.className = 'badge badge-tag badge-dismissible';
            chip.textContent = option.text;

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn-close';
            close.setAttribute('aria-label', `${this.removeLabelValue} ${option.text}`.trim());
            close.addEventListener('click', () => this.toggle(option, false));

            chip.appendChild(close);
            this.chips.appendChild(chip);
        });
    }

    openAndFilter(): void {
        this.activeIndex = -1;
        this.renderMenu();
        this.open();
    }

    renderMenu(): void {
        const query = this.input.value.trim().toLowerCase();
        this.matches = this.options.filter(
            (option) => !option.input.checked && option.text.toLowerCase().includes(query),
        );

        this.menu.replaceChildren();

        if (0 === this.matches.length) {
            const empty = document.createElement('li');
            empty.className = 'dropdown-item-text text-muted small';
            empty.textContent = this.noResultsValue;
            this.menu.appendChild(empty);

            return;
        }

        this.matches.forEach((option, index) => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = index === this.activeIndex ? 'dropdown-item active' : 'dropdown-item';
            button.setAttribute('role', 'option');
            button.textContent = option.text;
            // Use mousedown (not click) so it fires before the input's blur closes the menu.
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                this.toggle(option, true);
            });
            item.appendChild(button);
            this.menu.appendChild(item);
        });
    }

    onKeydown(event: KeyboardEvent): void {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.open();
                this.move(1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.open();
                this.move(-1);
                break;
            case 'Enter': {
                // While the menu is open the typeahead owns Enter, so it never reaches the form's implicit submit.
                // Use the highlighted match, or fall back to the first match when the user just typed and hit Enter.
                if (this.menu.classList.contains('show')) {
                    event.preventDefault();
                    const option = this.activeIndex >= 0 ? this.matches[this.activeIndex] : this.matches[0];
                    if (option) {
                        this.toggle(option, true);
                    }
                }
                break;
            }
            case 'Escape':
                this.close();
                break;
            default:
                break;
        }
    }

    move(delta: number): void {
        if (0 === this.matches.length) {
            return;
        }

        this.activeIndex = (this.activeIndex + delta + this.matches.length) % this.matches.length;
        this.renderMenu();
    }

    toggle(option: LabelOption, checked: boolean): void {
        option.input.checked = checked;
        option.input.dispatchEvent(new Event('input', { bubbles: true }));
        option.input.dispatchEvent(new Event('change', { bubbles: true }));

        this.input.value = '';
        this.activeIndex = -1;
        this.refresh();

        if (checked) {
            this.input.focus();
            this.openAndFilter();
        }
    }

    open(): void {
        this.menu.classList.add('show');
        this.input.setAttribute('aria-expanded', 'true');
    }

    close(): void {
        this.menu.classList.remove('show');
        this.input.setAttribute('aria-expanded', 'false');
    }
}
