import { Controller } from '@hotwired/stimulus';

/**
 * Drag-and-drop photo uploads for the album manage view. Files are posted one request per file (with a small
 * concurrency cap) so a single rejected file never aborts the batch and each file gets its own progress bar. The
 * endpoint is board-guarded and takes no CSRF token (it is not a form submit); authentication is enforced server-side.
 * Once every file has settled the page reloads if anything was created, so the new photos (and the regenerated cover)
 * appear.
 *
 *   <div data-controller="photo-upload" data-photo-upload-upload-url-value="...">
 *       <div data-photo-upload-target="dropzone"> ... </div>
 *       <input type="file" hidden multiple data-photo-upload-target="input">
 *       <ul data-photo-upload-target="list"></ul>
 *   </div>
 */
export default class extends Controller<HTMLElement> {
    static values = {
        uploadUrl: String,
        concurrency: { type: Number, default: 3 },
    };

    static targets = ['dropzone', 'input', 'list'];

    declare readonly uploadUrlValue: string;
    declare readonly concurrencyValue: number;
    declare readonly dropzoneTarget: HTMLElement;
    declare readonly hasInputTarget: boolean;
    declare readonly inputTarget: HTMLInputElement;
    declare readonly hasListTarget: boolean;
    declare readonly listTarget: HTMLElement;

    private readonly queue: File[] = [];
    private active = 0;
    private remaining = 0;
    private created = 0;
    private readonly requests = new Set<XMLHttpRequest>();

    private readonly _onDragOver = (event: DragEvent): void => {
        event.preventDefault();
        this.dropzoneTarget.classList.add('is-dragging');
    };

    private readonly _onDragLeave = (): void => {
        this.dropzoneTarget.classList.remove('is-dragging');
    };

    private readonly _onDrop = (event: DragEvent): void => {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('is-dragging');
        this._enqueue(event.dataTransfer?.files ?? null);
    };

    private readonly _onClick = (): void => {
        if (this.hasInputTarget) {
            this.inputTarget.click();
        }
    };

