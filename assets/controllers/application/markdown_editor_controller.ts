import { Controller } from '@hotwired/stimulus';
import { CkEditorInstance, CkEditorModule, createEditor } from '../../js/ckeditor.ts';
import { flattenFloatingLabel } from '../../js/floating_label.ts';

/**
 * Turns a `<textarea>` into a CKEditor 5 Markdown editor (GFM, via the Markdown plugin). What is typed is written back
 * to the textarea, which stays the bound source of truth; see js/ckeditor.ts.
 *
 * The editor is built as the page opens, not when the box is first pressed: the bundle is large, but nearly every box
 * it is put on sits on a screen whose whole purpose is writing, and there a download in the way of the first keystroke
 * costs more than it saves.
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

        const editor = await createEditor(
            this.textarea,
            this.languageValue,
            (c) => this.config(c),
            () => this.aborted || null !== this.editor,
        );

        if (null === editor) {
            return;
        }

        this.editor = editor;
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
