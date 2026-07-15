interface MemberTag {
    id: number;
    lidnr: number;
    fullName: string;
    canRemove: boolean;
}

interface OrganTag {
    id: number;
    name: string;
    abbr: string;
    canRemove: boolean;
}

interface Details {
    memberTags: MemberTag[];
    organTags: OrganTag[];
    canTag: boolean;
    canVote: boolean;
    voted: boolean;
    recentVote: boolean;
    taggedSelf: boolean;
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
    labels: Record<string, string>;
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

    private title: HTMLElement | null = null;
    private list: HTMLElement | null = null;
    private form: HTMLElement | null = null;
    private results: HTMLElement | null = null;
    private searchInput: HTMLInputElement | null = null;

    private voteButton: HTMLElement | null = null;
    private profileButton: HTMLElement | null = null;

    constructor(
        private readonly lightbox: any,
        private readonly config: ViewerConfig,
    ) {
        this.lightbox.on('uiRegister', (): void => this.registerElements());
        this.lightbox.on('change', (): void => void this.refresh());
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

        ui.registerElement({
            name: 'photo-tags',
            appendTo: 'root',
            onInit: (element: HTMLElement): void => this.buildPanel(element),
        });

        ui.registerElement({
            name: 'vote-button',
            order: 7,
            isButton: true,
            tagName: 'button',
            html: '<i class="fa-solid fa-thumbs-up"></i><span class="pswp__vote-dot"></span>',
            onInit: (element: HTMLElement): void => {
                this.voteButton = element;
                element.addEventListener('click', (): void => void this.vote());
            },
        });

        ui.registerElement({
            name: 'profile-photo-button',
            order: 6,
            isButton: true,
            tagName: 'button',
            html: '<i class="fa-solid fa-image-portrait"></i>',
            onInit: (element: HTMLElement): void => {
                this.profileButton = element;
                element.addEventListener('click', (): void => void this.setProfilePhoto());
            },
        });
    }

    private buildPanel(root: HTMLElement): void {
        root.classList.add('pswp__photo-tags');

        this.title = document.createElement('span');
        this.title.className = 'pswp__photo-tags-title';

        this.list = document.createElement('div');
        this.list.className = 'pswp__photo-tags-list';

        this.form = this.buildAddForm();

        root.append(this.title, this.list, this.form);
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

        form.append(this.searchInput, this.results, this.organSelect());

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

        this.details = await this.fetchJson<Details>(this.fill(this.config.detailsUrlTemplate, '__PHOTO__', pid));
        // The slide may have changed again while the request was in flight.
        if (pid !== this.currentPid() || null === this.details) {
            return;
        }

        this.renderButtons(this.details);
        this.renderPanel(this.details);
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
        // A group icon marks this as an organ tag. There is no public organ page yet (#1991), so it is plain text.
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

        await this.post(this.fill(this.config.tagUrlTemplate, '__PHOTO__', pid), { type, id: String(id) });
        await this.refresh();
    }

    private async removeTag(tagId: number): Promise<void> {
        await this.post(this.fill(this.config.tagRemoveUrlTemplate, '__TAG__', tagId));
        await this.refresh();
    }

    private async vote(): Promise<void> {
        const pid = this.currentPid();
        if (null === pid) {
            return;
        }

        await this.post(this.fill(this.config.voteUrlTemplate, '__PHOTO__', pid));
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
