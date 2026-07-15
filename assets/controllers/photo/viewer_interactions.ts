interface MemberTag {
    id: number;
    lidnr: number;
    fullName: string;
    x: number | null;
    y: number | null;
    canRemove: boolean;
}

interface OrganTag {
    id: number;
    name: string;
    abbr: string;
    x: number | null;
    y: number | null;
    canRemove: boolean;
}

interface Exif {
    artist: string | null;
    camera: string | null;
    dateTime: string;
    flash: boolean | null;
    focalLength: number | null;
    shutterSpeed: string | null;
    aperture: string | null;
    iso: number | null;
}

interface Details {
    memberTags: MemberTag[];
    organTags: OrganTag[];
    canTag: boolean;
    canVote: boolean;
    voted: boolean;
    recentVote: boolean;
    taggedSelf: boolean;
    photoOfTheWeek: string | null;
    exif: Exif;
}

interface Organ {
    id: number;
    abbr: string;
    name: string;
}

export interface ViewerConfig {
    // Each *Template has a placeholder (__PHOTO__, __TAG__ or __LIDNR__) replaced with the id.
    detailsUrlTemplate: string;
    tagUrlTemplate: string;
    tagRemoveUrlTemplate: string;
    voteUrlTemplate: string;
    profileUrlTemplate: string;
    memberSearchUrl: string;
    organsUrl: string;
    memberUrlTemplate: string;
    iconSpriteUrl: string;
    labels: Record<string, string>;
}

/**
 * The markup for a custom toolbar button's icon: a Font Awesome sprite reference wrapped in PhotoSwipe's own `.pswp__icn`
 * SVG (via `isCustomSVG`), so PhotoSwipe positions and centres it exactly like its built-in zoom and close buttons.
 */
export function spriteIcon(spriteUrl: string, name: string): { isCustomSVG: true; inner: string } {
    // PhotoSwipe wraps this in a 32x32 viewBox. Font Awesome glyphs fill their own viewBox edge to edge, so inset the
    // <use> to a 20-unit box centred in the 32 (padding 6), matching the visual size of the built-in zoom/close icons.
    return {
        isCustomSVG: true,
        inner: `<use href="${spriteUrl}#${name}" x="6" y="6" width="20" height="20"></use>`,
    };
}

/**
 * The tag, vote and profile-photo layer of the album viewer. It hangs off the PhotoSwipe lightbox the gallery owns and
 * shows, for every slide, a panel to the side: who is tagged (with links and — where allowed — a remove control and an
 * add form), a vote button, and a set-as-profile-photo button when the viewer is tagged. Every action posts to the
 * backend and re-reads the details, so the panel always reflects the server's answer, including the graduate rule.
 *
 * The panel's structure is built once; only the tag list and button states are updated per slide. That keeps the search
 * field alive across photos, so a member can be tagged in one photo after another without re-focusing it.
 */
export class ViewerInteractions {
    private details: Details | null = null;
    private organs: Organ[] | null = null;
    // Details keyed by photo id, so a photo already visited (or prefetched as a neighbour) renders instantly on swipe.
    private readonly detailsCache = new Map<number, Details>();

    private potwBadge: HTMLElement | null = null;
    private title: HTMLElement | null = null;
    private list: HTMLElement | null = null;
    private form: HTMLElement | null = null;
    private results: HTMLElement | null = null;
    private searchInput: HTMLInputElement | null = null;
    private placeButton: HTMLButtonElement | null = null;

    private voteButton: HTMLElement | null = null;
    private profileButton: HTMLElement | null = null;
    private metadataPanel: HTMLElement | null = null;

    // The overlay that carries the point markers. It fills the PhotoSwipe root; each marker is positioned in pixels
    // from the on-screen image rect (the zoom container itself is zero-sized), and repositioned on zoom/pan/resize.
    private markersLayer: HTMLElement | null = null;
    private readonly onViewerResize = (): void => this.positionMarkers();
    // While placing, the next click on the photo records a normalised point; the next tag added is pinned there.
    private placing = false;
    private pendingPosition: { x: number; y: number } | null = null;
    private placingOverlay: HTMLElement | null = null;

