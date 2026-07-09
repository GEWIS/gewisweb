import { Controller } from '@hotwired/stimulus';

/**
 * Makes an entire activity list item navigate to the activity, while leaving real links and buttons inside it (the
 * title link, Markdown links, the "Show more" toggle) working on their own.
 */
export default class extends Controller {
    static values = { url: String };

    declare readonly urlValue: string;

    open(event: Event): void {
        if ((event.target as Element).closest('a, button')) {
            return;
        }

        window.location.assign(this.urlValue);
    }
}
