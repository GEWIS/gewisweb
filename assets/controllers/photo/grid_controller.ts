import { Controller } from '@hotwired/stimulus';

/**
 * Masonry layout for the selectable photo tiles on the album manage view, using the same shortest-column packing as the
 * public gallery but over the server-rendered tiles (no manifest, no windowing) so the selection checkboxes keep working
 * with the bulk form. Each tile carries its aspect ratio (height / width) in `data-aspect`.
 */
export default class extends Controller<HTMLElement> {
    // Matches the tile gap in the stylesheet.
    private readonly gap = 8;
    private readonly targetTileWidth = 200;

    private items: HTMLElement[] = [];
    private observer: ResizeObserver | null = null;
    private frame = 0;
    private laidOutWidth = -1;

    connect(): void {
        this.items = Array.from(this.element.querySelectorAll<HTMLElement>('.photo-masonry__item'));
        this.layout();

        this.observer = new ResizeObserver((): void => this.scheduleLayout());
        this.observer.observe(this.element);
    }

    disconnect(): void {
        this.observer?.disconnect();
        this.observer = null;
        window.cancelAnimationFrame(this.frame);
    }

    private scheduleLayout(): void {
        window.cancelAnimationFrame(this.frame);
        this.frame = window.requestAnimationFrame((): void => this.layout());
    }

    private columnCount(width: number): number {
        return Math.max(2, Math.floor((width + this.gap) / (this.targetTileWidth + this.gap)));
    }

    private layout(): void {
        const width = this.element.clientWidth;
        // Skip while hidden, and when only our own height change (not a width change) woke the observer.
        if (0 === width || width === this.laidOutWidth) {
            return;
        }

        this.laidOutWidth = width;

        const columns = this.columnCount(width);
        const columnWidth = (width - (columns - 1) * this.gap) / columns;
        const heights = new Array<number>(columns).fill(0);

        for (const item of this.items) {
            const aspect = Number(item.dataset.aspect) || 1;
            const height = columnWidth * aspect;

            let shortest = 0;
            for (let column = 1; column < columns; column++) {
                if (heights[column] < heights[shortest]) {
                    shortest = column;
                }
            }

            item.style.width = `${columnWidth}px`;
            item.style.height = `${height}px`;
            item.style.transform = `translate(${shortest * (columnWidth + this.gap)}px, ${heights[shortest]}px)`;

            heights[shortest] += height + this.gap;
        }

        this.element.style.height = `${Math.max(...heights)}px`;
    }
}
