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
 * Repairs of the ARIA attributes that Moodle and IOMAD write wrongly on some pages (WCAG 4.1.2), in the page only:
 * aria-hidden="1" becomes "true"; the lists of aria-controls keep the elements that exist (the links to hide the
 * columns of a table name a cell of every row, rows included that the page does not show); the fields of IOMAD
 * known to have an empty label get their name.
 *
 * @module     theme_epure/aria_repair
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Whether a field has a name for the screen readers.
 *
 * @param {HTMLElement} field The field.
 * @return {boolean}
 */
const named = (field) => field.getAttribute('aria-label')?.trim() || field.getAttribute('aria-labelledby')
    || [...(field.labels || [])].some((label) => label.textContent.trim());

/**
 * Repairs the attributes in a part of the page.
 *
 * @param {ParentNode} root The part of the page.
 * @param {Object<string, string>} names Names of the fields of IOMAD with an empty label, by their name.
 */
const repair = (root, names) => {
    root.querySelectorAll('[aria-hidden="1"]').forEach((element) => element.setAttribute('aria-hidden', 'true'));
    root.querySelectorAll('[aria-controls*=" "]').forEach((element) => {
        const ids = element.getAttribute('aria-controls').split(/\s+/).filter((id) => id && document.getElementById(id));
        if (ids.length) {
            element.setAttribute('aria-controls', ids.join(' '));
        } else {
            element.removeAttribute('aria-controls');
        }
    });
    Object.entries(names).forEach(([name, label]) => {
        root.querySelectorAll(`select[name="${name}"], input[name="${name}"]`).forEach((field) => {
            if (!named(field)) {
                field.setAttribute('aria-label', label);
            }
        });
    });
};

/**
 * Repairs the page, and again once it is loaded.
 *
 * @param {Object<string, string>} names Names of the fields of IOMAD with an empty label, by their name.
 */
export const init = (names = {}) => {
    repair(document, names);
    window.addEventListener('load', () => repair(document, names));
};
