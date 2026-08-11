import { Controller } from '@hotwired/stimulus';

type Rectangle = { x: number; y: number; width: number; height: number };

/**
 * Pick the part of an image that is actually shown, and write it back as fractions of that image.
 *
 * Fractions rather than pixels: the frame is drawn on whichever rendition of the original fits on the page, or on the
 * file the browser has just been handed, and the server applies it to the original either way. Everything is measured
 * against the image's own box within the canvas, so it does not matter how the image sits in there.
 *
 * The frame cannot leave the image. Cropper draws it on the canvas, which is the wider box the image is fitted into, so
 * without refusing the changes that would take it off the picture it can be dragged onto the empty margins beside it and
 * ask for a crop of nothing.
 *
 * The markup:
 *
 *   - the file input:  data-image-crop-target="file"  data-action="image-crop#choose"
 *   - the frame:       data-image-crop-target="frame" (hidden until there is something to frame)
 *   - the image:       data-image-crop-target="image"
 *   - the boxes:       data-image-crop-target="x" | "y" | "width" | "height"   (hidden inputs)
 *   - the shape:       data-image-crop-ratio-value="4"                         (width divided by height)
 *   - the crop:        data-image-crop-rectangle-value="{...}"                 (the one in force, if any)
 *
 * The crop that was chosen before is restored, so re-opening the form shows the frame that is in force rather than
 * starting over. Choosing a new file starts a fresh frame, since the old one described an image that is being
 * replaced.
 *
 * Everything here is a convenience: with no JavaScript at all the file still uploads and is stored whole.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['file', 'frame', 'image', 'message', 'preview', 'x', 'y', 'width', 'height'];

    static values = {
        ratio: Number,
        minimumWidth: Number,
        minimumHeight: Number,
        sourceWidth: Number,
        rectangle: Object,
    };

    declare readonly hasFileTarget: boolean;
    declare readonly fileTarget: HTMLInputElement;
    declare readonly hasFrameTarget: boolean;
    declare readonly frameTarget: HTMLElement;
    declare readonly hasImageTarget: boolean;
    declare readonly imageTarget: HTMLImageElement;
    declare readonly hasMessageTarget: boolean;
    declare readonly messageTarget: HTMLElement;
    declare readonly hasPreviewTarget: boolean;
    declare readonly previewTarget: HTMLElement;
    declare readonly xTarget: HTMLInputElement;
    declare readonly yTarget: HTMLInputElement;
    declare readonly widthTarget: HTMLInputElement;
    declare readonly heightTarget: HTMLInputElement;
    declare readonly ratioValue: number;
    declare readonly minimumWidthValue: number;
    declare readonly minimumHeightValue: number;
    declare readonly sourceWidthValue: number;
    declare readonly rectangleValue: Partial<Rectangle>;

    private cropper?: {
        getCropperImage(): (Element & { $ready(): Promise<unknown>; $center(size: string): void }) | null;
        getCropperSelection(): Element | null;
        destroy(): void;
    };

    private selection?: Element;

    private observer?: ResizeObserver;

    /** How wide the file the reader picked is, once one has been picked, which beats anything the server said. */
    private picked?: number;

    /** Set while the frame is being put back on the image, so that correction is not itself corrected. */
    private correcting = false;

    connect(): void {
        // Only an image that is already there is framed on connect; a file the reader picks is handled by choose().
        // An image with nothing to show carries no src at all, which reads as null rather than as an empty one.
        if (!this.hasImageTarget || !this.imageTarget.getAttribute('src')) {
            return;
        }

        void this.frame(true);
    }

    disconnect(): void {
        this.teardown();
    }

    /**
     * The reader picked a file. Show it straight away and frame it, so choosing one visibly does something long before
     * anything is saved.
     */
    choose(): void {
        const file = this.fileTarget.files?.[0];
        if (undefined === file) {
            return;
        }

        this.say(false);

        // Read into a data URL rather than handing over an object URL: the policy the site sends allows images from
        // data: but not from blob:, and the browser would refuse to show the one thing the reader just picked.
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            void this.show(String(reader.result));
        }, { once: true });
        reader.readAsDataURL(file);
    }

    /**
     * Show the picked file, unless it is narrower than the server is going to accept: framing an image that is turned
     * away on saving reads as a promise that it was fine.
     */
    private async show(source: string): Promise<void> {
        const probe = new Image();
        probe.src = source;
        await probe.decode().catch(() => undefined);

        // Nothing else changes on a refusal, so a frame that was already drawn keeps standing and the crop it holds is
        // still the one that is saved.
        if (probe.naturalWidth > 0 && this.tooSmall(probe)) {
            this.fileTarget.value = '';
            this.say(true);

            return;
        }

        this.teardown();
        this.clear();

        this.picked = probe.naturalWidth;
        this.imageTarget.src = source;

        if (this.hasFrameTarget) {
            this.frameTarget.hidden = false;
        }

        void this.frame(false);
    }

    private tooSmall(image: HTMLImageElement): boolean {
        return image.naturalWidth < this.minimumWidthValue
            || image.naturalHeight < this.minimumHeightValue;
    }

    private say(refused: boolean): void {
        this.fileTarget.classList.toggle('is-invalid', refused);

        if (!this.hasMessageTarget) {
            return;
        }

        this.messageTarget.classList.toggle('d-none', !refused);
    }

    /**
     * Build the cropper once the image knows its own size, which is what every measurement here depends on.
     */
    private async frame(restore: boolean): Promise<void> {
        const saved = restore ? this.stored() : null;

        await this.imageLoaded();

        // The images sit on a step of the form that starts out closed, where nothing has a size to measure and every
        // box would come out empty. Framing waits for the step to be opened, which also means the library is only
        // fetched once somebody goes looking for it.
        await this.laidOut();

        // Lazily imported: a page with nothing to frame should not pay for the library.
        const { default: Cropper } = await import('cropperjs');

        this.cropper = new Cropper(this.imageTarget, { template: this.template() });

        const image = this.cropper.getCropperImage();
        const selection = this.cropper.getCropperSelection();
        if (null === image || null === selection) {
            return;
        }

        this.selection = selection;
        selection.addEventListener('change', (event) => this.proposed(event as CustomEvent<Rectangle>));

        // Cropper fits the image to its canvas when the image loads, and an image the browser already has never loads
        // again, so one that was there all along would otherwise be laid out at its own size, spilling out of the box.
        await image.$ready().catch(() => undefined);
        image.$center('contain');

        // Cropper sizes the selection itself on its own schedule, a tick or two after it is put together, and whatever
        // is drawn before that is drawn over. Hence the wait: the frame below is the one that stays.
        await this.painted();

        const bounds = this.imageBounds();
        if (null === bounds) {
            return;
        }

        this.place(this.opening(saved, bounds), bounds);
    }

    private laidOut(): Promise<void> {
        if (this.element.clientWidth > 0) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            this.observer = new ResizeObserver(() => {
                if (0 === this.element.clientWidth) {
                    return;
                }

                this.stopObserving();
                resolve();
            });
            this.observer.observe(this.element);
        });
    }

    private painted(): Promise<void> {
        return new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(() => resolve()));
        });
    }

    private imageLoaded(): Promise<void> {
        if (this.imageTarget.complete && this.imageTarget.naturalWidth > 0) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            this.imageTarget.addEventListener('load', () => resolve(), { once: true });
            this.imageTarget.addEventListener('error', () => resolve(), { once: true });
        });
    }

    /**
     * A move or a resize, before Cropper applies it. One that would take the frame off the image is replaced by the
     * nearest one that is still on it, so dragging past an edge slides along it rather than stopping dead.
     */
    private proposed(event: CustomEvent<Rectangle>): void {
        const bounds = this.imageBounds();
        if (null === bounds || this.correcting) {
            return;
        }

        if (this.within(event.detail, bounds)) {
            this.write(event.detail, bounds);

            return;
        }

        event.preventDefault();

        this.correcting = true;
        this.place(this.inside(event.detail, bounds), bounds);
        this.correcting = false;
    }

    /**
     * Draw the frame. Cropper's own initial coverage is of the canvas rather than of the image, so on a picture that
     * does not fill the canvas it would start out partly beside it, with nothing to cut there.
     */
    private place(frame: Rectangle, bounds: Rectangle): void {
        (this.selection as unknown as {
            $change?(x: number, y: number, width: number, height: number): void;
        }).$change?.(frame.x, frame.y, frame.width, frame.height);

        this.write(frame, bounds);
    }

    /**
     * The nearest frame of the right shape that lies on the image: no larger than fits, no smaller than what is going
     * to be cut has to be, and pushed back inside.
     */
    private inside(frame: Rectangle, bounds: Rectangle): Rectangle {
        const width = Math.min(Math.max(frame.width, this.smallestWidth(bounds)), this.largestWidth(bounds));
        const height = width / this.ratio();

        return {
            x: Math.min(Math.max(frame.x, bounds.x), bounds.x + bounds.width - width),
            y: Math.min(Math.max(frame.y, bounds.y), bounds.y + bounds.height - height),
            width,
            height,
        };
    }

    /**
     * The frame to start from: the one that was saved, or the largest that fits, centred.
     */
    private opening(stored: Rectangle | null, bounds: Rectangle): Rectangle {
        if (null !== stored) {
            return this.inside({
                x: bounds.x + stored.x * bounds.width,
                y: bounds.y + stored.y * bounds.height,
                width: stored.width * bounds.width,
                height: stored.height * bounds.height,
            }, bounds);
        }

        const frame = this.inside({ ...bounds, width: this.largestWidth(bounds) }, bounds);

        return {
            ...frame,
            x: bounds.x + (bounds.width - frame.width) / 2,
            y: bounds.y + (bounds.height - frame.height) / 2,
        };
    }

    /**
     * Where the image sits within the canvas it is fitted into, which is what the frame is confined to and what the
     * fractions are of.
     */
    private imageBounds(): Rectangle | null {
        const image = this.cropper?.getCropperImage();
        const canvas = this.selection?.parentElement;
        if (undefined === image || null === image || undefined === canvas || null === canvas) {
            return null;
        }

        const imageBox = image.getBoundingClientRect();
        const canvasBox = canvas.getBoundingClientRect();
        if (0 === imageBox.width || 0 === imageBox.height) {
            return null;
        }

        return {
            x: imageBox.left - canvasBox.left,
            y: imageBox.top - canvasBox.top,
            width: imageBox.width,
            height: imageBox.height,
        };
    }

    /**
     * Half a pixel of slack, so a frame dragged flush against an edge or resized down to the minimum is not rejected
     * over the rounding between the boxes Cropper works in and the ones the browser reports.
     */
    private within(frame: Rectangle, bounds: Rectangle): boolean {
        const slack = 0.5;

        return frame.x >= bounds.x - slack
            && frame.y >= bounds.y - slack
            && frame.x + frame.width <= bounds.x + bounds.width + slack
            && frame.y + frame.height <= bounds.y + bounds.height + slack
            && frame.width >= this.smallestWidth(bounds) - slack;
    }

    private largestWidth(bounds: Rectangle): number {
        return Math.min(bounds.width, bounds.height * this.ratio());
    }

    /**
     * The narrowest the frame may be drawn, so that what is cut out is never narrower than what was demanded of the
     * upload in the first place. Measured against the original rather than against what is on screen: the frame is
     * drawn on a rendition of it, and a rendition is capped at the very width being asked for, which would put the
     * floor at the whole image and leave nothing to drag.
     */
    private smallestWidth(bounds: Rectangle): number {
        const source = this.source();
        if (0 === this.minimumWidthValue || 0 === source) {
            return 0;
        }

        return Math.min(
            bounds.width * Math.min(1, this.minimumWidthValue / source),
            this.largestWidth(bounds),
        );
    }

    /**
     * How wide the original is: what the file the reader just picked measures, or what the server said about the one
     * that is already stored. Falling back to the rendition on screen only keeps the frame usable if neither is known.
     */
    private source(): number {
        return this.picked
            ?? (this.sourceWidthValue > 0 ? this.sourceWidthValue : this.imageTarget.naturalWidth);
    }

    private write(frame: Rectangle, bounds: Rectangle): void {
        const x = this.clamp((frame.x - bounds.x) / bounds.width);
        const y = this.clamp((frame.y - bounds.y) / bounds.height);
        const width = this.clamp(frame.width / bounds.width, 1 - x);
        const height = this.clamp(frame.height / bounds.height, 1 - y);

        this.xTarget.value = this.round(x);
        this.yTarget.value = this.round(y);
        this.widthTarget.value = this.round(width);
        this.heightTarget.value = this.round(height);

        this.paint({ x, y, width, height });
    }

    /**
     * The framed part on its own, at the shape it is cut to, so what is being chosen can be seen beside what it would
     * replace. Drawn from the same fractions that are written above, which is what the server cuts by, rather than from
     * anything the frame knows: the two cannot drift apart that way.
     */
    private paint(fractions: Rectangle): void {
        if (!this.hasPreviewTarget) {
            return;
        }

        const image = this.imageTarget;
        const canvas = this.canvas();
        const context = canvas.getContext('2d');
        if (
            null === context
            || 0 === image.naturalWidth
            || 0 === canvas.clientWidth
            || fractions.width <= 0
        ) {
            return;
        }

        canvas.width = Math.round(canvas.clientWidth * window.devicePixelRatio);
        canvas.height = Math.max(1, Math.round(canvas.width / this.ratio()));

        context.drawImage(
            image,
            fractions.x * image.naturalWidth,
            fractions.y * image.naturalHeight,
            fractions.width * image.naturalWidth,
            fractions.height * image.naturalHeight,
            0,
            0,
            canvas.width,
            canvas.height,
        );
    }

    private canvas(): HTMLCanvasElement {
        const existing = this.previewTarget.querySelector('canvas');
        if (null !== existing) {
            return existing;
        }

        const canvas = document.createElement('canvas');
        this.previewTarget.replaceChildren(canvas);

        return canvas;
    }

    /**
     * The crop that is in force. It arrives beside the frame rather than in the boxes that are submitted: those are
     * only ever written here, so a form saved without a frame having been drawn carries no rectangle at all, and the
     * server leaves the crop alone rather than cutting an old one out of a new image.
     */
    private stored(): Rectangle | null {
        const frame = {
            x: Number(this.rectangleValue.x),
            y: Number(this.rectangleValue.y),
            width: Number(this.rectangleValue.width),
            height: Number(this.rectangleValue.height),
        };

        if (Object.values(frame).some(Number.isNaN) || frame.width <= 0 || frame.height <= 0) {
            return null;
        }

        return frame;
    }

    /**
     * Cropper's own template, with the transforms taken off the image so it stays put, and the shape of the frame fixed
     * to what this image is shown at.
     */
    private template(): string {
        const ratio = this.ratio();

        return `
            <cropper-canvas background>
                <cropper-image></cropper-image>
                <cropper-shade hidden></cropper-shade>
                <cropper-handle action="select" plain></cropper-handle>
                <cropper-selection movable resizable aspect-ratio="${ratio}">
                    <cropper-grid role="grid" bordered covered></cropper-grid>
                    <cropper-crosshair centered></cropper-crosshair>
                    <cropper-handle action="move" theme-color="rgba(255, 255, 255, 0.35)"></cropper-handle>
                    <cropper-handle action="n-resize"></cropper-handle>
                    <cropper-handle action="e-resize"></cropper-handle>
                    <cropper-handle action="s-resize"></cropper-handle>
                    <cropper-handle action="w-resize"></cropper-handle>
                    <cropper-handle action="ne-resize"></cropper-handle>
                    <cropper-handle action="nw-resize"></cropper-handle>
                    <cropper-handle action="se-resize"></cropper-handle>
                    <cropper-handle action="sw-resize"></cropper-handle>
                </cropper-selection>
            </cropper-canvas>
        `;
    }

    private stopObserving(): void {
        this.observer?.disconnect();
        this.observer = undefined;
    }

    private teardown(): void {
        this.stopObserving();
        this.cropper?.destroy();
        this.cropper = undefined;
        this.selection = undefined;

        if (!this.hasPreviewTarget) {
            return;
        }

        this.previewTarget.replaceChildren();
    }

    /**
     * A frame that described the file being replaced says nothing about the new one.
     */
    private clear(): void {
        [this.xTarget, this.yTarget, this.widthTarget, this.heightTarget].forEach((box) => { box.value = ''; });
    }

    private ratio(): number {
        return this.ratioValue > 0 ? this.ratioValue : 1;
    }

    private clamp(value: number, maximum = 1): number {
        return Math.min(Math.max(value, 0), maximum);
    }

    private round(value: number): string {
        return value.toFixed(6);
    }
}
