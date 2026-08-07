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

        // Read the target once: after the first _place() call, the bar element
        // is moved outside this.element, so Stimulus no longer considers it part
        // of this controller's target scope and this.barTarget would throw.
        this._bar = this.barTarget;
        this._mql = window.matchMedia(this.breakpointValue);
        this._handler = (e) => this._place(e.matches);
        this._mql.addEventListener('change', this._handler);
        this._place(this._mql.matches);
    }

    disconnect() {
        this._mql?.removeEventListener('change', this._handler);
    }

    /**
     * Moves the bar element to the appropriate anchor.
     *
     * @param {boolean} isDesktop  true when the viewport matches the configured breakpoint
     */
    _place(isDesktop) {
        const selector = isDesktop ? this.desktopAnchorValue : this.mobileAnchorValue;

        const anchor = document.querySelector(selector);
        const bar = this._bar;

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
