/**
 * Drop the Bootstrap floating behaviour of the field that holds `element` and lift its `<label>` above it as a normal
 * caption. A floating label is absolutely positioned and would overlap a widget (a CKEditor instance) that replaces
 * the plain control it was written for.
 */
export function flattenFloatingLabel(element: Element): void {
    const wrapper = element.closest('.form-floating');
    if (null === wrapper) {
        return;
    }

    wrapper.classList.remove('form-floating');
    const label = wrapper.querySelector('label');
    if (null !== label) {
        label.classList.add('form-label');
        wrapper.prepend(label);
    }
}
