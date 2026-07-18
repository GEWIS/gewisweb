import { Controller } from '@hotwired/stimulus';

/**
 * Drives the signing step of the external-application form. The shared secret only applies to HS512, so its field is
 * cleared and disabled for every other profile. RS512 is still supported for applications that cannot verify anything
 * stronger, but it should not be chosen for new ones, so selecting it reveals a warning.
 */
export default class extends Controller {
    static targets = ['signature', 'secret', 'secretField', 'warning'];

    declare readonly signatureTarget: HTMLSelectElement;
    declare readonly secretTarget: HTMLInputElement;
    declare readonly hasSecretFieldTarget: boolean;
    declare readonly secretFieldTarget: HTMLElement;
    declare readonly hasWarningTarget: boolean;
    declare readonly warningTarget: HTMLElement;

    connect(): void {
        this.apply();
    }

    apply(): void {
        const usesSecret = 'HS512' === this.signatureTarget.value;

        this.secretTarget.disabled = !usesSecret;
        if (!usesSecret) {
            this.secretTarget.value = '';
        }

        if (this.hasSecretFieldTarget) {
            this.secretFieldTarget.classList.toggle('opacity-50', !usesSecret);
        }

        if (this.hasWarningTarget) {
            this.warningTarget.classList.toggle('d-none', 'RS512' !== this.signatureTarget.value);
        }
    }
}
