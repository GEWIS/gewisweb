import { Controller } from '@hotwired/stimulus';

/**
 * Selection mode for the member's own photo page. While off, a tile is a link into its album; the "Select" button turns
 * it on, and clicking a tile then toggles its checkbox (native `<label>` behaviour) for the bulk hide/unhide form.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['toolbar', 'button'];
    static values = { selecting: Boolean };

    declare readonly hasToolbarTarget: boolean;
    declare readonly toolbarTarget: HTMLElement;
    declare readonly hasButtonTarget: boolean;
    declare readonly buttonTarget: HTMLElement;
    declare selectingValue: boolean;

    private readonly onClick = (event: Event): void => this.handleClick(event);

    connect(): void {
        this.element.addEventListener('click', this.onClick);
    }

    disconnect(): void {
        this.element.removeEventListener('click', this.onClick);
    }

    toggle(): void {
        this.selectingValue = !this.selectingValue;
    }

    selectingValueChanged(): void {
        this.element.classList.toggle('is-selecting', this.selectingValue);

        if (this.hasToolbarTarget) {
            this.toolbarTarget.hidden = !this.selectingValue;
        }

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-pressed', String(this.selectingValue));
        }

        if (!this.selectingValue) {
            this.clearSelection();
        }
    }

    private handleClick(event: Event): void {
        const tile = (event.target as HTMLElement).closest<HTMLElement>('.photo-masonry__item');
        if (null === tile || this.selectingValue) {
            return;
        }

        const href = tile.dataset.href;
        if (undefined !== href) {
            event.preventDefault();
            window.location.href = href;
        }
    }

    private clearSelection(): void {
        for (const input of this.element.querySelectorAll<HTMLInputElement>('.photo-select__input:checked')) {
            input.checked = false;
        }
    }
}
