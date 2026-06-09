import { Controller } from '@hotwired/stimulus';

/**
 * Add/remove entries of a Symfony CollectionType (allow_add / allow_delete).
 *
 * Nesting works because each level uses a distinct prototype placeholder (e.g. `__list__`, `__field__`, `__option__`):
 * replacing the outer placeholder leaves thr inner ones intact, and Stimulus auto-connects the nested controllers when
 * the new markup is inserted.
 */
export default class extends Controller {
    static targets = ['entries'];
    static values = {
        prototype: String,
        prototypeName: String,
        allowDelete: Boolean,
        removeLabel: String,
        index: Number,
    };

    connect() {
        if (!this.hasIndexValue) {
            this.indexValue = this.entriesTarget.querySelectorAll(':scope > [data-form-collection-target="entry"]').length;
        }
    }

    add(event) {
        event.preventDefault();

        const placeholder = this.prototypeNameValue || '__name__';
        const html = this.prototypeValue.replaceAll(placeholder, String(this.indexValue));

        const entry = document.createElement('div');
        entry.setAttribute('data-form-collection-target', 'entry');
        entry.className = 'border rounded p-3 mb-2';
        entry.innerHTML = html;

        if (this.allowDeleteValue) {
            entry.appendChild(this.removeButton());
        }

        this.entriesTarget.appendChild(entry);
        this.indexValue += 1;
    }

    remove(event) {
        event.preventDefault();
        event.currentTarget.closest('[data-form-collection-target="entry"]').remove();
    }

    removeButton() {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-gewis-primary mt-2';
        button.setAttribute('data-action', 'form-collection#remove');

        const icon = document.createElement('i');
        icon.className = 'fas fa-trash';
        button.append(icon, ` ${this.removeLabelValue || 'Remove'}`);

        return button;
    }
}
