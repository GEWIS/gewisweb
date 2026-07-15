import { Controller } from '@hotwired/stimulus';

// Delay before a submenu closes after the pointer leaves it, so a diagonal move onto it does not close it.
const CLOSE_DELAY = 180;

/**
 * Adds nested submenus and hover to the main navbar dropdowns, neither of which Bootstrap 5.3 supports.
 *
 * Top-level menus open on click. Submenus open on hover on desktop (>= lg with a fine pointer) and on click below
 * that, where they expand inline inside the offcanvas. Escape closes the innermost menu; a click or tab away closes
 * everything.
 *
 * It works on the existing Bootstrap markup: `.dropdown-nav` (top level) and `.dropdown-submenu` items, each with a
 * `.dropdown-toggle` and a `.dropdown-menu`. The template adds `data-controller="nav-dropdown"` on the container and
 * leaves `data-bs-toggle` off these toggles. Menus are shown with Bootstrap's `.show` class and positioned in
 * _navbar.scss. The right-hand user and settings menus keep `data-bs-toggle` and stay plain Bootstrap.
 */
export default class extends Controller {
    // Desktop is the `lg` breakpoint (992px), where the offcanvas becomes an inline navbar. Hover also needs a fine
    // pointer, so touch devices only get click.
    private readonly desktopQuery = window.matchMedia('(min-width: 992px)');
    private readonly hoverQuery = window.matchMedia('(hover: hover) and (pointer: fine)');

    private dropdowns: HTMLElement[] = [];
    private readonly closeTimers = new Map<HTMLElement, number>();
    private readonly cleanups: Array<() => void> = [];

    connect(): void {
        // Skip a `.dropdown-nav` with no menu (the logged-out "Photos" item is only a link).
        this.dropdowns = Array.from(
            this.element.querySelectorAll<HTMLElement>('.dropdown-nav, .dropdown-submenu'),
        ).filter((el) => null !== this.toggleOf(el) && null !== this.menuOf(el));

        this.dropdowns.forEach((el) => {
            const toggle = this.toggleOf(el)!;
            this.listen(toggle, 'click', (event) => this.onToggleClick(el, event as MouseEvent));
            this.listen(toggle, 'keydown', (event) => this.onToggleKeydown(el, event as KeyboardEvent));

            // Only submenus open on hover; top-level menus are click only.
            if (el.classList.contains('dropdown-submenu')) {
                this.listen(el, 'mouseenter', () => this.onEnter(el));
                this.listen(el, 'mouseleave', () => this.onLeave(el));
            }
        });

        this.listen(this.element, 'focusout', (event) => this.onFocusOut(event as FocusEvent));
        this.listen(this.element, 'shown.bs.offcanvas', () => this.onOffcanvasShown());
        this.listen(this.element, 'hidden.bs.offcanvas', () => this.closeAll());

        this.listen(document, 'pointerdown', (event) => this.onOutsidePointer(event as PointerEvent));
        this.listen(document, 'keydown', (event) => this.onDocumentKeydown(event as KeyboardEvent));

        // Close everything when the breakpoint or pointer type changes, so no menu is left open in the wrong mode.
        this.listen(this.desktopQuery, 'change', () => this.closeAll());
        this.listen(this.hoverQuery, 'change', () => this.closeAll());
    }

    disconnect(): void {
        this.closeTimers.forEach((timer) => window.clearTimeout(timer));
        this.closeTimers.clear();
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

    private onEnter(el: HTMLElement): void {
        if (this.hoverMode) {
            this.open(el);
        }
    }

    private onLeave(el: HTMLElement): void {
        if (this.hoverMode) {
            this.scheduleClose(el);
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

        const innermost = this.deepestOpen();
        if (null === innermost) {
            return;
        }

        const toggle = this.toggleOf(innermost);
        this.close(innermost);
        toggle?.focus();
    }

    private onFocusOut(event: FocusEvent): void {
        const lost = event.target as Node;
        const next = event.relatedTarget as Node | null;

        // Close a menu when focus leaves it, but not while focus moves within it or into its submenu.
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
        this.cancelClose(el);
        this.closeSiblings(el);
        el.classList.add('show');
        this.menuOf(el)?.classList.add('show');
        this.toggleOf(el)?.setAttribute('aria-expanded', 'true');
    }

    private close(el: HTMLElement): void {
        this.cancelClose(el);
        // Close any submenus inside it first, so it reopens closed.
        el.querySelectorAll<HTMLElement>('.dropdown-submenu.show').forEach((sub) => this.reset(sub));
        this.reset(el);
    }

    private reset(el: HTMLElement): void {
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

    /** Close sibling menus so only one is open at each level. */
    private closeSiblings(el: HTMLElement): void {
        const topLevel = el.classList.contains('dropdown-nav');
        const scope: ParentNode = topLevel ? this.element : (el.closest('.dropdown-menu') ?? this.element);
        const selector = topLevel ? '.dropdown-nav' : ':scope > .dropdown-submenu';

        scope.querySelectorAll<HTMLElement>(selector).forEach((other) => {
            if (other !== el && this.isOpen(other)) {
                this.close(other);
            }
        });
    }

    private scheduleClose(el: HTMLElement): void {
        this.cancelClose(el);
        this.closeTimers.set(el, window.setTimeout(() => {
            this.closeTimers.delete(el);
            this.close(el);
        }, CLOSE_DELAY));
    }

    private cancelClose(el: HTMLElement): void {
        const timer = this.closeTimers.get(el);
        if (undefined !== timer) {
            window.clearTimeout(timer);
            this.closeTimers.delete(el);
        }
    }

    /** The innermost open menu, which Escape closes first. */
    private deepestOpen(): HTMLElement | null {
        const open = this.dropdowns.filter((el) => this.isOpen(el));

        return open.find((el) => !open.some((other) => other !== el && el.contains(other))) ?? null;
    }

    private get hoverMode(): boolean {
        return this.desktopQuery.matches && this.hoverQuery.matches;
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
