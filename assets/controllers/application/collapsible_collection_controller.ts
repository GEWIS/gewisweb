import { Controller } from '@hotwired/stimulus';

/**
 * Add/remove entries of a `CollectionType` where each entry is a collapsible `.panel`.
 *
 * The prototype already contains the whole panel (heading toggle, collapsible body and remove button), so adding an
 * entry just clones the prototype with its placeholder (e.g. `__list__` / `__field__`) replaced by a fresh,
 * monotonically increasing index. That keeps the collapse target ids unique and never collides, even after removals,
 * and a cloned list keeps its nested field/option placeholders intact, and the nested controllers connect automatically
 * once inserted.
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
            this.indexValue = this.entriesTarget.querySelectorAll(':scope > [data-collapsible-collection-target="entry"]').length;
        }
    }

    add(event: Event): void {
        event.preventDefault();

        const html = this.prototypeValue.replaceAll(this.prototypeNameValue, String(this.indexValue));

        const template = document.createElement('template');
        template.innerHTML = html.trim();

        this.entriesTarget.appendChild(template.content.firstElementChild!);
        this.indexValue += 1;
    }

    remove(event: Event): void {
        event.preventDefault();
        (event.currentTarget as HTMLElement).closest('[data-collapsible-collection-target="entry"]')?.remove();
    }
}