    constructor(
        private readonly lightbox: any,
        private readonly config: ViewerConfig,
    ) {
        this.lightbox.on('uiRegister', (): void => this.registerElements());
        this.lightbox.on('change', (): void => void this.refresh());
        this.lightbox.on('close', (): void => {
            // Closing the viewer mid-placement must not leave the capture overlay on the page, and the markers layer
            // and its resize listener are dropped so the next open starts clean.
            this.stopPlacing();
            window.removeEventListener('resize', this.onViewerResize);
            this.markersLayer?.remove();
            this.markersLayer = null;
        });
    }

    private label(key: string): string {
        return this.config.labels[key] ?? key;
    }

    private currentPid(): number | null {
        const pid = this.lightbox.pswp?.currSlide?.data?.pid;

        return 'number' === typeof pid ? pid : null;
    }

    private registerElements(): void {
        const ui = this.lightbox.pswp.ui;

        // Reposition the point markers whenever the image moves (zoom/pan) or the window resizes.
        this.lightbox.pswp.on('zoomPanUpdate', (): void => this.positionMarkers());
        window.addEventListener('resize', this.onViewerResize);

        ui.registerElement({
            name: 'photo-tags',
            appendTo: 'root',
            onInit: (element: HTMLElement): void => this.buildPanel(element),
        });

        // The camera-metadata panel and the toolbar button that toggles it (order 10 sits between the album and
        // download buttons the gallery registers).
        ui.registerElement({
            name: 'metadata-ui',
            appendTo: 'root',
            onInit: (element: HTMLElement): void => this.buildMetadataPanel(element),
        });

        ui.registerElement({
            name: 'info-button',
            order: 10,
            isButton: true,
            tagName: 'button',
            title: this.label('information'),
            html: spriteIcon(this.config.iconSpriteUrl, 'circle-info'),
            onInit: (element: HTMLElement): void => {
                element.addEventListener('click', (): void => this.toggleMetadata());
            },
        });

        // The toolbar's preloader (order 7) carries margin-right:auto, so only orders greater than it sit on the right;
        // these keep the vote and profile buttons in the download/share/zoom/close cluster there.
        ui.registerElement({
            name: 'vote-button',
            order: 13,
            isButton: true,
            tagName: 'button',
            html: spriteIcon(this.config.iconSpriteUrl, 'thumbs-up'),
            onInit: (element: HTMLElement): void => {
                this.voteButton = element;
                // The "you have not voted recently" nudge dot sits on top of the icon (the sprite icon replaces the
                // button's inner HTML, so the dot is appended rather than part of the markup).
                const dot = document.createElement('span');
                dot.className = 'pswp__vote-dot';
                element.appendChild(dot);
                element.addEventListener('click', (): void => void this.vote());
            },
        });

        ui.registerElement({
            name: 'profile-photo-button',
            order: 14,
            isButton: true,
            tagName: 'button',
            html: spriteIcon(this.config.iconSpriteUrl, 'image-portrait'),
            onInit: (element: HTMLElement): void => {
                this.profileButton = element;
                element.addEventListener('click', (): void => void this.setProfilePhoto());
            },
        });
    }

    private buildPanel(root: HTMLElement): void {
        root.classList.add('pswp__photo-tags');

        // A badge shown when this photo is (or was) the photo of the week.
        this.potwBadge = document.createElement('span');
        this.potwBadge.className = 'pswp__photo-potw';
        this.potwBadge.hidden = true;
        Object.assign(this.potwBadge.style, {
            display: 'inline-flex',
            alignItems: 'center',
            gap: '0.35rem',
            alignSelf: 'flex-start',
            padding: '0.15rem 0.6rem',
            color: '#000',
            background: '#ffc107',
            borderRadius: '1rem',
            fontSize: '0.8rem',
            fontWeight: '600',
        });
        this.potwBadge.innerHTML = '<i class="fa-solid fa-star"></i><span></span>';

        this.title = document.createElement('span');
        this.title.className = 'pswp__photo-tags-title';

        this.list = document.createElement('div');
        this.list.className = 'pswp__photo-tags-list';

        this.form = this.buildAddForm();

        root.append(this.potwBadge, this.title, this.list, this.form);
    }

