import { Controller } from '@hotwired/stimulus';

interface ManifestEntry {
    id: number;
    w: number;
    h: number;
    thumbUrl: string;
    largeUrl: string;
    xlargeUrl: string;
    downloadUrl: string;
}

interface SlideData {
    pid: number;
    src: string;
    srcset: string;
    width: number;
    height: number;
    msrc: string;
    downloadUrl: string;
}

// A photo's place in the masonry, computed from the whole album so the scroll height and every position are exact even
// though only the tiles near the viewport are ever in the DOM.
interface Slot {
    entry: ManifestEntry;
    x: number;
    y: number;
    width: number;
    height: number;
    element: HTMLAnchorElement | null;
}

/**
 * The album gallery: a windowed masonry plus the PhotoSwipe viewer, both driven by the album manifest (fetched once).
 *
 * Every photo's position is computed up front from its aspect ratio, so the page has its full height immediately and a
 * `#pid` deep link can scroll straight to a photo that has not been rendered yet. Only the tiles within roughly a
 * screenful of the viewport are mounted; the rest are added and removed as the page scrolls, so an album of thousands of
 * photos stays light in both directions. Without JavaScript there is no grid (the viewer needs it regardless); the
 * manifest is member-only and never indexed.
 */
export default class extends Controller<HTMLElement> {
    static values = {
        manifestUrl: String,
    };

    static targets = ['grid', 'empty'];

    declare readonly manifestUrlValue: string;
    declare readonly gridTarget: HTMLElement;
    declare readonly emptyTarget: HTMLElement;
    declare readonly hasEmptyTarget: boolean;

    // Matches the 0.5rem gap in the stylesheet.
    private readonly gap = 8;
    private slots: Slot[] = [];
    private indexByPid = new Map<number, number>();
    private slides: SlideData[] = [];
    private lightbox: any = null;
    private aborted = false;
    private renderScheduled = false;

    private readonly onScroll = (): void => this.scheduleRender();
    private readonly onResize = (): void => this.relayout();
    private readonly onPopState = (): void => this.syncFromHash();
    private readonly onClick = (event: Event): void => this.handleTileClick(event);

    async connect(): Promise<void> {
        this.aborted = false;

        const manifest = await this.loadManifest();
        // The controller may have disconnected (navigation) while the manifest was loading.
        if (this.aborted || null === manifest) {
            return;
        }

        if (0 === manifest.length) {
            this.emptyTarget.hidden = false;

            return;
        }

        this.buildSlots(manifest);
        this.buildSlides(manifest);
        await this.buildLightbox();
        if (this.aborted) {
            return;
        }

        this.relayout();

        window.addEventListener('scroll', this.onScroll, { passive: true });
        window.addEventListener('resize', this.onResize);
        window.addEventListener('popstate', this.onPopState);
        this.gridTarget.addEventListener('click', this.onClick);

        this.syncFromHash();
    }

    disconnect(): void {
        this.aborted = true;
        window.removeEventListener('scroll', this.onScroll);
        window.removeEventListener('resize', this.onResize);
        window.removeEventListener('popstate', this.onPopState);
        this.gridTarget.removeEventListener('click', this.onClick);
        this.lightbox?.destroy();
        this.lightbox = null;
    }

    private async loadManifest(): Promise<ManifestEntry[] | null> {
        try {
            const response = await fetch(this.manifestUrlValue, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return null;
            }

            return await response.json() as ManifestEntry[];
        } catch {
            return null;
        }
    }

    private buildSlots(manifest: ManifestEntry[]): void {
        this.slots = manifest.map((entry) => ({ entry, x: 0, y: 0, width: 0, height: 0, element: null }));
        this.indexByPid.clear();
        this.slots.forEach((slot, index) => this.indexByPid.set(slot.entry.id, index));
    }

