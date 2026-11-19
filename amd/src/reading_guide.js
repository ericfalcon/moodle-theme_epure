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
 * Reading guide of the display preferences (« Aa »): a ruler under the line read, or a mask that darkens all but a few
 * lines around it. It follows the pointer, or the element reached with the keyboard. Drawn over the page for the eyes
 * only: hidden from the screen readers, it lets every click through and changes nothing under it.
 *
 * @module     theme_epure/reading_guide
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @type {string} Guide shown: ruler, mask, or none. */
let mode = '';
/** @type {HTMLElement[]} Its elements: the ruler, or the two parts of the mask. */
let parts = [];
/** @type {number|null} Height of the page where the guide is, from the top of the screen. */
let line = null;
/** @type {boolean} Whether the guide is to be moved at the next frame of the screen. */
let pending = false;

/**
 * The height of a line of text of the page, in pixels.
 *
 * @returns {number}
 */
const lineHeight = () => parseFloat(getComputedStyle(document.body).fontSize) * 1.5;

/**
 * Puts the guide at a height of the screen.
 */
const place = () => {
    if (line === null || !parts.length) {
        return;
    }
    parts.forEach((part) => {
        part.hidden = false;
    });
    if (mode === 'ruler') {
        parts[0].style.top = `${Math.round(line - parts[0].offsetHeight / 2)}px`;
        return;
    }
    // Two lines and a half open on each side of the line read.
    const open = lineHeight() * 2.5;
    parts[0].style.height = `${Math.max(0, Math.round(line - open))}px`;
    parts[1].style.height = `${Math.max(0, Math.round(window.innerHeight - line - open))}px`;
};

/**
 * Moves the guide at the next frame of the screen: the pointer moves many times between two frames, the guide only
 * once per frame.
 */
const later = () => {
    if (!pending) {
        pending = true;
        window.requestAnimationFrame(() => {
            pending = false;
            place();
        });
    }
};

/**
 * Follows the pointer (a mouse or a pen: a finger covers what it reads).
 *
 * @param {PointerEvent} e Event.
 */
const pointer = (e) => {
    if (e.pointerType !== 'touch') {
        line = e.clientY;
        later();
    }
};

/**
 * Follows the element reached with the keyboard: the guide goes to its middle.
 *
 * @param {FocusEvent} e Event.
 */
const focus = (e) => {
    if (e.target instanceof Element && e.target.matches(':focus-visible')) {
        const rect = e.target.getBoundingClientRect();
        line = rect.top + Math.min(rect.height, lineHeight()) / 2;
        later();
    }
};

/**
 * Creates an element of the guide.
 *
 * @param {string} name Its class, after epure-a11y-reader-.
 * @returns {HTMLElement}
 */
const part = (name) => {
    const element = document.createElement('div');
    element.className = `epure-a11y-reader epure-a11y-reader-${name}`;
    element.setAttribute('aria-hidden', 'true');
    element.hidden = true;
    document.body.append(element);
    return element;
};

/**
 * Shows a guide, or none.
 *
 * @param {string} next ruler, mask, or empty for none.
 */
export const set = (next) => {
    if (next === mode) {
        return;
    }
    parts.forEach((element) => element.remove());
    parts = [];
    mode = next;
    if (!mode) {
        document.removeEventListener('pointermove', pointer);
        document.removeEventListener('focusin', focus);
        window.removeEventListener('resize', later);
        return;
    }
    parts = mode === 'ruler' ? [part('ruler')] : [part('top'), part('bottom')];
    document.addEventListener('pointermove', pointer, {passive: true});
    document.addEventListener('focusin', focus);
    window.addEventListener('resize', later);
    place();
};

/**
 * Shows the guide chosen in the preferences of the user, read from the classes of the page.
 */
export const init = () => {
    const html = document.documentElement;
    set(['ruler', 'mask'].find((name) => html.classList.contains(`epure-a11y-guide-${name}`)) || '');
};
