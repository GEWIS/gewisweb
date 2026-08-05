import { Controller } from '@hotwired/stimulus';

/**
 * One request per file, like the album upload, so a file the server rejects reports itself on its own row and the rest
 * of the batch carries on.
 */
export default class extends Controller<HTMLElement> {
    static values = {
        url: String,
        token: String,
        concurrency: { type: Number, default: 3 },
    };

    static targets = ['dropzone', 'input', 'list'];

    declare readonly urlValue: string;
    declare readonly tokenValue: string;
    declare readonly concurrencyValue: number;
    declare readonly dropzoneTarget: HTMLElement;
    declare readonly hasInputTarget: boolean;
    declare readonly inputTarget: HTMLInputElement;
    declare readonly hasListTarget: boolean;
    declare readonly listTarget: HTMLElement;

    private readonly queue: File[] = [];
    private active = 0;
    private remaining = 0;
    private staged = 0;
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
            if (this._isPdf(file)) {
                this.remaining += 1;
                this.queue.push(file);
            }
        }

        this._pump();
    }

    private _isPdf(file: File): boolean {
        // The server re-validates the actual content, so an empty MIME type falls back to the extension.
        return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
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
        body.append('file', file);
        body.append('_csrf_token', this.tokenValue);

        const request = new XMLHttpRequest();
        this.requests.add(request);
        request.open('POST', this.urlValue);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        request.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                bar.style.width = `${Math.round((event.loaded / event.total) * 100)}%`;
            }
        });

        request.addEventListener('load', () => {
            this.requests.delete(request);
            this._settle(row, request.status >= 200 && request.status < 300, this._reason(request));
        });

        request.addEventListener('error', () => {
            this.requests.delete(request);
            this._settle(row, false, null);
        });

        request.addEventListener('abort', () => {
            this.requests.delete(request);
        });

        request.send(body);
    }

    // What the server said went wrong, so a rejected file says why rather than only that it failed.
    private _reason(request: XMLHttpRequest): string | null {
        try {
            const data: unknown = JSON.parse(request.responseText);
            if (typeof data === 'object' && null !== data && 'error' in data) {
                return String((data as { error: unknown }).error);
            }
        } catch {
            return null;
        }

        return null;
    }

    private _settle(row: HTMLElement, staged: boolean, reason: string | null): void {
        this.active -= 1;
        this.remaining -= 1;
        if (staged) {
            this.staged += 1;
        }

        this._markRow(row, staged, reason);

        if (this.remaining > 0) {
            this._pump();

            return;
        }

        // Everything that arrived has to appear in the publish forms below, which are server-rendered.
        if (this.staged > 0) {
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

    private _markRow(row: HTMLElement, staged: boolean, reason: string | null): void {
        const bar = row.querySelector<HTMLElement>('.progress-bar');
        const status = row.querySelector<HTMLElement>('i');
        if (null === bar || null === status) {
            return;
        }

        bar.style.width = '100%';
        if (staged) {
            bar.classList.add('bg-success');
            status.className = 'fa-solid fa-circle-check text-success flex-shrink-0';

            return;
        }

        bar.classList.add('bg-danger');
        status.className = 'fa-solid fa-circle-xmark text-danger flex-shrink-0';
        if (null !== reason) {
            status.title = reason;
        }
    }
}
