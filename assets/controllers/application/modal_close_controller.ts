import { Controller } from '@hotwired/stimulus';

declare global {
    interface Window {
        bootstrap: {
            Modal: {
                getInstance(element: Element): { hide(): void } | null;
            };
        };
    }
}

/**
 * Closes the Bootstrap modal this controller is attached to when a scoped `signup:success` browser event fires
 * (dispatched by the Activity:SignupList live component after a successful sign-up or edit). The event carries the
 * list id in its detail, so with several lists on the page only the matching modal closes. The modal lives inside the
 * live component, so on a re-render the show class is preserved (ExternalMutationTracker) until this hides it.
 */
export default class extends Controller {
    static values = { listId: Number };

    declare readonly listIdValue: number;

    hide(event: CustomEvent<{ listId?: number | string }>): void {
        if (
            event.detail
            && event.detail.listId !== undefined
            && Number(event.detail.listId) !== this.listIdValue
        ) {
            return;
        }

        const modal = window.bootstrap.Modal.getInstance(this.element);
        if (modal) {
            modal.hide();
        }
    }
}
