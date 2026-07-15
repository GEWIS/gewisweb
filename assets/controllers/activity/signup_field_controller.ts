import { Controller } from '@hotwired/stimulus';

/**
 * One custom sign-up field: only a "number" field shows the min/max bounds and only a "choice" field shows the options
 * collection. Switching to "choice" seeds one empty option (so the editor is not left with an empty choice); switching
 * away clears the options again. Bound to the type `<select>`. It also keeps the choice options' "default" checkboxes
 * mutually exclusive, so at most one option per field is preselected on the public sign-up form.
 *
 * ```
 * <div data-controller="signup-field">
 *     <select data-signup-field-target="type" data-action="change->signup-field#typeChanged">...</select>
 *     <div data-signup-field-target="number">...min/max...</div>
 *     <div data-signup-field-target="choice">...options collection, each with
 *         <input type="checkbox" data-signup-field-target="defaultOption"
 *                data-action="change->signup-field#defaultChanged">...</div>
 * </div>
 * ```
 */
export default class extends Controller {
    static targets = ['type', 'number', 'choice', 'defaultOption'];

    declare readonly typeTarget: HTMLSelectElement;
    declare readonly hasNumberTarget: boolean;
    declare readonly numberTarget: HTMLElement;
    declare readonly hasChoiceTarget: boolean;
    declare readonly choiceTarget: HTMLElement;
    declare readonly defaultOptionTargets: HTMLInputElement[];

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

    /**
     * The "default" checkboxes act as a radio group across this field's options: checking one clears the rest, so at
     * most one option is preselected (none is allowed). Dynamically added options register as targets automatically.
     */
    defaultChanged(event: Event): void {
        const changed = event.target;
        if (
            !(changed instanceof HTMLInputElement)
            || !changed.checked
        ) {
            return;
        }

        this.defaultOptionTargets.forEach((checkbox) => {
            if (checkbox === changed) {
                return;
            }

            checkbox.checked = false;
        });
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
