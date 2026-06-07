import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Drives the shared confirmation modal for live-component actions. A trigger button opens the modal declaratively and
 * describes, via data-* attributes, the live action to run only if the user confirms. The modal lives OUTSIDE the live
 * component, so the component's re-render after the action never touches the modal or leaves an orphaned backdrop.
 *
 *   <button data-bs-toggle="modal" data-bs-target="#confirm-modal"
 *           data-confirm-title="Run the draw?"
 *           data-confirm-message="This admits people up to capacity and locks it; it cannot be undone."
 *           data-confirm-label="Run the draw"
 *           data-confirm-live-action="drawFirstCome"
 *           data-confirm-live-args='{"listId": 12}'>…</button>
 *
 * The modal: <div id="confirm-modal" data-controller="confirm-modal"> with `title`, `message` and `confirm` targets.
 */
export default class extends Controller {
    static targets = ['title', 'message', 'confirm'];

    connect() {
        this._onShow = this._onShow.bind(this);
        this._onConfirm = this._onConfirm.bind(this);
        this.element.addEventListener('show.bs.modal', this._onShow);
        if (this.hasConfirmTarget) {
            this.confirmTarget.addEventListener('click', this._onConfirm);
        }
    }

    disconnect() {
        this.element.removeEventListener('show.bs.modal', this._onShow);
        if (this.hasConfirmTarget) {
            this.confirmTarget.removeEventListener('click', this._onConfirm);
        }
    }

    _onShow(event) {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        this._trigger = trigger;
        this._action = trigger.dataset.confirmLiveAction ?? null;
        this._args = trigger.dataset.confirmLiveArgs
            ? JSON.parse(trigger.dataset.confirmLiveArgs)
            : {};

        if (this.hasTitleTarget && trigger.dataset.confirmTitle !== undefined) {
            this.titleTarget.textContent = trigger.dataset.confirmTitle;
        }

        if (this.hasMessageTarget && trigger.dataset.confirmMessage !== undefined) {
            this.messageTarget.textContent = trigger.dataset.confirmMessage;
        }

        if (this.hasConfirmTarget && trigger.dataset.confirmLabel !== undefined) {
            this.confirmTarget.textContent = trigger.dataset.confirmLabel;
        }
    }

    async _onConfirm() {
        // The confirm button also carries data-bs-dismiss, so Bootstrap closes the modal; here we just run the action.
        if (null === this._action || undefined === this._trigger) {
            return;
        }

        // getComponent() resolves by a component's ROOT element (an exact WeakMap lookup, no DOM walk), so resolve
        // from the live root the trigger lives in -- the trigger button is only a descendant and would never match,
        // leaving the action silently un-run.
        const root = this._trigger.closest('[data-controller~="live"]');
        if (null === root) {
            return;
        }

        const component = await getComponent(root);
        component.action(this._action, this._args);
    }
}
