import { Controller } from '@hotwired/stimulus';

const KEY = 'gewis:dismissed-announcements';

/**
 * Remembers which announcement banners a visitor has closed. Closing one records its id so it stays closed on later
 * visits, until the announcement's own end date removes it server-side.
 *
 * Banners arrive hidden and are shown here once they are known not to have been dismissed. The other way around meant
 * a banner somebody had already closed was painted and then taken away again.
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

                return;
            }

            banner.classList.add('show');
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
