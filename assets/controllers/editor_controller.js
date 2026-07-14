import { Controller } from '@hotwired/stimulus';
import EditorJS from '@editorjs/editorjs';
import AttachesTool from '@editorjs/attaches';
import Header from '@editorjs/header';
import ImageTool from '@editorjs/image';
import List from '@editorjs/list';
import Quote from '@editorjs/quote';

/*
 * Mounts Editor.js on a form field. The block data (JSON) lives in a hidden
 * input; on submit we intercept, await editor.save(), write the JSON into the
 * input and re-submit (Turbo picks up the second, non-prevented submit).
 */
export default class extends Controller {
    static targets = ['input', 'holder'];
    static values = { uploadUrl: String, attachUrl: String };

    connect() {
        this.synced = false;
        this.form = this.element.closest('form');

        this.editor = new EditorJS({
            holder: this.holderTarget,
            data: this.#initialData(),
            placeholder: 'Start writing the course content…',
            tools: this.#tools(),
        });

        this.onSubmit = this.#syncBeforeSubmit.bind(this);
        this.form?.addEventListener('submit', this.onSubmit);
    }

    disconnect() {
        this.form?.removeEventListener('submit', this.onSubmit);
        this.editor?.destroy();
    }

    async #syncBeforeSubmit(event) {
        if (this.synced) {
            this.synced = false;

            return;
        }

        event.preventDefault();

        const output = await this.editor.save();
        this.inputTarget.value = output.blocks.length > 0 ? JSON.stringify(output) : '';

        this.synced = true;
        this.form.requestSubmit(event.submitter);
    }

    #tools() {
        const tools = {
            header: { class: Header, inlineToolbar: true, config: { levels: [2, 3, 4], defaultLevel: 2 } },
            list: { class: List, inlineToolbar: true },
            quote: { class: Quote, inlineToolbar: true },
        };

        if (this.hasUploadUrlValue && '' !== this.uploadUrlValue) {
            tools.image = {
                class: ImageTool,
                config: {
                    endpoints: { byFile: this.uploadUrlValue },
                    additionalRequestHeaders: { 'X-Requested-With': 'XMLHttpRequest' },
                    types: 'image/png,image/jpeg,image/gif,image/webp',
                },
            };
        }

        if (this.hasAttachUrlValue && '' !== this.attachUrlValue) {
            tools.attaches = {
                class: AttachesTool,
                config: {
                    endpoint: this.attachUrlValue,
                    additionalRequestHeaders: { 'X-Requested-With': 'XMLHttpRequest' },
                    buttonText: 'Attach a file',
                    errorMessage: 'File upload failed',
                },
            };
        }

        return tools;
    }

    #initialData() {
        const value = this.inputTarget.value.trim();

        if ('' === value) {
            return undefined;
        }

        try {
            return JSON.parse(value);
        } catch {
            return undefined;
        }
    }
}
