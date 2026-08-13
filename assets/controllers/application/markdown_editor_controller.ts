import { Controller } from '@hotwired/stimulus';
import { flattenFloatingLabel } from '../../js/floating_label.ts';

// Minimal surface of CKEditor 5 we use. We vendor the official self-contained browser ESM bundle
// (assets/js/ckeditor5/ckeditor5.js, mapped as `ckeditor5` in importmap.php) instead of resolving the npm package
// through the importmap: the package's entry re-exports the @ckeditor/* source tree, which the jsDelivr resolver
// cannot ESM-ify. The browser bundle inlines everything (no dependency tree to keep patched).
interface CkEditorInstance {
    getData(): string;
    setData(data: string): void;
    destroy(): Promise<unknown>;
    enableReadOnlyMode(lockId: string): void;
    disableReadOnlyMode(lockId: string): void;
    model: { document: { on(event: string, callback: () => void): void } };
}

interface CkEditorModule {
    ClassicEditor: {
        create(element: HTMLElement, config: Record<string, unknown>): Promise<CkEditorInstance>;
    };
    [exportName: string]: unknown;
}

/**
 * Turns a `<textarea>` into a CKEditor 5 Markdown editor. The textarea stays in the DOM as the bound source of truth:
 * the editor's Markdown (GFM, via the Markdown plugin) is written back to it and a bubbling `input` event is fired, so
 * both a Symfony form POST and a Live Component `data-model` binding keep working.
 *
 * The bundle is loaded with a dynamic `import()` on first use, keeping ~1.9 MB off pages without an editor.
 *
 * `data-markdown-editor-toolbar-value="minimal"` selects the restricted toolbar (the sign-up email) and `"comment"`
 * the four inline marks a poll comment may carry; the default is the full toolbar (activity descriptions).
 *
 * `clear()` empties the editor, for a form that is not reloaded after it is sent: a live component that keeps its
 * textarea behind `data-live-ignore` cannot empty it through a re-render, so it says so with a browser event instead
 * (`data-action="poll-comment:posted@window->markdown-editor#clear"`).
 *
 * Coordinates with the `localised-fields` controller without coupling to it: when that disables the textarea (an
 * unchecked language is not submitted), a MutationObserver puts the editor into read-only mode; the disabled textarea
 * is still omitted from the POST, so the stored value is preserved.
 */
export default class extends Controller {
    static values = {
        toolbar: { type: String, default: 'full' },
        language: { type: String, default: 'en' },
    };

    declare readonly toolbarValue: string;
    declare readonly languageValue: string;

    private editor: CkEditorInstance | null = null;
    private observer: MutationObserver | null = null;
    private aborted = false;
    private readonly readOnlyLock = 'localised-fields-disabled';

    connect(): void {
        this.aborted = false;
        flattenFloatingLabel(this.textarea);
        this.observer = new MutationObserver(() => this.applyDisabledState());
        this.observer.observe(this.textarea, { attributes: true, attributeFilter: ['disabled'] });
        void this.createEditor();
    }

    disconnect(): void {
        this.aborted = true;
        this.observer?.disconnect();
        this.observer = null;
        void this.editor?.destroy();
        this.editor = null;
    }

    private get textarea(): HTMLTextAreaElement {
        return this.element as HTMLTextAreaElement;
    }

    private async createEditor(): Promise<void> {
        if (null !== this.editor || this.aborted) {
            return;
        }

        const ckeditor = (await import('ckeditor5')) as unknown as CkEditorModule;
        // Re-check after the await: the controller may have disconnected meanwhile.
        if (null !== this.editor || this.aborted) {
            return;
        }

        const config = this.config(ckeditor);
        if ('nl' === this.languageValue) {
            // The bundle ships English built in; the Dutch UI comes from a separate translations module.
            const dutch = await import('ckeditor5/translations/nl.js');
            if (null !== this.editor || this.aborted) {
                return;
            }
            config.language = 'nl';
            config.translations = [(dutch as { default: unknown }).default];
        }

        const editor = await ckeditor.ClassicEditor.create(this.textarea, config);
        if (this.aborted) {
            void editor.destroy();
            return;
        }

        this.editor = editor;
        editor.model.document.on('change:data', () => {
            this.textarea.value = editor.getData();
            // Bubbles past a `data-live-ignore` boundary to the Live Component root, and is carried on form submit.
            this.textarea.dispatchEvent(new Event('input', { bubbles: true }));
        });
        this.applyDisabledState();
    }

    clear(): void {
        this.editor?.setData('');
        this.textarea.value = '';
    }

    private applyDisabledState(): void {
        if (null === this.editor) {
            return;
        }

        if (this.textarea.disabled) {
            this.editor.enableReadOnlyMode(this.readOnlyLock);
        } else {
            this.editor.disableReadOnlyMode(this.readOnlyLock);
        }
    }

    // The Markdown plugin makes getData() and the initial data GFM Markdown whichever toolbar is asked for.
    // 'GPL' license key: valid for this GPL-3.0 project (CKEditor 5 >= v44 requires a key).
    private config(c: CkEditorModule): Record<string, unknown> {
        // A poll comment is a sentence or two, not a document, and every button beyond these four invites one to be
        // written as if it were.
        if ('comment' === this.toolbarValue) {
            return {
                licenseKey: 'GPL',
                plugins: [c.Essentials, c.Paragraph, c.Bold, c.Italic, c.Underline, c.Strikethrough, c.Markdown],
                toolbar: ['bold', 'italic', 'underline', 'strikethrough'],
            };
        }

        if ('minimal' === this.toolbarValue) {
            return {
                licenseKey: 'GPL',
                plugins: [
                    c.Essentials, c.Paragraph, c.Bold, c.Italic, c.Strikethrough,
                    c.List, c.Link, c.AutoLink, c.Markdown,
                ],
                toolbar: ['bold', 'italic', 'strikethrough', '|', 'bulletedList', 'numberedList', '|', 'link'],
            };
        }

        return {
            licenseKey: 'GPL',
            plugins: [
                c.Essentials, c.Paragraph, c.Heading,
                c.Bold, c.Italic, c.Strikethrough, c.Code, c.RemoveFormat,
                c.List, c.HorizontalLine,
                c.Link, c.AutoLink, c.BlockQuote,
                c.Table, c.TableToolbar,
                c.FindAndReplace, c.SourceEditing, c.Autoformat, c.Markdown,
            ],
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'strikethrough', 'removeFormat', '|',
                'bulletedList', 'numberedList', 'horizontalLine', '|',
                'link', 'blockQuote', 'insertTable', 'code', '|',
                'findAndReplace', 'undo', 'redo', '|',
                'sourceEditing',
            ],
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
            },
        };
    }
}
