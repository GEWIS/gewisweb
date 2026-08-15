import { Controller } from '@hotwired/stimulus';
import { CkEditorInstance, CkEditorModule, FileLoader, UploadAdapter, createEditor } from '../../js/ckeditor.ts';
import { flattenFloatingLabel } from '../../js/floating_label.ts';

/**
 * Turns a `<textarea>` into a CKEditor 5 editor that writes HTML, for the custom pages. Everything else on the site is
 * written in Markdown (see markdown_editor_controller.ts); a page is laid out with the tables, columns and images
 * Markdown has no way to say.
 *
 *
 *   - the textarea: data-controller="page-editor"
 *   - the upload:   data-page-editor-upload-url-value / data-page-editor-upload-token-value
 */
export default class extends Controller {
    static values = {
        uploadUrl: String,
        uploadToken: String,
        language: { type: String, default: 'en' },
    };

    declare readonly uploadUrlValue: string;
    declare readonly uploadTokenValue: string;
    declare readonly languageValue: string;

    private editor: CkEditorInstance | null = null;
    private aborted = false;

    connect(): void {
        this.aborted = false;
        flattenFloatingLabel(this.textarea);
        void this.createEditor();
    }

    disconnect(): void {
        this.aborted = true;
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
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => this.uploadAdapter(loader);
    }

    /**
     * CKEditor's own upload adapter posts a field name and no CSRF token, neither of which the endpoint takes.
     */
    private uploadAdapter(loader: FileLoader): UploadAdapter {
        const controller = new AbortController();

        return {
            upload: async (): Promise<{ default: string }> => {
                const file = await loader.file;
                if (null === file) {
                    throw new Error('There is no file to upload.');
                }

                const body = new FormData();
                body.append('image', file);
                body.append('_csrf_token', this.uploadTokenValue);

                const response = await fetch(this.uploadUrlValue, {
                    method: 'POST',
                    body,
                    credentials: 'same-origin',
                    headers: { 'Sec-Fetch-Site': 'same-origin' },
                    signal: controller.signal,
                });

                const stored = (await response.json()) as { url?: string; error?: string };
                if (!response.ok || 'string' !== typeof stored.url) {
                    throw new Error(stored.error ?? 'The image could not be uploaded.');
                }

                return { default: stored.url };
            },
            abort: (): void => controller.abort(),
        };
    }

    // 'GPL' license key: valid for this GPL-3.0 project (CKEditor 5 >= v44 requires a key).
    private config(c: CkEditorModule): Record<string, unknown> {
        return {
            licenseKey: 'GPL',
            plugins: [
                c.Essentials, c.Paragraph, c.Heading, c.Autoformat, c.PasteFromOffice,
                c.Bold, c.Italic, c.Underline, c.Strikethrough, c.Subscript, c.Superscript,
                c.Code, c.CodeBlock, c.RemoveFormat,
                c.List, c.TodoList, c.Indent, c.IndentBlock, c.Alignment, c.HorizontalLine,
                c.Link, c.AutoLink, c.BlockQuote,
                c.Table, c.TableToolbar, c.TableCaption, c.TableProperties, c.TableCellProperties,
                c.Image, c.ImageToolbar, c.ImageCaption, c.ImageStyle, c.ImageResize, c.ImageUpload,
                c.FindAndReplace, c.SourceEditing, c.GeneralHtmlSupport,
            ],
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'removeFormat', '|',
                'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', 'alignment', '|',
                'link', 'blockQuote', 'insertTable', 'uploadImage', 'horizontalLine', 'code', 'codeBlock', '|',
                'findAndReplace', 'undo', 'redo', '|',
                'sourceEditing',
            ],
            // Whatever a page already holds is kept as it is, so opening an old page and saving it does not throw
            // half of it away. The sanitizer on save is the only thing that removes anything.
            htmlSupport: {
                allow: [{ name: /.*/, attributes: true, classes: true, styles: true }],
            },
            image: {
                toolbar: [
                    'imageTextAlternative', 'toggleImageCaption', '|',
                    'imageStyle:inline', 'imageStyle:block', 'imageStyle:side',
                ],
            },
            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells',
                    'tableProperties', 'tableCellProperties', 'toggleTableCaption',
                ],
            },
        };
    }
}
