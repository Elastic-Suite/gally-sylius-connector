import { Controller } from '@hotwired/stimulus';

/**
 * Moves the single search bar element into the desktop or mobile anchor
 * depending on the configured breakpoint, avoiding DOM duplication.
 */
export default class extends Controller {
    static targets = ['bar'];

    static values = {
        desktopAnchor: { type: String, default: '[data-gally-search-bar-anchor="desktop"]' },
        mobileAnchor: { type: String, default: '[data-gally-search-bar-anchor="mobile"]' },
        breakpoint: { type: String, default: '(min-width: 992px)' },
    };

    connect() {
        if (!this.hasBarTarget) {
            console.error('SearchBarMoverController: missing "bar" target, the search bar cannot be moved.');

            return;
        }

        // Read the target once: after the first place() call, the bar element
        // is moved outside this.element, so Stimulus no longer considers it part
        // of this controller's target scope and this.barTarget would throw.
        this.bar = this.barTarget;
        this.mql = window.matchMedia(this.breakpointValue);
        this.handler = (e) => this.place(e.matches);
        this.mql.addEventListener('change', this.handler);
        this.place(this.mql.matches);
    }

    disconnect() {
        this.mql?.removeEventListener('change', this.handler);
    }

    /**
     * Moves the bar element to the appropriate anchor.
     *
     * @param {boolean} isDesktop  true when the viewport matches the configured breakpoint
     */
    place(isDesktop) {
        const selector = isDesktop ? this.desktopAnchorValue : this.mobileAnchorValue;

        const anchor = document.querySelector(selector);
        const bar = this.bar;

        if (!anchor) {
            // Anchor not yet in the DOM (e.g. Turbo page transition) — keep bar
            // in its current position and retry on the next breakpoint change.
            return;
        }

        // Only move if not already in the right place to avoid layout thrashing.
        if (anchor.firstElementChild !== bar) {
            // Remove style="display:none" from controller element on first placement
            this.element.removeAttribute('style');
            this.element.removeAttribute('aria-hidden');
            anchor.appendChild(bar);
        }
    }
}
