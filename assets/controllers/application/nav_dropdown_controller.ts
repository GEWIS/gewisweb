import { Controller } from '@hotwired/stimulus';

/**
 * Opens the main navbar dropdowns on click, which Bootstrap 5.3 only does through its own dropdown plugin and Popper.
 *
 * Escape closes the open menu; a click or tab away closes everything. Sub-sections inside a panel are plain Bootstrap
 * collapses and are none of this controller's business, so a click on one leaves the panel open.
 *
 * It works on the existing Bootstrap markup: a `.dropdown-nav` with a `.dropdown-toggle` and a `.dropdown-menu`. The
 * template adds `data-controller="nav-dropdown"` on the container and leaves `data-bs-toggle` off those toggles. Menus
 * are shown with Bootstrap's `.show` class and positioned in _navbar.scss. The right-hand user, notification and
 * settings menus keep `data-bs-toggle` and stay plain Bootstrap.
 */
export default class extends Controller {
    // Desktop is the `lg` breakpoint (992px), where the offcanvas becomes an inline navbar.
    private readonly desktopQuery = window.matchMedia('(min-width: 992px)');

    private dropdowns: HTMLElement[] = [];
    private readonly cleanups: Array<() => void> = [];

    connect(): void {
        // Skip a `.dropdown-nav` with no menu (the logged-out "Photos" item is only a link).
        this.dropdowns = Array.from(
            this.element.querySelectorAll<HTMLElement>('.dropdown-nav'),
        ).filter((el) => null !== this.toggleOf(el) && null !== this.menuOf(el));

        this.dropdowns.forEach((el) => {
            const toggle = this.toggleOf(el)!;
            this.listen(toggle, 'click', (event) => this.onToggleClick(el, event as MouseEvent));
            this.listen(toggle, 'keydown', (event) => this.onToggleKeydown(el, event as KeyboardEvent));
        });

        this.listen(this.element, 'focusout', (event) => this.onFocusOut(event as FocusEvent));
        this.listen(this.element, 'shown.bs.offcanvas', () => this.onOffcanvasShown());
        this.listen(this.element, 'hidden.bs.offcanvas', () => this.closeAll());

        this.listen(document, 'pointerdown', (event) => this.onOutsidePointer(event as PointerEvent));
        this.listen(document, 'keydown', (event) => this.onDocumentKeydown(event as KeyboardEvent));

        // Close everything when the breakpoint changes, so no menu is left open in the wrong mode.
        this.listen(this.desktopQuery, 'change', () => this.closeAll());
    }

    disconnect(): void {
        this.cleanups.forEach((off) => off());
        this.cleanups.length = 0;
    }

    private onToggleClick(el: HTMLElement, event: MouseEvent): void {
        // The toggles must not navigate; the "Association" one is an <a href="">.
        event.preventDefault();
        this.toggleOpen(el);
    }

    private onToggleKeydown(el: HTMLElement, event: KeyboardEvent): void {
        // Buttons already toggle on Enter and Space; the <a> toggle needs Space added here.
        if (' ' === event.key && 'A' === this.toggleOf(el)?.tagName) {
            event.preventDefault();
            this.toggleOpen(el);
        }
    }

    private onOutsidePointer(event: PointerEvent): void {
        const target = event.target as Element | null;
        if (null === target) {
            return;
        }

        if (this.element.contains(target)) {
            // A toggle handles its own click, and a click inside an open menu should work normally.
            if (null !== target.closest('.dropdown-toggle')) {
                return;
            }
            if (this.dropdowns.some((el) => this.isOpen(el) && el.contains(target))) {
                return;
            }
        }

        this.closeAll();
    }

    private onDocumentKeydown(event: KeyboardEvent): void {
        if ('Escape' !== event.key) {
            return;
        }

        const open = this.dropdowns.find((el) => this.isOpen(el));
        if (undefined === open) {
            return;
        }

        const toggle = this.toggleOf(open);
        this.close(open);
        toggle?.focus();
    }

    private onFocusOut(event: FocusEvent): void {
        const lost = event.target as Node;
        const next = event.relatedTarget as Node | null;

        // Close a menu when focus leaves it, but not while focus moves within it.
        this.dropdowns.forEach((el) => {
            if (this.isOpen(el) && el.contains(lost) && (null === next || !el.contains(next))) {
                this.close(el);
            }
        });
    }

    private onOffcanvasShown(): void {
        if (this.desktopQuery.matches) {
            return;
        }

        // Open the section the user is on, so the mobile menu starts there.
        const active = this.element.querySelector<HTMLElement>('.dropdown-nav.active');
        if (null !== active && this.dropdowns.includes(active)) {
            this.open(active);
        }
    }

    private toggleOpen(el: HTMLElement): void {
        if (this.isOpen(el)) {
            this.close(el);
        } else {
            this.open(el);
        }
    }

    private open(el: HTMLElement): void {
        this.closeAll();
        el.classList.add('show');
        this.menuOf(el)?.classList.add('show');
        this.toggleOf(el)?.setAttribute('aria-expanded', 'true');
    }

    private close(el: HTMLElement): void {
        el.classList.remove('show');
        this.menuOf(el)?.classList.remove('show');
        this.toggleOf(el)?.setAttribute('aria-expanded', 'false');
    }

    private closeAll(): void {
        this.dropdowns.forEach((el) => {
            if (this.isOpen(el)) {
                this.close(el);
            }
        });
    }

    private isOpen(el: HTMLElement): boolean {
        return el.classList.contains('show');
    }

    private toggleOf(el: HTMLElement): HTMLElement | null {
        return el.querySelector<HTMLElement>(':scope > .dropdown-toggle');
    }

    private menuOf(el: HTMLElement): HTMLElement | null {
        return el.querySelector<HTMLElement>(':scope > .dropdown-menu');
    }

    private listen(
        target: EventTarget,
        type: string,
        handler: EventListener,
        options?: AddEventListenerOptions,
    ): void {
        target.addEventListener(type, handler, options);
        this.cleanups.push(() => target.removeEventListener(type, handler, options));
    }
}
