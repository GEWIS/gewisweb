import { Controller } from '@hotwired/stimulus';

type LocalisedField = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

/**
 * Enable/disable the Dutch and English variants of localised fields with two language checkboxes.
 *
 * A disabled variant is not submitted, so an unchecked language keeps whatever it already had (empty for a new item,
 * the existing translation for an edit). Reused by every form with mirrored Dutch/English localised text: the activity
 * create/edit form and the Career company/vacancy forms.
 *
 * The markup tags elements generically so this controller needs no per-domain knowledge:
 *
 *   - the checkboxes:  data-localised-fields-target="dutchToggle" | "englishToggle"
 *                      data-action="localised-fields#apply"
 *   - the inputs:      data-localised-fields-target="dutch" | "english"
 */
export default class extends Controller {
    static targets = ['dutchToggle', 'englishToggle', 'dutch', 'english'];

    declare readonly hasDutchToggleTarget: boolean;
    declare readonly dutchToggleTarget: HTMLInputElement;
    declare readonly hasEnglishToggleTarget: boolean;
    declare readonly englishToggleTarget: HTMLInputElement;
    declare readonly dutchTargets: LocalisedField[];
    declare readonly englishTargets: LocalisedField[];

    connect(): void {
        this.apply();
    }

    apply(): void {
        this.dutchTargets.forEach((field) => { field.disabled = !this.dutchEnabled(); });
        this.englishTargets.forEach((field) => { field.disabled = !this.englishEnabled(); });
    }

    dutchTargetConnected(field: LocalisedField): void {
        field.disabled = !this.dutchEnabled();
    }

    englishTargetConnected(field: LocalisedField): void {
        field.disabled = !this.englishEnabled();
    }

    dutchEnabled(): boolean {
        return !this.hasDutchToggleTarget || this.dutchToggleTarget.checked;
    }

    englishEnabled(): boolean {
        return !this.hasEnglishToggleTarget || this.englishToggleTarget.checked;
    }
}
