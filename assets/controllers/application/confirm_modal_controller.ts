import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import type { Component } from '@symfony/ux-live-component';

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

    declare readonly hasTitleTarget: boolean;
    declare readonly titleTarget: HTMLElement;
    declare readonly hasMessageTarget: boolean;
    declare readonly messageTarget: HTMLElement;
    declare readonly hasConfirmTarget: boolean;
    declare readonly confirmTarget: HTMLElement;

    // The live action armed by the trigger that opened the modal, held in one field so an armed action always comes
    // with the component lookup to run it on; null while nothing is armed.
    private _pending: {
        action: string;
        args: Record<string, unknown>;
        component: Promise<Component | null>;
    } | null = null;

    connect(): void {
        this.element.addEventListener('show.bs.modal', this._onShow);
        if (this.hasConfirmTarget) {
            this.confirmTarget.addEventListener('click', this._onConfirm);
        }
    }

    disconnect(): void {
        this.element.removeEventListener('show.bs.modal', this._onShow);
        if (this.hasConfirmTarget) {
            this.confirmTarget.removeEventListener('click', this._onConfirm);
        }
    }

    private readonly _onShow = (event: Event & { relatedTarget?: HTMLElement }): void => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        // getComponent() does a STRICT lookup keyed by the component's ROOT element (the [data-controller~="live"]
        // node), not a closest() from a descendant — so passing the trigger button (a descendant) never matches and
        // rejects with "Component not found". Resolve the root from the trigger here, at show-time while the trigger is
        // still attached: a re-render between opening and confirming (e.g. a data-model field blurring on click) can
        // detach the trigger, and a detached node's closest() returns null — whereas the root element and the resolved
        // component instance both survive re-renders. `.catch(() => null)` is attached eagerly so a cancelled modal
        // never logs an unhandled rejection.
        const action = trigger.dataset.confirmLiveAction ?? null;
        const root = trigger.closest<HTMLElement>('[data-controller~="live"]');
        this._pending = null !== action && null !== root
            ? {
                action,
                args: this._parseArgs(trigger.dataset.confirmLiveArgs),
                component: getComponent(root).catch(() => null),
            }
            : null;

        if (this.hasTitleTarget && trigger.dataset.confirmTitle !== undefined) {
            this.titleTarget.textContent = trigger.dataset.confirmTitle;
        }

        if (this.hasMessageTarget && trigger.dataset.confirmMessage !== undefined) {
            this.messageTarget.textContent = trigger.dataset.confirmMessage;
        }

        if (this.hasConfirmTarget && trigger.dataset.confirmLabel !== undefined) {
            this.confirmTarget.textContent = trigger.dataset.confirmLabel;
        }
    };

    // Parse the trigger's data-confirm-live-args. The value is server-rendered JSON, so a parse failure means a
    // templating slip, not user input: swallow it (logging to the console) and fall back to no args rather than letting
    // an uncaught SyntaxError leave the confirm button silently dead.
    private _parseArgs(raw: string | undefined): Record<string, unknown> {
        if (!raw) {
            return {};
        }

        try {
            const parsed: unknown = JSON.parse(raw);

            return typeof parsed === 'object' && null !== parsed ? (parsed as Record<string, unknown>) : {};
        } catch {
            console.error('confirm-modal: ignoring malformed data-confirm-live-args', raw);

            return {};
        }
    }

    private readonly _onConfirm = async (): Promise<void> => {
        // The confirm button also carries data-bs-dismiss, so Bootstrap closes the modal; here we just run the action.
        if (null === this._pending) {
            return;
        }

        const { action, args, component } = this._pending;
        const resolved = await component;
        if (null !== resolved) {
            resolved.action(action, args);
        }
    };
}