    private buildMetadataPanel(root: HTMLElement): void {
        root.classList.add('pswp__photo-metadata');
        root.hidden = true;
        this.metadataPanel = root;
    }

    private toggleMetadata(): void {
        if (null !== this.metadataPanel) {
            this.metadataPanel.hidden = !this.metadataPanel.hidden;
        }
    }

    private renderMetadata(exif: Exif): void {
        if (null === this.metadataPanel) {
            return;
        }

        const unknown = this.label('unknown');
        const flash = null === exif.flash ? unknown : this.label(exif.flash ? 'yes' : 'no');
        const rows: [string, string][] = [
            ['artist', exif.artist ?? unknown],
            ['camera', exif.camera ?? unknown],
            ['dateTime', exif.dateTime],
            ['flash', flash],
            ['focalLength', null === exif.focalLength ? unknown : `${exif.focalLength} mm`],
            ['shutterSpeed', exif.shutterSpeed ?? unknown],
            ['aperture', exif.aperture ?? unknown],
            ['iso', null === exif.iso ? unknown : String(exif.iso)],
        ];

        const table = document.createElement('table');
        table.className = 'pswp__photo-metadata-table';
        for (const [key, value] of rows) {
            const row = document.createElement('tr');
            const label = document.createElement('th');
            label.textContent = this.label(key);
            const cell = document.createElement('td');
            cell.textContent = value;
            row.append(label, cell);
            table.appendChild(row);
        }

        this.metadataPanel.replaceChildren(table);
    }

    private buildAddForm(): HTMLElement {
        const form = document.createElement('div');
        form.className = 'pswp__photo-tag-add';

        this.searchInput = document.createElement('input');
        this.searchInput.type = 'search';
        this.searchInput.className = 'form-control form-control-sm pswp__photo-tag-search';
        this.searchInput.placeholder = this.label('tagMember');

        this.results = document.createElement('div');
        this.results.className = 'pswp__photo-tag-results';

        this.searchInput.addEventListener('input', (): void => void this.searchMembers());

        this.placeButton = document.createElement('button');
        this.placeButton.type = 'button';
        this.placeButton.className = 'btn btn-sm btn-outline-light pswp__photo-tag-place';
        this.placeButton.textContent = this.label('placeOnPhoto');
        this.placeButton.addEventListener('click', (): void => this.togglePlacing());

        form.append(this.searchInput, this.results, this.organSelect(), this.placeButton);

        return form;
    }

    private organSelect(): HTMLElement {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm pswp__photo-tag-organ';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = this.label('tagOrgan');
        select.append(placeholder);

        void this.loadOrgans().then((organs) => {
            for (const organ of organs) {
                const option = document.createElement('option');
                option.value = String(organ.id);
                option.textContent = organ.abbr;
                option.title = organ.name;
                select.append(option);
            }
        });

        select.addEventListener('change', (): void => {
            if ('' !== select.value) {
                void this.addTag('organ', Number(select.value));
                select.value = '';
            }
        });

        return select;
    }

    private async refresh(): Promise<void> {
        const pid = this.currentPid();
        if (null === pid) {
            return;
        }

        // A new photo starts with no pending placement and placing off.
        this.stopPlacing();
        this.pendingPosition = null;

        this.details = await this.loadDetails(pid);
        // The slide may have changed again while the request was in flight.
        if (pid !== this.currentPid() || null === this.details) {
            return;
        }

        this.renderButtons(this.details);
        this.renderPanel(this.details);
        this.renderMarkers(this.details);
        this.renderMetadata(this.details.exif);

        // Warm the neighbours so the next swipe in either direction shows its tags without a round-trip.
        void this.prefetchNeighbours();
    }

    // The details for a photo, from the cache when available (instant) or the server otherwise.
    private async loadDetails(pid: number): Promise<Details | null> {
        const cached = this.detailsCache.get(pid);
        if (cached !== undefined) {
            return cached;
        }

        const details = await this.fetchJson<Details>(this.fill(this.config.detailsUrlTemplate, '__PHOTO__', pid));
        if (null !== details) {
            this.detailsCache.set(pid, details);
        }

        return details;
    }

