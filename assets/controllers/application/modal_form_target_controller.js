import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'name', 'csrfToken'];

    connect() {
        this._onShow = this._onShow.bind(this);
        this.element.addEventListener('show.bs.modal', this._onShow);
    }

    disconnect() {
        this.element.removeEventListener('show.bs.modal', this._onShow);
    }

    _onShow(event) {
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
    }
}
