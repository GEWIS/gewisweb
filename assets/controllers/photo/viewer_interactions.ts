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
    organId: number;
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
    latitude: number | null;
    longitude: number | null;
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

// A member or body picked from the tag search, before it is committed as a tag.
interface Candidate {
    type: 'member' | 'organ';
    id: number;
    name: string;
    // Bodies display their abbreviation; the full name rides along as a hover title.
    title?: string;
}

interface ViewerConfig {
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
    // Font Awesome icon for each EXIF row's label, shown before it in the metadata panel.
    private static readonly exifIcons: Record<string, string> = {
        artist: 'fa-user',
        camera: 'fa-camera',
        dateTime: 'fa-calendar-day',
        flash: 'fa-bolt',
        focalLength: 'fa-ruler-horizontal',
        shutterSpeed: 'fa-stopwatch',
        aperture: 'fa-circle-notch',
        iso: 'fa-film',
        coordinates: 'fa-location-dot',
    };

    // Above this many tags the list collapses behind a count by default, so it does not cover the photo.
    private static readonly tagCollapseThreshold = 6;

    private details: Details | null = null;
    private organs: Organ[] | null = null;
    // Details keyed by photo id, so a photo already visited (or prefetched as a neighbour) renders instantly on swipe.
    private readonly detailsCache = new Map<number, Details>();

    private potwBadge: HTMLElement | null = null;
    private title: HTMLButtonElement | null = null;
    private list: HTMLElement | null = null;
    private form: HTMLElement | null = null;
    private memberTab: HTMLElement | null = null;
    private bodyTab: HTMLElement | null = null;
    private searchInput: HTMLInputElement | null = null;
    private suggestions: HTMLElement | null = null;
    private pendingRow: HTMLElement | null = null;
    // Whether the search targets members or bodies (committees, fraternities, ...), and the candidate picked from the
    // suggestions but not yet committed (awaiting a Tag or Place action).
    private activeType: 'member' | 'organ' = 'member';
    private pending: Candidate | null = null;
    // Whether the collapsible tag list is expanded; a photo with many tags starts collapsed so the chips do not cover it.
    private tagsExpanded = false;

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
        this.lightbox.on('change', (): void => {
            // A new photo starts with the tag list collapsed again; renderPanel re-decides based on the tag count.
            this.tagsExpanded = false;
            void this.refresh();
        });
        // While the member is typing in the tag search, keep PhotoSwipe from swallowing the keys it acts on: 'z' toggles
        // zoom, and the arrow keys change slide. Both are needed for the text field instead (arrows only once it has a
        // value, so an empty field still navigates).
        this.lightbox.on('keydown', (event: any): void => {
            const search = this.searchInput;
            if (null === search || document.activeElement !== search) {
                return;
            }

            const key = event.originalEvent.key;
            if (
                'z' === key
                || ('' !== search.value && ('ArrowLeft' === key || 'ArrowRight' === key))
            ) {
                event.preventDefault();
            }
        });
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

        // order 11 sits between the built-in zoom and the share button.
        ui.registerElement({
            name: 'metadata-ui',
            appendTo: 'root',
            onInit: (element: HTMLElement): void => this.buildMetadataPanel(element),
        });

        ui.registerElement({
            name: 'photo-of-the-week',
            appendTo: 'root',
            // The photo-of-the-week badge sits on its own in the bottom-right corner (outside the bottom-centre tag
            // panel), shown when a photo is or was the photo of the week.
            onInit: (element: HTMLElement): void => {
                element.classList.add('pswp__photo-potw');
                element.hidden = true;
                element.innerHTML = '<i class="fa-solid fa-star"></i><span class="pswp__photo-potw-text"></span>';
                this.potwBadge = element;
            },
        });

        ui.registerElement({
            name: 'info-button',
            order: 11,
            isButton: true,
            tagName: 'button',
            title: this.label('information'),
            html: spriteIcon(this.config.iconSpriteUrl, 'circle-info'),
            onInit: (element: HTMLElement): void => {
                element.addEventListener('click', (): void => this.toggleMetadata());
            },
        });