    private async prefetchNeighbours(): Promise<void> {
        const pswp = this.lightbox.pswp;
        if (!pswp) {
            return;
        }

        for (const offset of [-1, 1]) {
            const data = pswp.getItemData?.(pswp.currIndex + offset);
            const pid = 'number' === typeof data?.pid ? data.pid : null;
            if (null !== pid && !this.detailsCache.has(pid)) {
                // Fire-and-forget: loadDetails caches the result for the eventual swipe.
                void this.loadDetails(pid);
            }
        }
    }

    // A mutating action changes the current photo's details, so drop its cache entry before re-reading.
    private invalidateCurrent(): void {
        const pid = this.currentPid();
        if (null !== pid) {
            this.detailsCache.delete(pid);
        }
    }

    private renderButtons(details: Details): void {
        if (null !== this.voteButton) {
            this.voteButton.hidden = !details.canVote && !details.voted;
            this.voteButton.classList.toggle('pswp__vote-button--voted', details.voted);
            this.voteButton.classList.toggle(
                'pswp__vote-button--nudge',
                details.canVote && !details.voted && !details.recentVote,
            );
            this.voteButton.title = details.voted ? this.label('voted') : this.label('vote');
        }

        if (null !== this.profileButton) {
            this.profileButton.hidden = !details.taggedSelf;
            this.profileButton.title = this.label('setProfilePhoto');
        }
    }

    private renderPanel(details: Details): void {
        if (null === this.title || null === this.list || null === this.form) {
            return;
        }

        if (null !== this.potwBadge) {
            this.potwBadge.hidden = null === details.photoOfTheWeek;
            const text = this.potwBadge.querySelector('span');
            if (null !== text) {
                text.textContent = this.label('photoOfTheWeek');
            }
        }

        this.title.textContent = (0 === details.memberTags.length && 0 === details.organTags.length)
            ? this.label('noTags')
            : this.label('inThisPhoto');

        const chips = [
            ...details.memberTags.map((tag) => this.memberChip(tag)),
            ...details.organTags.map((tag) => this.organChip(tag)),
        ];
        this.list.replaceChildren(...chips);

        // Hide the add form when the viewer may not tag; keep it in the DOM so the search field survives slide changes.
        this.form.hidden = !details.canTag;
    }

    private memberChip(tag: MemberTag): HTMLElement {
        const chip = document.createElement('span');
        chip.className = 'pswp__photo-tag pswp__photo-tag--member';

        const link = document.createElement('a');
        link.href = this.fill(this.config.memberUrlTemplate, '__LIDNR__', tag.lidnr);
        link.textContent = tag.fullName;
        // A person icon marks this as a member tag, distinct from the organ icon below.
        chip.append(this.icon('fa-user'), link);

        if (tag.canRemove) {
            chip.append(this.removeButton(tag.id));
        }

        return chip;
    }

    private organChip(tag: OrganTag): HTMLElement {
        const chip = document.createElement('span');
        chip.className = 'pswp__photo-tag pswp__photo-tag--organ';
        chip.title = tag.name;
        // A group icon marks this as an organ tag; unlike a member tag it is rendered as plain text, not a link.
        chip.append(this.icon('fa-users'), document.createTextNode(tag.abbr));

        if (tag.canRemove) {
            chip.append(this.removeButton(tag.id));
        }

        return chip;
    }

    private icon(name: string): HTMLElement {
        const icon = document.createElement('i');
        icon.className = `fa-solid ${name} pswp__photo-tag-icon`;

        return icon;
    }

    private removeButton(tagId: number): HTMLElement {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pswp__photo-tag-remove';
        button.setAttribute('aria-label', this.label('removeTag'));
        button.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        button.addEventListener('click', (): void => void this.removeTag(tagId));

        return button;
    }

