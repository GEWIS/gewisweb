import { Controller } from '@hotwired/stimulus';

/**
 * Persists the "festive effects" preference from the navbar switch. Flipping the switch immediately toggles
 * the `data-cosmetics` attribute on <html> (the CSS hides #gewis-festivities when it is "off"), then POSTs the new
 * state so it survives across pages and devices. Re-enabling only fully re-appears on the next navigation, since the
 * effect scripts are emitted server-side.
 */
export default class extends Controller {
    static values = { url: String, csrf: String };
    static targets = ['input'];

    declare readonly urlValue: string;
    declare readonly csrfValue: string;
    declare readonly hasInputTarget: boolean;
    declare readonly inputTarget: HTMLInputElement;

    toggle(): void {
        const enabled = this.inputTarget.checked;
        document.documentElement.dataset.cosmetics = enabled ? 'on' : 'off';
        void this.persist(!enabled);
    }

    private async persist(disabled: boolean): Promise<void> {
        try {
            await fetch(this.urlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({
                    _csrf_token: this.csrfValue,
                    disabled: disabled ? '1' : '0',
                }),
            });
        } catch {
            // A failed toggle just means the preference is not persisted; the visible state already matches the switch.
        }
    }
}