    private buildSlides(manifest: ManifestEntry[]): void {
        // width/height are the large variant's reference size; the srcset lets PhotoSwipe pull the extra-large variant
        // when zoomed (#2057), and msrc shows the thumbnail as a blur-up placeholder (#1496).
        this.slides = manifest.map((entry) => ({
            pid: entry.id,
            src: entry.largeUrl,
            srcset: `${entry.largeUrl} ${entry.w}w, ${entry.xlargeUrl} 2560w`,
            width: entry.w,
            height: entry.h,
            msrc: entry.thumbUrl,
            downloadUrl: entry.downloadUrl,
        }));
    }

    private async buildLightbox(): Promise<void> {
        const { default: PhotoSwipeLightbox } = await import('photoswipe/lightbox');
        if (this.aborted) {
            return;
        }

        this.lightbox = new PhotoSwipeLightbox({
            dataSource: this.slides,
            pswpModule: () => import('photoswipe'),
        });
        this.registerToolbarButtons();
        this.lightbox.on('close', (): void => this.onViewerClosed());
        this.lightbox.init();
    }

    private columnCount(width: number): number {
        if (width >= 1600) {
            return 7;
        }
        if (width >= 1200) {
            return 6;
        }
        if (width >= 992) {
            return 5;
        }
        if (width >= 768) {
            return 4;
        }
        if (width >= 576) {
            return 3;
        }

        return 2;
    }

    private relayout(): void {
        const width = this.gridTarget.clientWidth;
        const columns = this.columnCount(width);
        const columnWidth = (width - (columns - 1) * this.gap) / columns;
        const heights = new Array<number>(columns).fill(0);

        for (const slot of this.slots) {
            const aspect = slot.entry.h / slot.entry.w;
            const height = columnWidth * aspect;

            let shortest = 0;
            for (let column = 1; column < columns; column++) {
                if (heights[column] < heights[shortest]) {
                    shortest = column;
                }
            }

            slot.x = shortest * (columnWidth + this.gap);
            slot.y = heights[shortest];
            slot.width = columnWidth;
            slot.height = height;

            heights[shortest] += height + this.gap;
        }

        this.gridTarget.style.height = `${Math.max(...heights)}px`;

        // Positions changed, so drop every mounted tile and re-mount the window from scratch.
        for (const slot of this.slots) {
            this.unmount(slot);
        }

        this.renderWindow();
    }

    private scheduleRender(): void {
        if (this.renderScheduled) {
            return;
        }

        this.renderScheduled = true;
        window.requestAnimationFrame((): void => {
            this.renderScheduled = false;
            this.renderWindow();
        });
    }

    private renderWindow(): void {
        // Keep roughly a screenful mounted above and below the viewport. Ranges are relative to the grid's own top,
        // read live so they stay correct when content above the grid changes height.
        const buffer = window.innerHeight;
        const gridTop = this.gridTarget.getBoundingClientRect().top;
        const top = -gridTop - buffer;
        const bottom = -gridTop + window.innerHeight + buffer;

        for (const slot of this.slots) {
            const visible = slot.y + slot.height >= top && slot.y <= bottom;
            if (visible) {
                this.mount(slot);
            } else {
                this.unmount(slot);
            }
        }
    }

    private mount(slot: Slot): void {
        if (null !== slot.element) {
            this.position(slot);

            return;
        }

        const link = document.createElement('a');
        link.className = 'photo-masonry__item';
        link.href = `#pid=${slot.entry.id}`;
        link.dataset.id = String(slot.entry.id);
        link.setAttribute('aria-label', this.gridTarget.dataset.viewLabel ?? 'View photo');

        const image = document.createElement('img');
        image.loading = 'lazy';
        image.alt = '';
        image.src = slot.entry.thumbUrl;

        link.append(image);
        slot.element = link;
        this.position(slot);
        this.gridTarget.append(link);
    }

    private unmount(slot: Slot): void {
        if (null === slot.element) {
            return;
        }

        slot.element.remove();
        slot.element = null;
    }