    private async searchMembers(): Promise<void> {
        if (null === this.searchInput || null === this.results) {
            return;
        }

        const query = this.searchInput.value.trim();
        this.results.replaceChildren();
        if (query.length < 2) {
            return;
        }

        const members = await this.fetchJson<{ lidnr: number; fullName: string }[]>(
            `${this.config.memberSearchUrl}?q=${encodeURIComponent(query)}`,
        );
        // A newer keystroke may already have cleared or changed the box.
        if (null === members || null === this.results || query !== this.searchInput?.value.trim()) {
            return;
        }

        for (const member of members) {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'pswp__photo-tag-result';
            option.textContent = member.fullName;
            option.addEventListener('click', (): void => {
                void this.addTag('member', member.lidnr);
                this.clearSearch();
            });
            this.results.append(option);
        }
    }

    private clearSearch(): void {
        if (null !== this.searchInput) {
            this.searchInput.value = '';
            this.searchInput.focus();
        }
        this.results?.replaceChildren();
    }

    private async loadOrgans(): Promise<Organ[]> {
        if (null === this.organs) {
            this.organs = await this.fetchJson<Organ[]>(this.config.organsUrl) ?? [];
        }

        return this.organs;
    }

    private async addTag(
        type: 'member' | 'organ',
        id: number,
    ): Promise<void> {
        const pid = this.currentPid();
        if (null === pid) {
            return;
        }

        const body: Record<string, string> = { type, id: String(id) };
        // A tag placed on the photo carries its point; a plain add (no placement) tags the whole photo.
        if (null !== this.pendingPosition) {
            body.x = String(this.pendingPosition.x);
            body.y = String(this.pendingPosition.y);
        }

        this.pendingPosition = null;
        await this.post(this.fill(this.config.tagUrlTemplate, '__PHOTO__', pid), body);
        this.invalidateCurrent();
        await this.refresh();
    }

    private renderMarkers(details: Details): void {
        const layer = this.ensureMarkersLayer();
        if (null === layer) {
            return;
        }

        const markers: HTMLElement[] = [];
        for (const tag of details.memberTags) {
            if (null !== tag.x && null !== tag.y) {
                markers.push(this.marker(tag.x, tag.y, tag.fullName, 'member'));
            }
        }

        for (const tag of details.organTags) {
            if (null !== tag.x && null !== tag.y) {
                markers.push(this.marker(tag.x, tag.y, tag.abbr, 'organ'));
            }
        }

        if (null !== this.pendingPosition) {
            markers.push(this.marker(this.pendingPosition.x, this.pendingPosition.y, this.label('newTag'), 'pending'));
        }

        layer.replaceChildren(...markers);
        this.positionMarkers();
    }

    // A single overlay that fills the PhotoSwipe root; markers are positioned inside it in pixels (see positionMarkers).
    private ensureMarkersLayer(): HTMLElement | null {
        const root = this.lightbox.pswp?.element;
        if (!root) {
            return null;
        }

        if (null === this.markersLayer) {
            this.markersLayer = document.createElement('div');
            this.markersLayer.className = 'pswp__photo-markers';
            Object.assign(this.markersLayer.style, {
                position: 'absolute',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                pointerEvents: 'none',
                zIndex: '5',
            });
        }

        if (this.markersLayer.parentElement !== root) {
            root.appendChild(this.markersLayer);
        }

        return this.markersLayer;
    }

    // Place every marker at its normalised point over the current on-screen image rect, in pixels relative to the root.
    private positionMarkers(): void {
        if (null === this.markersLayer) {
            return;
        }

        const image = this.imageRect();
        const root = this.lightbox.pswp?.element?.getBoundingClientRect();
        if (null === image || undefined === root) {
            return;
        }

        for (const marker of Array.from(this.markersLayer.children)) {
            if (!(marker instanceof HTMLElement)) {
                continue;
            }

            const x = Number(marker.dataset.x);
            const y = Number(marker.dataset.y);
            marker.style.left = `${image.left - root.left + x * image.width}px`;
            marker.style.top = `${image.top - root.top + y * image.height}px`;
        }
    }

    // The current image's on-screen rectangle (reflecting zoom and pan). PhotoSwipe's zoom container is zero-sized with
    // the <img> absolutely positioned inside it, so we read the actual <img> from the DOM rather than the slide model
    // (whose content.element is not reliably the displayed image).
    private imageRect(): DOMRect | null {
        const slide = this.lightbox.pswp?.currSlide;
        const image = slide?.container?.querySelector?.('img')
            ?? slide?.holderElement?.querySelector?.('img');

        if (image instanceof HTMLElement) {
            const rect = image.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                return rect;
            }
        }

