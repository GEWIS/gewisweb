import { Controller } from '@hotwired/stimulus';

/**
 * Add/remove entries of a Symfony CollectionType (allow_add / allow_delete).
 *
 * The prototype already contains the whole entry (wrapper and remove button, see the collection form theme), so adding
 * an entry just clones it with its placeholder replaced by a fresh index. Nesting works because each level uses a
 * distinct prototype placeholder (e.g. `__list__`, `__field__`, `__option__`): replacing the outer placeholder leaves
 * the inner ones intact, and Stimulus auto-connects the nested controllers when the new markup is inserted.
 */
export default class extends Controller {
    static targets = ['entries'];
    static values = {
        prototype: String,
        prototypeName: String,
        index: Number,
    };

    declare readonly entriesTarget: HTMLElement;

    declare readonly prototypeValue: string;
    declare readonly prototypeNameValue: string;
    declare readonly hasIndexValue: boolean;
    declare indexValue: number;

    connect(): void {
        if (!this.hasIndexValue) {
            this.indexValue = this.entriesTarget.querySelectorAll(':scope > [data-form-collection-target="entry"]').length;
        }
    }

    add(event: Event): void {
        event.preventDefault();

        const placeholder = this.prototypeNameValue || '__name__';
        const html = this.prototypeValue.replaceAll(placeholder, String(this.indexValue));

        const template = document.createElement('template');
        template.innerHTML = html.trim();

        this.entriesTarget.appendChild(template.content.firstElementChild!);
        this.indexValue += 1;
    }

    remove(event: Event): void {
        event.preventDefault();
        (event.currentTarget as HTMLElement).closest('[data-form-collection-target="entry"]')?.remove();
    }
}
