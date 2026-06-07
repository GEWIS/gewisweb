import { Controller } from '@hotwired/stimulus';

/**
 * One sign-up list's settings. The capacity + allocation-method block only apply when "limited capacity" is checked,
 * and within it only the selected method's settings (and, for a conditional draw, only the selected cutoff rule's
 * sub-field) are shown. Bound to the limited checkbox, the method select and the cutoff-rule select.
 *
 *   <div data-controller="signup-list">
 *     <input type="checkbox" data-signup-list-target="limited" data-action="change->signup-list#apply">
 *     <div data-signup-list-target="capacity">…capacity…</div>
 *     <div data-signup-list-target="methodBlock">
 *       <select data-signup-list-target="method" data-action="change->signup-list#apply">…</select>
 *       <div data-signup-list-target="conditional">
 *         <select data-signup-list-target="rule" data-action="change->signup-list#apply">…</select>
 *         <div data-signup-list-target="cutoffAt">…</div>
 *         <div data-signup-list-target="durationHours">…</div>
 *       </div>
 *       <div data-signup-list-target="external">…</div>
 *       <div data-signup-list-target="custom">…</div>
 *     </div>
 *   </div>
 */
export default class extends Controller {
    static targets = [
        'limited', 'capacity', 'methodBlock', 'method',
        'conditional', 'rule', 'cutoffAt', 'durationHours', 'external', 'custom',
    ];

    connect() {
        this.apply();
    }

    apply() {
        const limited = this.hasLimitedTarget && this.limitedTarget.checked;
        const method = this.hasMethodTarget ? this.methodTarget.value : '';
        const rule = this.hasRuleTarget ? this.ruleTarget.value : '';
        const conditional = limited && 'conditional-draw' === method;

        this.setHidden('capacity', !limited);
        this.setHidden('methodBlock', !limited);
        this.setHidden('conditional', !conditional);
        this.setHidden('external', !(limited && 'external-party' === method));
        this.setHidden('custom', !(limited && 'custom' === method));
        this.setHidden('cutoffAt', !(conditional && 'if-full-before' === rule));
        this.setHidden('durationHours', !(conditional && 'after-duration-open' === rule));
    }

    setHidden(name, hidden) {
        const capitalised = name.charAt(0).toUpperCase() + name.slice(1);
        if (this[`has${capitalised}Target`]) {
            this[`${name}Target`].hidden = hidden;
        }
    }
}
