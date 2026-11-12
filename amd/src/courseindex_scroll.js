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
 * The course index brings the current activity into view in its drawer while the page scrolls (Moodle's
 * core_courseformat/local/courseindex/cm, scrollIntoView). When the drawer does not scroll on its own, as when the
 * styles are turned off to check a page (an extension such as « Assistant RGAA »), the browser scrolls the page
 * instead, back to the index, at each step: the page can no longer be scrolled. The index then only brings an
 * activity into view when one of its own containers scrolls; the page is left where the user put it.
 *
 * @module     theme_epure/courseindex_scroll
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Whether an element of the index is inside a container that scrolls on its own, below the page.
 *
 * @param {HTMLElement} element Element of the index.
 * @returns {boolean}
 */
const inOwnScroller = (element) => {
    for (let node = element.parentElement; node && node !== document.body; node = node.parentElement) {
        const overflow = getComputedStyle(node).overflowY;
        if ((overflow === 'auto' || overflow === 'scroll') && node.scrollHeight > node.clientHeight) {
            return true;
        }
    }
    return false;
};

let started = false;

/**
 * Starts the guard, once per page.
 */
export const init = () => {
    if (started || !document.getElementById('courseindex')) {
        return;
    }
    started = true;
    const scrollIntoView = Element.prototype.scrollIntoView;
    Element.prototype.scrollIntoView = function(...args) {
        if (this.closest?.('#courseindex') && !inOwnScroller(this)) {
            return undefined;
        }
        return scrollIntoView.apply(this, args);
    };
};
