import { Controller } from '@hotwired/stimulus';
import type { ActionEvent } from '@hotwired/stimulus';

/**
 * Page a single long form into sequential steps without changing how it submits: every step stays in the DOM (hidden,
 * never disabled), so the one POST still carries all fields and sibling controllers (localised-fields, edit-lock) keep
 * seeing every input. On (re)connect the first step that contains a validation error is opened, so a rejected submit
 * lands the user where the problem is.
 *
 * ```
 * <div data-controller="form-stepper">
 *     <ul class="nav nav-tabs">
 *         <button data-form-stepper-target="tab" data-action="form-stepper#goto" data-form-stepper-index-param="0">
 *             ...
 *         </button>
 *         ...
 *     </ul>
 *     <div data-form-stepper-target="step">...</div>
 *     ...
 *     <button data-form-stepper-target="back" data-action="form-stepper#previous">Back</button>
 *     <button data-form-stepper-target="next" data-action="form-stepper#next">Next</button>
 *     <button type="submit" data-form-stepper-target="submit">Save</button>
 *   </div>
 * ```
 */
export default class extends Controller {
    static targets = ['step', 'tab', 'back', 'next', 'submit'];

    declare readonly stepTargets: HTMLElement[];
    declare readonly tabTargets: HTMLElement[];
    declare readonly hasBackTarget: boolean;
    declare readonly backTarget: HTMLElement;
    declare readonly hasNextTarget: boolean;
    declare readonly nextTarget: HTMLElement;
    declare readonly hasSubmitTarget: boolean;
    declare readonly submitTarget: HTMLButtonElement;

    private current = 0;
    private initialised = false;

    connect(): void {
        this.current = this.firstStepWithError();
        this.render();
    }

    firstStepWithError(): number {
        const index = this.stepTargets.findIndex(
            (step) => null !== step.querySelector('.is-invalid, .invalid-feedback'),
        );

        return index >= 0 ? index : 0;
    }

    next(): void {
        if (this.current < this.stepTargets.length - 1) {
            this.current += 1;
            this.render();
        }
    }

    previous(): void {
        if (this.current > 0) {
            this.current -= 1;
            this.render();
        }
    }

    goto(event: ActionEvent): void {
        this.current = event.params.index;
        this.render();
    }

    render(): void {
        const last = this.stepTargets.length - 1;

        this.stepTargets.forEach((step, index) => { step.hidden = index !== this.current; });
        this.tabTargets.forEach((tab, index) => {
            tab.classList.toggle('active', index === this.current);
            tab.classList.toggle('is-complete', index < this.current);
            if (index === this.current) {
                tab.setAttribute('aria-current', 'step');
            } else {
                tab.removeAttribute('aria-current');
            }
        });

        if (this.hasBackTarget) {
            this.backTarget.hidden = 0 === this.current;
        }
        if (this.hasNextTarget) {
            this.nextTarget.hidden = this.current === last;
        }
        if (this.hasSubmitTarget) {
            // Hidden AND disabled on non-final steps: a hidden submit button is still the form's default button for
            // implicit submission (Enter), so disabling it keeps an earlier step from POSTing the form prematurely.
            this.submitTarget.hidden = this.current !== last;
            this.submitTarget.disabled = this.current !== last;
        }

        // Scroll back to the step header on navigation, but leave the initial paint where the user is.
        if (this.initialised) {
            this.element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        this.initialised = true;
    }
}
