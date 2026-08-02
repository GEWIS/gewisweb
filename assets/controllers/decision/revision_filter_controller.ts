import { Controller } from '@hotwired/stimulus';

/**
 * The "All documents" / "Revised only" chips on the meeting page: toggles a class on the wrapper that CSS uses to
 * hide documents with a single version. Purely client-side; nothing is persisted.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['all', 'revised'];

    declare readonly allTarget: HTMLElement;
    declare readonly revisedTarget: HTMLElement;

    showAll(): void {
        this.element.classList.remove('revised-only');
        this.allTarget.classList.add('active');
        this.revisedTarget.classList.remove('active');
    }

    showRevised(): void {
        this.element.classList.add('revised-only');
        this.revisedTarget.classList.add('active');
        this.allTarget.classList.remove('active');
    }
}