        // Orders above the preloader (7) sit right of its margin-right:auto; 8 leads that cluster (ahead of
        // album/zoom/info). vote registers before profile, so it sorts first on the shared order.
        ui.registerElement({
            name: 'vote-button',
            order: 8,
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
            order: 8,
            isButton: true,
            tagName: 'button',
            html: spriteIcon(this.config.iconSpriteUrl, 'image-portrait'),
            onInit: (element: HTMLElement): void => {
                this.profileButton = element;
                // Hidden until the details confirm the viewer is tagged, so it does not flash on open before the
                // first render.
                element.hidden = true;
                element.addEventListener('click', (): void => void this.setProfilePhoto());
            },
        });
    }

    private buildPanel(root: HTMLElement): void {
        root.classList.add('pswp__photo-tags');
        // Keep the wheel to the panel's own scrollable lists; otherwise it bubbles to PhotoSwipe and zooms the photo.
        root.addEventListener('wheel', (event: WheelEvent): void => event.stopPropagation());

        this.title = document.createElement('button');
        this.title.type = 'button';
        this.title.className = 'pswp__photo-tags-title';

        this.list = document.createElement('div');
        this.list.className = 'pswp__photo-tags-list';

        this.form = this.buildAddForm();

        root.append(this.title, this.list, this.form);
    }

    private buildMetadataPanel(root: HTMLElement): void {
        root.classList.add('pswp__photo-metadata');
        root.hidden = true;
        // As with the tag panel, a wheel over the metadata list must scroll it, not zoom the photo underneath.
        root.addEventListener('wheel', (event: WheelEvent): void => event.stopPropagation());
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
        const coordinates =
            null === exif.latitude || null === exif.longitude
                ? unknown
                : `${exif.latitude}, ${exif.longitude}`;
        const rows: [string, string][] = [
            ['artist', exif.artist ?? unknown],
            ['camera', exif.camera ?? unknown],
            ['dateTime', exif.dateTime],
            ['flash', flash],
            ['focalLength', null === exif.focalLength ? unknown : `${exif.focalLength} mm`],
            ['shutterSpeed', exif.shutterSpeed ?? unknown],
            ['aperture', exif.aperture ?? unknown],
            ['iso', null === exif.iso ? unknown : String(exif.iso)],
            ['coordinates', coordinates],
        ];

        const table = document.createElement('table');
        table.className = 'pswp__photo-metadata-table';
        for (const [key, value] of rows) {
            const row = document.createElement('tr');
            const label = document.createElement('th');
            const icon = document.createElement('i');
            icon.className = `fa-solid ${ViewerInteractions.exifIcons[key] ?? 'fa-circle-info'} pswp__photo-metadata-icon`;
            const labelText = document.createElement('span');
            labelText.textContent = this.label(key);
            label.append(icon, labelText);
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

        // The member/body toggle and the search field share one row.
        const controls = document.createElement('div');
        controls.className = 'pswp__photo-tag-controls';

        const toggle = document.createElement('div');
        toggle.className = 'pswp__photo-tag-toggle';
        this.memberTab = this.typeTab('member', this.label('member'));
        this.bodyTab = this.typeTab('organ', this.label('body'));
        toggle.append(this.memberTab, this.bodyTab);

        const searchWrap = document.createElement('div');
        searchWrap.className = 'pswp__photo-tag-searchwrap';
        const searchIcon = document.createElement('i');
        searchIcon.className = 'fa-solid fa-magnifying-glass pswp__photo-tag-search-icon';
        this.searchInput = document.createElement('input');
        this.searchInput.type = 'search';
        this.searchInput.className = 'form-control form-control-sm pswp__photo-tag-search';
        this.searchInput.placeholder = this.label('searchMembers');
        this.searchInput.addEventListener('input', (): void => void this.search());
        this.suggestions = document.createElement('div');
        this.suggestions.className = 'pswp__photo-tag-suggestions';
        searchWrap.append(searchIcon, this.searchInput, this.suggestions);

        controls.append(toggle, searchWrap);

        // The picked member/body, awaiting a Tag or Place action. Filled by renderPending, hidden until then.
        this.pendingRow = document.createElement('div');
        this.pendingRow.className = 'pswp__photo-tag-pending';
        this.pendingRow.hidden = true;

        form.append(controls, this.pendingRow);

        return form;
    }

    private typeTab(
        type: 'member' | 'organ',
        label: string,
    ): HTMLElement {
        const tab = document.createElement('button');
        tab.type = 'button';
        tab.className = 'pswp__photo-tag-toggle-btn';
        tab.textContent = label;
        tab.classList.toggle('active', this.activeType === type);
        tab.addEventListener('click', (): void => this.selectType(type));

        return tab;
    }

    // Switch the search between members and bodies, resetting the query, the suggestions and any pending pick.
    private selectType(type: 'member' | 'organ'): void {
        this.activeType = type;
        this.memberTab?.classList.toggle('active', 'member' === type);
        this.bodyTab?.classList.toggle('active', 'organ' === type);

        if (null !== this.searchInput) {
            this.searchInput.value = '';
            this.searchInput.placeholder = this.label('member' === type ? 'searchMembers' : 'searchBodies');
            this.searchInput.focus();
        }

        this.suggestions?.replaceChildren();
        this.clearPending();
        // Bodies are filtered client-side, so make sure they are loaded before the first search.
        if ('organ' === type) {
            void this.loadOrgans();
        }
    }

    private typeIcon(type: 'member' | 'organ'): HTMLElement {
        return this.icon('member' === type ? 'fa-user' : 'fa-users');
    }

    private async refresh(): Promise<void> {
        const pid = this.currentPid();
        if (null === pid) {
            return;
        }

        // A new photo starts with no pending pick, no pending placement, placing off, and an empty search.
        this.stopPlacing();
        this.pendingPosition = null;
        this.pending = null;
        this.renderPending();
        if (null !== this.searchInput) {
            this.searchInput.value = '';
        }

        this.suggestions?.replaceChildren();

        // Fetching from the server (not a cached or prefetched slide) shows a brief "loading" message instead of the
        // previous photo's tags or an empty state; stale markers are cleared until the new ones render.
        if (!this.detailsCache.has(pid)) {
            this.renderTagsLoading();
            this.markersLayer?.replaceChildren();
        }

        this.details = await this.loadDetails(pid);
        // The slide may have changed again while the request was in flight.
        if (pid !== this.currentPid()) {
            return;
        }

        if (null === this.details) {
            this.renderTagsError();

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
            const week = details.photoOfTheWeek;
            this.potwBadge.hidden = null === week;
            const text = this.potwBadge.querySelector('.pswp__photo-potw-text');
            if (null !== week && null !== text) {
                const [year, month, day] = week.split('-').map(Number);
                const iso = this.isoWeek(new Date(year, month - 1, day));
                text.replaceChildren(
                    this.textSpan(this.label('photoOfTheWeek'), 'pswp__photo-potw-title'),
                    this.textSpan(`${this.label('week')} ${iso.week} · ${iso.year}`, 'pswp__photo-potw-week'),
                );
            }
        }

        const chips = [
            ...details.memberTags.map((tag) => this.memberChip(tag)),
            ...details.organTags.map((tag) => this.organChip(tag)),
        ];
        this.list.replaceChildren(...chips);

        this.renderTagsHeader(details.memberTags.length + details.organTags.length);

        // Hide the add form when the viewer may not tag; keep it in the DOM so the search field survives slide changes.
        this.form.hidden = !details.canTag;
    }

    // Show a single header message (loading / error) with the tag list and badge cleared, so a slide change never
    // flashes the previous photo's tags or an empty "no tags" state.
    private renderTagsMessage(message: string): void {
        if (null === this.title || null === this.list) {
            return;
        }

        this.title.replaceChildren(document.createTextNode(message));
        this.title.classList.remove('pswp__photo-tags-title--toggle');
        this.title.disabled = true;
        this.title.onclick = null;
        this.list.hidden = true;
        this.list.replaceChildren();

        if (null !== this.potwBadge) {
            this.potwBadge.hidden = true;
        }
    }

    private renderTagsLoading(): void {
        this.renderTagsMessage(this.label('loadingTags'));
    }

    private renderTagsError(): void {
        this.renderTagsMessage(this.label('tagsError'));
        // The details did not load, so hide the tagging controls too.
        if (null !== this.form) {
            this.form.hidden = true;
        }
    }

    // The header labels the tag list and, once a photo carries more than a handful of tags, doubles as a collapse
    // toggle (count + chevron) with the list hidden by default, so a heavily-tagged photo is not covered by its chips.
    // Placed tags still show as dots on the photo regardless.
    private renderTagsHeader(count: number): void {
        if (null === this.title || null === this.list) {
            return;
        }

        if (0 === count) {
            this.title.replaceChildren(document.createTextNode(this.label('noTags')));
            this.title.classList.remove('pswp__photo-tags-title--toggle');
            this.title.disabled = true;
            this.title.onclick = null;
            this.list.hidden = true;

            return;
        }

        const collapsible = count > ViewerInteractions.tagCollapseThreshold;
        const expanded = !collapsible || this.tagsExpanded;

        const label = document.createElement('span');
        label.textContent = `${this.label('inThisPhoto')} (${count})`;
        const children: Node[] = [label];

        if (collapsible) {
            const chevron = document.createElement('i');
            chevron.className = `fa-solid ${expanded ? 'fa-chevron-up' : 'fa-chevron-down'} pswp__photo-tags-chevron`;
            children.push(chevron);
        }

        this.title.replaceChildren(...children);
        this.title.classList.toggle('pswp__photo-tags-title--toggle', collapsible);
        this.title.disabled = !collapsible;
        this.title.setAttribute('aria-expanded', String(expanded));
        this.title.onclick = collapsible
            ? (): void => {
                this.tagsExpanded = !this.tagsExpanded;
                this.renderTagsHeader(count);
            }
            : null;

        this.list.hidden = !expanded;
    }

    private taggedOrganIds(): Set<number> {
        return new Set((this.details?.organTags ?? []).map((tag) => tag.organId));
    }

    private taggedLidnrs(): Set<number> {
        return new Set((this.details?.memberTags ?? []).map((tag) => tag.lidnr));
    }

    private memberChip(tag: MemberTag): HTMLElement {
        const chip = document.createElement('span');
        chip.className = 'pswp__photo-tag pswp__photo-tag--member';

        const link = document.createElement('a');
        link.href = this.fill(this.config.memberUrlTemplate, '__LIDNR__', tag.lidnr);
        link.textContent = tag.fullName;
        link.className = 'pswp__photo-tag-name';
        // A person icon marks this as a member tag, distinct from the group icon below.
        chip.append(this.icon('fa-user'), link);

        this.decorateChip(chip, tag);

        return chip;
    }

    private organChip(tag: OrganTag): HTMLElement {
        const chip = document.createElement('span');
        chip.className = 'pswp__photo-tag pswp__photo-tag--organ';
        chip.title = tag.name;
        // A group icon marks this as a body tag; unlike a member tag it is plain text, not a link.
        chip.append(this.icon('fa-users'), this.textSpan(tag.abbr, 'pswp__photo-tag-name'));

        this.decorateChip(chip, tag);

        return chip;
    }

    // The shared trailing bits of a tag chip: a dot when the tag is pinned to a point, then a remove control.
    private decorateChip(
        chip: HTMLElement,
        tag: MemberTag | OrganTag,
    ): void {
        if (null !== tag.x && null !== tag.y) {
            const placed = document.createElement('span');
            placed.className = 'pswp__photo-tag-placed';
            placed.title = this.label('placedOnPhoto');
            chip.append(placed);
        }

        if (tag.canRemove) {
            chip.append(this.removeButton(tag.id));
        }
    }

    private icon(name: string): HTMLElement {
        const icon = document.createElement('i');
        icon.className = `fa-solid ${name} pswp__photo-tag-icon`;

        return icon;
    }

    private textSpan(
        text: string,
        className: string,
    ): HTMLElement {
        const span = document.createElement('span');
        span.className = className;
        span.textContent = text;

        return span;
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

    // Search the active pool (members via the server, bodies from the loaded list) and show the matches. Typing clears
    // any pending pick so the two never disagree.
    private async search(): Promise<void> {
        if (null === this.searchInput || null === this.suggestions) {
            return;
        }

        this.clearPending();
        const type = this.activeType;
        const query = this.searchInput.value.trim();
        this.suggestions.replaceChildren();
        if (query.length < 2) {
            return;
        }

        let candidates: Candidate[];
        if ('member' === type) {
            candidates = await this.searchMembers(query);
        } else {
            await this.loadOrgans();
            candidates = this.searchBodies(query);
        }

        // A newer keystroke or a type switch may have moved on while a member search was in flight.
        if (
            null === this.suggestions
            || type !== this.activeType
            || query !== this.searchInput?.value.trim()
        ) {
            return;
        }

        this.renderSuggestions(candidates);
    }

    private async searchMembers(query: string): Promise<Candidate[]> {
        const members = await this.fetchJson<{ lidnr: number; fullName: string }[]>(
            `${this.config.memberSearchUrl}?q=${encodeURIComponent(query)}`,
        ) ?? [];
        const tagged = this.taggedLidnrs();

        return members
            .filter((member) => !tagged.has(member.lidnr))
            .map((member) => ({
                type: 'member',
                id: member.lidnr,
                name: member.fullName,
            }));
    }

    private searchBodies(query: string): Candidate[] {
        const needle = query.toLowerCase();
        const tagged = this.taggedOrganIds();

        return (this.organs ?? [])
            .filter((organ) =>
                !tagged.has(organ.id)
                && (organ.name.toLowerCase().includes(needle) || organ.abbr.toLowerCase().includes(needle)))
            .slice(0, 6)
            .map((organ) => ({
                type: 'organ',
                id: organ.id,
                name: organ.abbr,
                title: organ.name,
            }));
    }

    private renderSuggestions(candidates: Candidate[]): void {
        if (null === this.suggestions) {
            return;
        }

        this.suggestions.replaceChildren(...candidates.map((candidate) => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'pswp__photo-tag-suggestion';
            row.title = candidate.title ?? '';
            row.append(
                this.typeIcon(candidate.type),
                this.textSpan(candidate.name, 'pswp__photo-tag-suggestion-name'),
            );
            row.addEventListener('click', (): void => this.setPending(candidate));

            return row;
        }));
    }

    private setPending(candidate: Candidate): void {
        this.pending = candidate;
        this.suggestions?.replaceChildren();
        if (null !== this.searchInput) {
            this.searchInput.value = '';
        }

        this.renderPending();
    }

    private clearPending(): void {
        if (null === this.pending) {
            return;
        }

        this.pending = null;
        this.renderPending();
    }

    // Build (or hide) the pending-pick row: the chosen member/body with a Tag and a Place on photo action.
    private renderPending(): void {
        if (null === this.pendingRow) {
            return;
        }

        if (null === this.pending) {
            this.pendingRow.replaceChildren();
            this.pendingRow.hidden = true;

            return;
        }

        const clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'pswp__photo-tag-pending-clear';
        clear.setAttribute('aria-label', this.label('cancel'));
        clear.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        clear.addEventListener('click', (): void => this.clearPending());

        const tag = document.createElement('button');
        tag.type = 'button';
        tag.className = 'pswp__photo-tag-action pswp__photo-tag-action--tag';
        tag.textContent = this.label('tag');
        tag.addEventListener('click', (): void => void this.tagPending());

        const place = document.createElement('button');
        place.type = 'button';
        place.className = 'pswp__photo-tag-action pswp__photo-tag-action--place';
        place.innerHTML = '<i class="fa-solid fa-crosshairs"></i>';
        place.append(document.createTextNode(this.label('placeOnPhoto')));
        place.addEventListener('click', (): void => this.placePending());

        this.pendingRow.title = this.pending.title ?? '';
        this.pendingRow.replaceChildren(
            this.typeIcon(this.pending.type),
            this.textSpan(this.pending.name, 'pswp__photo-tag-pending-name'),
            clear,
            tag,
            place,
        );
        this.pendingRow.hidden = false;
    }

    // Tag the pending pick on the whole photo (no point).
    private async tagPending(): Promise<void> {
        if (null === this.pending) {
            return;
        }

        const { type, id } = this.pending;
        this.pending = null;
        this.pendingPosition = null;
        this.renderPending();
        await this.addTag(type, id);
    }

    // Start placing the pending pick on a point of the photo; onPlaceClick commits it there.
    private placePending(): void {
        if (null === this.pending) {
            return;
        }

        this.startPlacing();
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

    private startPlacing(): void {
        this.placing = true;
        this.pendingPosition = null;

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

        const rect = this.imageRect();
        if (null === rect || null === this.pending) {
            this.stopPlacing();

            return;
        }

        // Normalise the click against the on-screen image rect, which reflects the current zoom and pan.
        this.pendingPosition = {
            x: this.clamp((event.clientX - rect.left) / rect.width),
            y: this.clamp((event.clientY - rect.top) / rect.height),
        };
        this.stopPlacing();

        // addTag reads pendingPosition, so this pins the pending pick to the placed point.
        const { type, id } = this.pending;
        this.pending = null;
        this.renderPending();
        void this.addTag(type, id);
    }

    private clamp(value: number): number {
        return Math.min(1, Math.max(0, value));
    }

    // The ISO-8601 week number and week-year of a date, for the Photo of the Week badge (as the old viewer showed).
    private isoWeek(date: Date): { week: number; year: number } {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        // Shift to the Thursday of this ISO week, which fixes both the week number and the week-year.
        d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        const week = Math.ceil(((d.getTime() - yearStart.getTime()) / 86_400_000 + 1) / 7);

        return {
            week,
            year: d.getUTCFullYear(),
        };
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
