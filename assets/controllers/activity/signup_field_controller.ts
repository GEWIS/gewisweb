import { Controller } from '@hotwired/stimulus';

/**
 * One custom sign-up field: only a "number" field shows the min/max bounds and only a "choice" field shows the options
 * collection. Switching to "choice" seeds one empty option (so the editor is not left with an empty choice); switching
 * away clears the options again. Bound to the type `<select>`.
 *
 * ```
 * <div data-controller="signup-field">
 *     <select data-signup-field-target="type" data-action="change->signup-field#typeChanged">...</select>
 *     <div data-signup-field-target="number">...min/max...</div>
 *     <div data-signup-field-target="choice">...options collection (form-collection)...</div>
 * </div>
 * ```
 */
export default class extends Controller {
    static targets = ['type', 'number', 'choice'];

    declare readonly typeTarget: HTMLSelectElement;
    declare readonly hasNumberTarget: boolean;
    declare readonly numberTarget: HTMLElement;
    declare readonly hasChoiceTarget: boolean;
    declare readonly choiceTarget: HTMLElement;

    connect(): void {
        // Only set visibility on connect (an existing field keeps its saved options); seeding/clearing is user-driven.
        this.apply();
    }

    typeChanged(): void {
        if ('choice' === this.typeTarget.value) {
            if (0 === this.optionEntries().length) {
                this.addOption();
            }
        } else {
            this.clearOptions();
        }

        this.apply();
    }

    apply(): void {
        const type = this.typeTarget.value;

        if (this.hasNumberTarget) {
            this.numberTarget.hidden = 'number' !== type;
        }

        if (this.hasChoiceTarget) {
            this.choiceTarget.hidden = 'choice' !== type;
        }
    }

    optionEntries(): HTMLElement[] {
        if (!this.hasChoiceTarget) {
            return [];
        }

        return Array.from(this.choiceTarget.querySelectorAll<HTMLElement>('[data-form-collection-target="entry"]'));
    }

    addOption(): void {
        // Reuse the options' own form-collection "add" button so the prototype/index logic stays in one place.
        const addButton = this.choiceTarget.querySelector<HTMLButtonElement>('[data-action~="form-collection#add"]');
        if (null !== addButton) {
            addButton.click();
        }
    }

    clearOptions(): void {
        this.optionEntries().forEach((entry) => {
            entry.remove();
        });
    }
}
