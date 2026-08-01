import { Controller } from '@hotwired/stimulus';

/**
 * Navigates to the URL carried by the selected option, for dropdowns on plain (non-live) pages.
 */
export default class extends Controller<HTMLSelectElement> {
    public navigate(): void {
        window.location.assign(this.element.value);
    }
}
