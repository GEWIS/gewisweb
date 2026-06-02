import { Controller } from '@hotwired/stimulus';

/**
 * Keeps an edit lock alive while the user is actually editing. It pings the server on an interval; after a stretch of
 * inactivity it releases the lock and locks the form down. It also locks the form down if the server reports the lock
 * was lost (taken over by a reviewer or expired); and it best-effort releases the lock when the page is left.
 *
 * Wire it on a wrapper around the edit form:
 *
 * ```
 * data-controller="edit-lock"
 * data-edit-lock-ping-url-value=...
 * data-edit-lock-release-url-value=...
 * data-edit-lock-csrf-token-value=...
 * data-edit-lock-lost-message-value=...
 * ```
 */
export default class extends Controller {
    static values = {
        pingUrl: String,
        releaseUrl: String,
        csrfToken: String,
        lostMessage: String,
        interval: { type: Number, default: 30000 },
        idle: { type: Number, default: 300000 },
    };

    connect() {
        this.lastActivity = Date.now();
        this.lost = false;
        this.submitting = false;
        this.inflight = null;

        this._onActivity = () => { this.lastActivity = Date.now(); };
        this.element.addEventListener('mousedown', this._onActivity);
        this.element.addEventListener('keydown', this._onActivity);

        // Submitting the form is itself a navigation, so the beforeunload handler below would otherwise race a lock
        // release against the save the server is about to perform. Flag the submit so `_release()` skips it; the server
        // releases the lock once the save succeeds.
        this._onSubmit = () => { this.submitting = true; };
        this.element.addEventListener('submit', this._onSubmit);

        this._onUnload = () => this._release();
        window.addEventListener('beforeunload', this._onUnload);

        this.timer = window.setInterval(() => this._tick(), this.intervalValue);
    }

    disconnect() {
        window.clearInterval(this.timer);
        // Abort an in-flight ping so its response cannot land after disconnect and lock down a detached element.
        this.inflight?.abort();
        this.element.removeEventListener('mousedown', this._onActivity);
        this.element.removeEventListener('keydown', this._onActivity);
        this.element.removeEventListener('submit', this._onSubmit);
        window.removeEventListener('beforeunload', this._onUnload);
    }

    async _tick() {
        if (this.lost) {
            return;
        }

        // Idle too long: drop the lock so it frees up for others, then lock the form down.
        if (Date.now() - this.lastActivity > this.idleValue) {
            this._release();
            this._onLost();

            return;
        }

        const controller = new AbortController();
        this.inflight = controller;
        try {
            const response = await fetch(this.pingUrlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: this._body(),
                signal: controller.signal,
            });
            const data = await response.json();
            if (!data.held) {
                this._onLost();
            }
        } catch (error) {
            // A transient network error is harmless: the next tick retries (when disconnected there is no next tick).
        } finally {
            this.inflight = null;
        }
    }

    _onLost() {
        this.lost = true;
        window.clearInterval(this.timer);

        // Lock down the form so a stale editor cannot submit over whoever took over.
        this.element.querySelectorAll('input, select, textarea, button').forEach((field) => {
            field.disabled = true;
        });

        if (this.hasLostMessageValue) {
            const banner = document.createElement('div');
            banner.className = 'alert alert-warning';
            banner.setAttribute('role', 'alert');
            banner.textContent = this.lostMessageValue;
            this.element.prepend(banner);
        }
    }

    _release() {
        if (this.lost || this.submitting || !this.hasReleaseUrlValue) {
            return;
        }

        navigator.sendBeacon(this.releaseUrlValue, this._body());
    }

    _body() {
        const body = new FormData();
        body.append('_csrf_token', this.csrfTokenValue);

        return body;
    }
}
