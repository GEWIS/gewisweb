import { Controller } from '@hotwired/stimulus';

/**
 * A single-PDF upload flow for the meeting management pages: a dropzone (or "New version" button) that, once a file
 * is picked, reveals a small confirm area where the name and/or version label can be adjusted before the XHR posts to
 * the endpoint. On success a bubbling `decision-upload:success` event lets the surrounding live component re-render.
 *
 * The name input is optional: a new-document dropzone has one (pre-filled from the filename), a new-version flow does
 * not. Extra fixed fields (such as the agenda point id) come from the `extra` value.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['input', 'picker', 'form', 'filename', 'name', 'label', 'error'];
    static values = {
        url: String,
        suggestedLabel: String,
        extra: { type: Object, default: {} },
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly hasPickerTarget: boolean;
    declare readonly pickerTarget: HTMLElement;
    declare readonly formTarget: HTMLElement;
    declare readonly hasFilenameTarget: boolean;
    declare readonly filenameTarget: HTMLElement;
    declare readonly hasNameTarget: boolean;
    declare readonly nameTarget: HTMLInputElement;
    declare readonly labelTarget: HTMLInputElement;
    declare readonly hasErrorTarget: boolean;
    declare readonly errorTarget: HTMLElement;

    declare readonly urlValue: string;
    declare readonly suggestedLabelValue: string;
    declare readonly extraValue: Record<string, string>;

    private file: File | null = null;

    browse(): void {
        this.inputTarget.click();
    }

    picked(): void {
        const file = this.inputTarget.files?.item(0) ?? null;
        if (null === file) {
            return;
        }

        this.prepare(file);
    }

    dragOver(event: DragEvent): void {
        event.preventDefault();
        this.element.classList.add('upload-dragover');
    }

    dragLeave(): void {
        this.element.classList.remove('upload-dragover');
    }

    dropped(event: DragEvent): void {
        event.preventDefault();
        this.element.classList.remove('upload-dragover');

        const file = event.dataTransfer?.files.item(0) ?? null;
        if (null === file) {
            return;
        }

        this.prepare(file);
    }

    cancel(): void {
        this.file = null;
        this.inputTarget.value = '';
        this.formTarget.classList.add('d-none');
        if (this.hasPickerTarget) {
            this.pickerTarget.classList.remove('d-none');
        }
    }

    async submit(): Promise<void> {
        if (null === this.file) {
            return;
        }

        const body = new FormData();
        body.append('file', this.file);
        body.append('versionLabel', this.labelTarget.value.trim());
        if (this.hasNameTarget) {
            body.append('name', this.nameTarget.value.trim());
        }

        for (const [key, value] of Object.entries(this.extraValue)) {
            body.append(key, String(value));
        }

        const response = await fetch(this.urlValue, {
            method: 'POST',
            body,
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            const payload = (await response.json().catch(() => null)) as { error?: string } | null;
            this.showError(payload?.error ?? response.statusText);

            return;
        }

        this.cancel();
        this.dispatch('success', { prefix: 'decision-upload', bubbles: true });
    }

    private prepare(file: File): void {
        this.file = file;

        if (this.hasErrorTarget) {
            this.errorTarget.classList.add('d-none');
        }

        if (this.hasFilenameTarget) {
            this.filenameTarget.textContent = file.name;
        }

        if (this.hasNameTarget && '' === this.nameTarget.value.trim()) {
            this.nameTarget.value = file.name.replace(/\.pdf$/i, '');
        }

        if ('' === this.labelTarget.value.trim()) {
            this.labelTarget.value = this.suggestedLabelValue;
        }

        if (this.hasPickerTarget) {
            this.pickerTarget.classList.add('d-none');
        }

        this.formTarget.classList.remove('d-none');
    }

    private showError(message: string): void {
        if (!this.hasErrorTarget) {
            return;
        }

        this.errorTarget.textContent = message;
        this.errorTarget.classList.remove('d-none');
    }
}