    private position(slot: Slot): void {
        if (null === slot.element) {
            return;
        }

        slot.element.style.width = `${slot.width}px`;
        slot.element.style.height = `${slot.height}px`;
        slot.element.style.transform = `translate(${slot.x}px, ${slot.y}px)`;
    }

    private handleTileClick(event: Event): void {
        const tile = (event.target as HTMLElement).closest<HTMLElement>('[data-id]');
        if (null === tile) {
            return;
        }

        const index = this.indexByPid.get(Number(tile.dataset.id));
        if (undefined === index) {
            return;
        }

        event.preventDefault();
        this.openAt(index);
    }

    private openAt(index: number): void {
        if (null === this.lightbox) {
            return;
        }

        const pid = this.slides[index].pid;
        // Push a history entry so the mobile back button closes the viewer instead of leaving the page (#2065).
        if (window.location.hash !== `#pid=${pid}`) {
            window.history.pushState({ pid }, '', `#pid=${pid}`);
        }

        this.lightbox.loadAndOpen(index);
    }

    /**
     * Open, move or close the viewer to match the URL hash. A shared `#pid` link scrolls the masonry to that photo
     * first, so the tile exists behind the viewer and closing lands back on it instead of jumping.
     */
    private syncFromHash(): void {
        const match = window.location.hash.match(/^#pid=(\d+)$/);
        if (null === match) {
            this.lightbox?.pswp?.close();

            return;
        }

        const index = this.indexByPid.get(Number(match[1]));
        if (undefined === index || null === this.lightbox) {
            return;
        }

        const slot = this.slots[index];
        const gridTop = this.gridTarget.getBoundingClientRect().top + window.scrollY;
        const target = gridTop + slot.y - (window.innerHeight - slot.height) / 2;
        window.scrollTo({ top: Math.max(0, target) });
        this.renderWindow();

        if (this.lightbox.pswp) {
            this.lightbox.pswp.goTo(index);
        } else {
            this.lightbox.loadAndOpen(index);
        }
    }

    private onViewerClosed(): void {
        // Closed by its own UI (not by a back navigation, which already removed the hash): drop the #pid from the URL.
        if (window.location.hash.startsWith('#pid=')) {
            window.history.back();
        }
    }

    private registerToolbarButtons(): void {
        this.lightbox.on('uiRegister', (): void => {
            this.lightbox.pswp.ui.registerElement({
                name: 'share-button',
                order: 9,
                isButton: true,
                tagName: 'button',
                html: '<i class="fa-solid fa-share-nodes"></i>',
                onClick: (_event: MouseEvent, element: HTMLElement): void => {
                    const slide = this.slides[this.lightbox.pswp.currIndex];
                    const link = `${window.location.origin}${window.location.pathname}#pid=${slide.pid}`;
                    void navigator.clipboard?.writeText(link);
                    this.flashCopied(element);
                },
            });

            this.lightbox.pswp.ui.registerElement({
                name: 'download-button',
                order: 8,
                isButton: true,
                tagName: 'a',
                html: '<i class="fa-solid fa-download"></i>',
                onInit: (element: HTMLAnchorElement): void => {
                    element.setAttribute('download', '');
                    element.setAttribute('rel', 'noopener');
                    this.lightbox.pswp.on('change', (): void => {
                        element.href = this.slides[this.lightbox.pswp.currIndex].downloadUrl;
                    });
                },
            });
        });
    }

    /**
     * Briefly swap the share button's icon to a checkmark so the user sees the link was copied to their clipboard.
     */
    private flashCopied(element: HTMLElement): void {
        const icon = element.querySelector('i');
        if (null === icon) {
            return;
        }

        const previous = icon.className;
        icon.className = 'fa-solid fa-check';
        window.setTimeout((): void => {
            icon.className = previous;
        }, 1500);
    }
}
