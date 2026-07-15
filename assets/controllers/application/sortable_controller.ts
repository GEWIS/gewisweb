import { Controller } from '@hotwired/stimulus';

/**
 * Reorders the entries of a Symfony CollectionType by dragging, persisting the new order through the surrounding form.
 *
 * Each entry carries a drag handle (`draggable`, firing `dragStart`/`dragEnd`) and a hidden `position` input
 * (`data-sortable-target="position"`); the entries wrapper (`data-sortable-target="entries"`) receives
 * `dragOver`/`drop`. On drop the dragged entry is moved in the DOM and every entry's position input is rewritten to its
 * new index. A capture-phase submit listener reindexes once more so entries added after the last drag (whose prototype
 * position is 0) are numbered too. Only DIRECT entries of this controller's wrapper are considered, so a nested
 * collection (a choice field's options inside a question) reorders independently -- Stimulus binds each `position`
 * target to its nearest `sortable` controller.
 *
 * ```
 * <div data-controller="form-collection sortable">
 *     <div data-form-collection-target="entries" data-sortable-target="entries"
 *          data-action="dragover->sortable#dragOver drop->sortable#drop">
 *         <div data-form-collection-target="entry">
 *             <span draggable="true" data-action="dragstart->sortable#dragStart dragend->sortable#dragEnd">...</span>
 *             <input type="hidden" data-sortable-target="position">
 *         </div>
 *     </div>
 * </div>
 * ```
 */
export default class extends Controller {
    static targets = ['entries', 'position'];

    declare readonly entriesTarget: HTMLElement;
    declare readonly positionTargets: HTMLInputElement[];

    private dragging: HTMLElement | null = null;
    private form: HTMLFormElement | null = null;
    private readonly reindexOnSubmit = (): void => this.reindex();

    connect(): void {
        // Reindex right before the form serializes, so newly added entries (never dragged) also get their order.
        this.form = this.element.closest('form');
        this.form?.addEventListener('submit', this.reindexOnSubmit, true);
    }

    disconnect(): void {
        this.form?.removeEventListener('submit', this.reindexOnSubmit, true);
    }

    dragStart(event: DragEvent): void {
        const handle = event.currentTarget as HTMLElement;
        const entry = handle.closest<HTMLElement>('[data-form-collection-target="entry"]');
        // Only reorder entries that belong directly to this collection, never a nested one.
        if (null === entry || entry.parentElement !== this.entriesTarget) {
            return;
        }

        this.dragging = entry;
        entry.classList.add('dragging');

        if (null !== event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            // Firefox only starts a drag once some data is set; the value itself is unused.
            event.dataTransfer.setData('text/plain', '');
            event.dataTransfer.setDragImage(entry, 0, 0);
        }
    }

    dragEnd(): void {
        if (null !== this.dragging) {
            this.dragging.classList.remove('dragging');
            this.dragging = null;
        }

        this.reindex();
    }

    dragOver(event: DragEvent): void {
        if (null === this.dragging) {
            return;
        }

        // Allow dropping here and live-preview the move by slotting the dragged entry before the entry under the cursor.
        event.preventDefault();

        const after = this.entryAfter(event.clientY);
        if (null === after) {
            this.entriesTarget.appendChild(this.dragging);
        } else if (after !== this.dragging) {
            this.entriesTarget.insertBefore(this.dragging, after);
        }
    }

    drop(event: DragEvent): void {
        event.preventDefault();
        this.reindex();
    }

    /**
     * The first direct entry whose vertical midpoint is below the cursor (the entry the dragged one should sit before),
     * or null to append at the end.
     */
    private entryAfter(y: number): HTMLElement | null {
        let closestOffset = Number.NEGATIVE_INFINITY;
        let closest: HTMLElement | null = null;

        for (const entry of this.directEntries()) {
            if (entry === this.dragging) {
                continue;
            }

            const box = entry.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closestOffset) {
                closestOffset = offset;
                closest = entry;
            }
        }

        return closest;
    }

    private directEntries(): HTMLElement[] {
        return Array.from(
            this.entriesTarget.querySelectorAll<HTMLElement>(':scope > [data-form-collection-target="entry"]'),
        );
    }

    private reindex(): void {
        // positionTargets are in document (i.e. entry) order, one per direct entry -- nested collections' position
        // inputs bind to their own inner sortable controller, not this one.
        this.positionTargets.forEach((input, index) => {
            input.value = String(index);
        });
    }
}