    private readonly _onKeydown = (event: KeyboardEvent): void => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this._onClick();
        }
    };

    private readonly _onChange = (): void => {
        if (!this.hasInputTarget) {
            return;
        }

        this._enqueue(this.inputTarget.files);
        this.inputTarget.value = '';
    };

    connect(): void {
        this.dropzoneTarget.addEventListener('dragover', this._onDragOver);
        this.dropzoneTarget.addEventListener('dragleave', this._onDragLeave);
        this.dropzoneTarget.addEventListener('drop', this._onDrop);
        this.dropzoneTarget.addEventListener('click', this._onClick);
        this.dropzoneTarget.addEventListener('keydown', this._onKeydown);
        if (this.hasInputTarget) {
            this.inputTarget.addEventListener('change', this._onChange);
        }
    }

    disconnect(): void {
        this.dropzoneTarget.removeEventListener('dragover', this._onDragOver);
        this.dropzoneTarget.removeEventListener('dragleave', this._onDragLeave);
        this.dropzoneTarget.removeEventListener('drop', this._onDrop);
        this.dropzoneTarget.removeEventListener('click', this._onClick);
        this.dropzoneTarget.removeEventListener('keydown', this._onKeydown);
        if (this.hasInputTarget) {
            this.inputTarget.removeEventListener('change', this._onChange);
        }

        // Abort in-flight uploads so their callbacks cannot run against a detached element.
        this.requests.forEach((request) => request.abort());
        this.requests.clear();
    }

    private _enqueue(files: FileList | null): void {
        if (null === files) {
            return;
        }

        for (const file of Array.from(files)) {
            if (this._isImage(file)) {
                this.remaining += 1;
                this.queue.push(file);
            }
        }

        this._pump();
    }

    private _isImage(file: File): boolean {
        if (file.type.startsWith('image/')) {
            return true;
        }

        // Browsers commonly report an empty MIME type for HEIC/HEIF, so fall back to the extension; the server
        // re-validates the actual content regardless.
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

        return ['heic', 'heif', 'jpg', 'jpeg', 'png', 'webp', 'avif'].includes(extension);
    }

    private _pump(): void {
        while (this.active < this.concurrencyValue && this.queue.length > 0) {
            const file = this.queue.shift();
            if (file !== undefined) {
                this._upload(file);
            }
        }
    }

    private _upload(file: File): void {
        this.active += 1;
        const [row, bar] = this._addRow(file.name);

        const body = new FormData();
        body.append('photos[]', file);

        const request = new XMLHttpRequest();
        this.requests.add(request);
        request.open('POST', this.uploadUrlValue);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        request.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                bar.style.width = `${Math.round((event.loaded / event.total) * 100)}%`;
            }
        });

        request.addEventListener('load', () => {
            this.requests.delete(request);
            this._settle(row, this._outcome(request));
        });

        request.addEventListener('error', () => {
            this.requests.delete(request);
            this._settle(row, 'failed');
        });

        request.addEventListener('abort', () => {
            this.requests.delete(request);
        });

        request.send(body);
    }

    // Read the JSON summary; with one file per request exactly one counter is non-zero.
    private _outcome(request: XMLHttpRequest): 'created' | 'duplicate' | 'failed' {
        if (request.status < 200 || request.status >= 300) {
            return 'failed';
        }

        try {
            const data: unknown = JSON.parse(request.responseText);
            if (typeof data === 'object' && null !== data) {
                const summary = data as { created?: number; duplicates?: number };
                if ((summary.created ?? 0) > 0) {
                    return 'created';
                }

                if ((summary.duplicates ?? 0) > 0) {
                    return 'duplicate';
                }
            }
        } catch {
            return 'failed';
        }

        return 'failed';
    }

    private _settle(row: HTMLElement, outcome: 'created' | 'duplicate' | 'failed'): void {
        this.active -= 1;
        this.remaining -= 1;
        if (outcome === 'created') {
            this.created += 1;
        }

        this._markRow(row, outcome);

        if (this.remaining > 0) {
            this._pump();

            return;
        }

        // The whole batch has settled: reload so the newly stored photos show up, but only if any were created.
        if (this.created > 0) {
            window.location.reload();
        }
    }

    private _addRow(name: string): [HTMLElement, HTMLElement] {
        const row = document.createElement('li');
        row.className = 'd-flex align-items-center gap-2 mb-2';

        const label = document.createElement('span');
        label.className = 'text-truncate small flex-shrink-0';
        label.style.maxWidth = '12rem';
        label.textContent = name;

        const progress = document.createElement('div');
        progress.className = 'progress flex-grow-1';
        progress.style.height = '0.5rem';

        const bar = document.createElement('div');
        bar.className = 'progress-bar';
        bar.style.width = '0%';
        progress.appendChild(bar);

        const status = document.createElement('i');
        status.className = 'fa-solid fa-spinner fa-spin text-muted flex-shrink-0';

        row.append(label, progress, status);
        if (this.hasListTarget) {
            this.listTarget.appendChild(row);
        }

        return [row, bar];
    }

    private _markRow(row: HTMLElement, outcome: 'created' | 'duplicate' | 'failed'): void {
        const bar = row.querySelector<HTMLElement>('.progress-bar');
        const status = row.querySelector<HTMLElement>('i');
        if (null === bar || null === status) {
            return;
        }

        bar.style.width = '100%';
        if (outcome === 'created') {
            bar.classList.add('bg-success');
            status.className = 'fa-solid fa-circle-check text-success flex-shrink-0';
        } else if (outcome === 'duplicate') {
            bar.classList.add('bg-warning');
            status.className = 'fa-solid fa-clone text-warning flex-shrink-0';
        } else {
            bar.classList.add('bg-danger');
            status.className = 'fa-solid fa-circle-xmark text-danger flex-shrink-0';
        }
    }
}
