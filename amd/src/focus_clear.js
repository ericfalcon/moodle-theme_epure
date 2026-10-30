// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Focus never hidden (WCAG 2.4.11): an element reached with the keyboard that lands under a bar fixed at the bottom
 * of the screen (navigation of the phones, « Continue » of the course page, buttons to save of the long forms) or
 * under the bar at the top is scrolled into the clear. Browsers do not scroll an element already on the screen,
 * even hidden under such a bar.
 *
 * @module     theme_epure/focus_clear
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var {string} Bars fixed at the bottom of the screen. */
const BOTTOM = '.epure-mobilenav, .epure-course-continue-sticky, .epure-longform-actions';

/** @var {string} Bar fixed at the top of the screen. */
const TOP = '.navbar.fixed-top';

/** @var {number} Room left between the element and the bar, in pixels. */
const MARGIN = 16;

/**
 * Whether an element stays on the screen while the page scrolls.
 *
 * @param {HTMLElement} element Element.
 * @returns {boolean}
 */
const floating = (element) => {
    const position = getComputedStyle(element).position;
    return (position === 'fixed' || position === 'sticky') && element.getClientRects().length > 0;
};

/**
 * What scrolls: the page itself with Moodle's drawers, else the window.
 *
 * @returns {HTMLElement|Window}
 */
const scroller = () => {
    const page = document.getElementById('page');
    if (page && /(auto|scroll)/.test(getComputedStyle(page).overflowY) && page.scrollHeight > page.clientHeight) {
        return page;
    }
    return window;
};

/**
 * Scrolls the element reached out from under the bars. A bar that sticks to the bottom of its form moves as the page
 * scrolls: the element is checked again, a few times at most, until it is clear.
 *
 * @param {HTMLElement} target Element that got the focus.
 * @param {number} tries Checks left.
 */
const clear = (target, tries = 3) => {
    const rect = target.getBoundingClientRect();
    // An element fixed on the screen itself (the link « Skip to main content » once focused) is never under them.
    if (!rect.height || floating(target)) {
        return;
    }
    let bottom = window.innerHeight;
    document.querySelectorAll(BOTTOM).forEach((bar) => {
        if (bar.contains(target) || !floating(bar)) {
            return;
        }
        const bounds = bar.getBoundingClientRect();
        // A bar stuck at the bottom of the screen, not one met in the middle of the page.
        if (bounds.height && bounds.bottom >= window.innerHeight - bounds.height - MARGIN) {
            bottom = Math.min(bottom, bounds.top);
        }
    });
    let top = 0;
    document.querySelectorAll(TOP).forEach((bar) => {
        if (!bar.contains(target) && floating(bar)) {
            top = Math.max(top, bar.getBoundingClientRect().bottom);
        }
    });
    let delta = 0;
    if (rect.bottom > bottom - MARGIN / 2) {
        delta = Math.min(rect.bottom - bottom + MARGIN, rect.top - top - MARGIN);
    } else if (rect.top < top + MARGIN / 2) {
        delta = rect.top - top - MARGIN;
    }
    if (delta && document.activeElement === target) {
        scroller().scrollBy({top: delta, behavior: 'instant'});
        if (tries > 1) {
            window.requestAnimationFrame(() => clear(target, tries - 1));
        }
    }
};

let started = false;

/**
 * Follows the focus of the page.
 */
export const init = () => {
    if (started) {
        return;
    }
    started = true;
    document.addEventListener('focusin', (event) => {
        if (event.target instanceof HTMLElement && event.target !== document.body) {
            // After the browser has scrolled the element into view, if it did: it does so after the event.
            window.setTimeout(() => clear(event.target), 50);
        }
    });
    // The focus going into a frame (the text editor) leaves the page without a focusin: the frame is then active.
    window.addEventListener('blur', () => {
        window.setTimeout(() => {
            const frame = document.activeElement;
            if (frame instanceof HTMLIFrameElement) {
                clear(frame);
            }
        }, 50);
    });
};
