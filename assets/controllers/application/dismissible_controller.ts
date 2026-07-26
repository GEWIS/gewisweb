import { Controller } from '@hotwired/stimulus';

const KEY = 'gewis:dismissed-announcements';

/**
 * Remembers which announcement banners a visitor has closed. On connect it hides any banner already dismissed in this
 * browser; closing one records its id so it stays hidden on later visits (until the announcement's own end date removes
 * it server-side).
 */
export default class extends Controller<HTMLElement> {
    static targets = ['banner'];

    declare readonly bannerTargets: HTMLElement[];

    connect(): void {
        const dismissed = this.dismissed();
        this.bannerTargets.forEach((banner: HTMLElement): void => {
            const id = banner.dataset.announcementId;
            if (undefined !== id && dismissed.includes(id)) {
                banner.remove();
            }
        });
    }

    dismiss(event: Event): void {
        const banner = (event.currentTarget as HTMLElement).closest('[data-dismissible-target~="banner"]');
        if (!(banner instanceof HTMLElement)) {
            return;
        }

        const id = banner.dataset.announcementId;
        if (undefined !== id) {
            const dismissed = this.dismissed();
            if (!dismissed.includes(id)) {
                dismissed.push(id);
                localStorage.setItem(KEY, JSON.stringify(dismissed));
            }
        }

        banner.remove();
    }

    private dismissed(): string[] {
        try {
            const raw = localStorage.getItem(KEY);
            if (null === raw) {
                return [];
            }

            const parsed: unknown = JSON.parse(raw);

            return Array.isArray(parsed) ? (parsed as string[]) : [];
        } catch {
            return [];
        }
    }
}
