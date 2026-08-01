import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Drag-reordering that persists immediately through a live component action instead of a form submit (the drag
 * mechanics mirror the form-based `sortable` controller). Each direct entry carries its id; on drop the new id order
 * is sent to the named live action, merged with any extra arguments.
 *
 * ```
 * <div data-controller="live-sortable" data-live-sortable-action-value="reorderPoints"
 *      data-live-sortable-extra-value='{"pointId": 3}'>
 *     <div data-live-sortable-target="entry" data-live-sortable-id-param="3">
 *         <span draggable="true" data-action="dragstart->live-sortable#dragStart dragend->live-sortable#dragEnd">...</span>
 *     </div>
 * </div>
 * ```
 */
export default class extends Controller<HTMLElement> {
    static targets = ['entry'];
    static values = {
        action: String,
        extra: { type: Object, default: {} },
    };

    declare readonly entryTargets: HTMLElement[];

    declare readonly actionValue: string;
    declare readonly extraValue: Record<string, unknown>;

    private dragging: HTMLElement | null = null;
    private orderBeforeDrag = '';

    dragStart(event: DragEvent): void {
        const handle = event.currentTarget as HTMLElement;
        const entry = handle.closest<HTMLElement>('[data-live-sortable-target="entry"]');
        if (null === entry || !this.directEntries().includes(entry)) {
            return;
        }

        this.dragging = entry;
        this.orderBeforeDrag = this.orderedIds().join(',');
        entry.classList.add('dragging');

        if (null !== event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            // Firefox only starts a drag once some data is set; the value itself is unused.
            event.dataTransfer.setData('text/plain', '');
            event.dataTransfer.setDragImage(entry, 0, 0);
        }
    }

    dragEnd(): void {
        if (null === this.dragging) {
            return;
        }

        this.dragging.classList.remove('dragging');
        this.dragging = null;

        const orderedIds = this.orderedIds();
        if (orderedIds.join(',') === this.orderBeforeDrag) {
            return;
        }

        void this.persist(orderedIds);
    }

    dragOver(event: DragEvent): void {
        if (null === this.dragging) {
            return;
        }

        // Allow dropping here and live-preview the move by slotting the dragged entry before the entry under the cursor.
        event.preventDefault();

        const after = this.entryAfter(event.clientY);
        if (null === after) {
            this.element.appendChild(this.dragging);
        } else if (after !== this.dragging) {
            this.element.insertBefore(this.dragging, after);
        }
    }

    drop(event: DragEvent): void {
        event.preventDefault();
    }

    private async persist(orderedIds: number[]): Promise<void> {
        const component = await getComponent(this.element.closest<HTMLElement>('[data-controller~="live"]')!);
        component.action(this.actionValue, { ...this.extraValue, orderedIds });
    }

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
        return Array.from(this.element.querySelectorAll<HTMLElement>(':scope > [data-live-sortable-target="entry"]'));
    }

    private orderedIds(): number[] {
        return this.directEntries().map((entry) => Number(entry.dataset.liveSortableIdParam));
    }
}
