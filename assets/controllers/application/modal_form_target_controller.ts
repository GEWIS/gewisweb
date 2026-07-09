import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'name', 'csrfToken'];

    declare readonly hasFormTarget: boolean;
    declare readonly formTarget: HTMLFormElement;
    declare readonly hasNameTarget: boolean;
    declare readonly nameTarget: HTMLElement;
    declare readonly hasCsrfTokenTarget: boolean;
    declare readonly csrfTokenTarget: HTMLInputElement;

    connect(): void {
        this.element.addEventListener('show.bs.modal', this._onShow);
    }

    disconnect(): void {
        this.element.removeEventListener('show.bs.modal', this._onShow);
    }

    private readonly _onShow = (event: Event & { relatedTarget?: HTMLElement }): void => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        if (this.hasFormTarget && trigger.dataset.action) {
            this.formTarget.setAttribute('action', trigger.dataset.action);
        }

        if (this.hasNameTarget && trigger.dataset.name !== undefined) {
            this.nameTarget.textContent = trigger.dataset.name;
        }

        if (this.hasCsrfTokenTarget && trigger.dataset.csrfToken !== undefined) {
            this.csrfTokenTarget.value = trigger.dataset.csrfToken;
        }
    };
}