        return null;
    }

    private marker(
        x: number,
        y: number,
        label: string,
        kind: 'member' | 'organ' | 'pending',
    ): HTMLElement {
        const pending = 'pending' === kind;

        const marker = document.createElement('span');
        marker.className = `pswp__photo-marker pswp__photo-marker--${kind}`;
        marker.dataset.x = String(x);
        marker.dataset.y = String(y);
        Object.assign(marker.style, {
            position: 'absolute',
            width: '16px',
            height: '16px',
            marginLeft: '-8px',
            marginTop: '-8px',
            borderRadius: '50%',
            border: '2px solid #fff',
            boxShadow: '0 0 0 1px rgba(0, 0, 0, 0.45)',
            // One neutral, translucent marker for every tag type; the white border and dark halo keep it legible on
            // any background, so members and organs are not distinguished by colour.
            background: 'rgba(255, 255, 255, 0.5)',
            // Existing tags rest subtly and brighten on hover/tap; the one being placed stays prominent.
            opacity: pending ? '0.95' : '0.45',
            cursor: pending ? 'default' : 'pointer',
            pointerEvents: pending ? 'none' : 'auto',
            transition: 'opacity 0.15s ease-in-out',
        });

        if (pending) {
            return marker;
        }

        // A name label above the dot, revealed on hover (desktop) or tap (mobile), so it is clear who/what is tagged.
        const name = document.createElement('span');
        name.textContent = label;
        Object.assign(name.style, {
            position: 'absolute',
            bottom: '150%',
            left: '50%',
            transform: 'translateX(-50%)',
            display: 'none',
            padding: '0.15rem 0.5rem',
            whiteSpace: 'nowrap',
            fontSize: '0.8rem',
            color: '#fff',
            background: 'rgba(0, 0, 0, 0.8)',
            borderRadius: '0.25rem',
            pointerEvents: 'none',
        });
        marker.append(name);

        const show = (): void => {
            marker.style.opacity = '1';
            name.style.display = 'block';
        };
        const hide = (): void => {
            if ('true' !== marker.dataset.pinned) {
                marker.style.opacity = '0.45';
                name.style.display = 'none';
            }
        };

        marker.addEventListener('mouseenter', show);
        marker.addEventListener('mouseleave', hide);
        // Swallow the pointer/click so a tap on the dot reveals its label instead of toggling PhotoSwipe's UI or zoom.
        const swallow = (event: Event): void => event.stopPropagation();
        marker.addEventListener('pointerdown', swallow);
        marker.addEventListener('pointerup', swallow);
        marker.addEventListener('click', (event: MouseEvent): void => {
            event.stopPropagation();
            const pinned = 'true' === marker.dataset.pinned;
            marker.dataset.pinned = pinned ? 'false' : 'true';
            if (pinned) {
                hide();
            } else {
                show();
            }
        });

        return marker;
    }

    private togglePlacing(): void {
        if (this.placing) {
            this.stopPlacing();

            return;
        }

        this.placing = true;
        this.pendingPosition = null;
        this.placeButton?.classList.add('active');

        // A full-viewport overlay OUTSIDE PhotoSwipe's DOM captures the placement click, so PhotoSwipe never sees it as
        // a tap/drag (which would zoom, pan, toggle its UI or close). The critical styles are set inline so it works
        // even before the stylesheet loads; PhotoSwipe's root sits at z-index 100000, so this must be above it. Escape
        // cancels.
        if (null === this.placingOverlay) {
            this.placingOverlay = document.createElement('div');
            this.placingOverlay.className = 'pswp__photo-place-overlay';
            Object.assign(this.placingOverlay.style, {
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                zIndex: '100001',
                cursor: 'crosshair',
                background: 'rgba(0, 0, 0, 0.2)',
            });

            // A top bar with the instruction and a Cancel control, so placing can be abandoned on touch too (no Escape).
            const hint = document.createElement('div');
            hint.className = 'pswp__photo-place-hint';
            Object.assign(hint.style, {
                position: 'absolute',
                top: '16px',
                left: '50%',
                transform: 'translateX(-50%)',
                display: 'flex',
                alignItems: 'center',
                gap: '0.75rem',
                padding: '0.4rem 0.5rem 0.4rem 0.9rem',
                color: '#fff',
                fontSize: '0.875rem',
                background: 'rgba(0, 0, 0, 0.75)',
                borderRadius: '0.35rem',
            });

            const hintText = document.createElement('span');
            hintText.textContent = this.label('placeHint');

            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.textContent = this.label('cancel');
            Object.assign(cancel.style, {
                padding: '0.15rem 0.6rem',
                color: '#fff',
                background: 'rgba(255, 255, 255, 0.2)',
                border: '1px solid rgba(255, 255, 255, 0.4)',
                borderRadius: '0.25rem',
                cursor: 'pointer',
            });
            // Stop the cancel tap from reaching the overlay (which would place a tag); just abandon placing.
            cancel.addEventListener('click', (event: MouseEvent): void => {
                event.stopPropagation();
                this.stopPlacing();
            });

            hint.append(hintText, cancel);
            this.placingOverlay.append(hint);

            // pointerdown/up are swallowed too, so a drag that starts on the overlay never reaches a gesture handler.
            const swallow = (event: Event): void => event.stopPropagation();
            this.placingOverlay.addEventListener('pointerdown', swallow);
            this.placingOverlay.addEventListener('pointerup', swallow);
            this.placingOverlay.addEventListener('click', (event: MouseEvent): void => this.onPlaceClick(event));
        }

        document.body.appendChild(this.placingOverlay);
        document.addEventListener('keydown', this.onPlacingKeydown);
    }

    private stopPlacing(): void {
        this.placing = false;
        this.placeButton?.classList.remove('active');
        this.placingOverlay?.remove();
        document.removeEventListener('keydown', this.onPlacingKeydown);
    }

    private readonly onPlacingKeydown = (event: KeyboardEvent): void => {
        if ('Escape' === event.key) {
            event.stopPropagation();
            this.stopPlacing();
        }
    };

    private onPlaceClick(event: MouseEvent): void {
        event.preventDefault();
        event.stopPropagation();

        // Normalise the click against the on-screen image rect, which reflects the current zoom and pan.
        const rect = this.imageRect();
        if (null !== rect) {
            this.pendingPosition = {
                x: this.clamp((event.clientX - rect.left) / rect.width),
                y: this.clamp((event.clientY - rect.top) / rect.height),
            };
        }

        this.stopPlacing();

        if (null !== this.details && null !== this.pendingPosition) {
            this.renderMarkers(this.details);
        }

        this.searchInput?.focus();
    }

    private clamp(value: number): number {
        return Math.min(1, Math.max(0, value));
    }

    private async removeTag(tagId: number): Promise<void> {
        await this.post(this.fill(this.config.tagRemoveUrlTemplate, '__TAG__', tagId));
        this.invalidateCurrent();
        await this.refresh();
    }

    private async vote(): Promise<void> {
        const pid = this.currentPid();
        if (null === pid) {
            return;
        }

        await this.post(this.fill(this.config.voteUrlTemplate, '__PHOTO__', pid));
        this.invalidateCurrent();
        await this.refresh();
    }

    private async setProfilePhoto(): Promise<void> {
        const pid = this.currentPid();
        if (null === pid) {
            return;
        }

        await this.post(this.fill(this.config.profileUrlTemplate, '__PHOTO__', pid));
    }

    private fill(
        template: string,
        placeholder: string,
        id: number,
    ): string {
        return template.replace(placeholder, String(id));
    }

    private async fetchJson<T>(url: string): Promise<T | null> {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });

            return response.ok ? await response.json() as T : null;
        } catch {
            return null;
        }
    }

    private async post(
        url: string,
        body: Record<string, string> = {},
    ): Promise<void> {
        try {
            await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(body),
            });
        } catch {
            // A failed action leaves the panel as it was; the next refresh reconciles with the server.
        }
    }
}
