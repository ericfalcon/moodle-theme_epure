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
 * known to have an empty label get their name; the two menus of Moodle (the site, the page) get a name each, which
 * tells them apart (RGAA 12.6).
 *
 * The hidden choice « Clear my choice » of the questions with one answer is inert while it is hidden (WCAG 2.1.2,
 * 2.4.3): its button is checked before any answer, so the key Tab stopped on the first answer of the question.
 *
 * The list of the items chosen in an autocomplete field (tags, groups…) is a listbox; empty, it only held the text
 * « No selection », which is not an option (WCAG 1.3.1, 4.1.2). The text stays on the screen; the empty list tells
 * the screen readers that nothing is chosen.
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
 * Names the menus of Moodle that have none: the menu of the site and the menu of the page are both navigations.
 *
 * @param {{site: string, page: string}} menus Names of the menus.
 */
const nameMenus = (menus) => {
    document.querySelectorAll('nav.moremenu:not([aria-label]):not([aria-labelledby])').forEach((nav) => {
        const name = nav.closest('.primary-navigation') ? menus.site : menus.page;
        if (name) {
            nav.setAttribute('aria-label', name);
        }
    });
};

/**
 * Keeps the hidden choice « Clear my choice » of the questions out of the keyboard while it is hidden.
 */
const clearChoices = () => {
    const containers = document.querySelectorAll('.qtype_multichoice_clearchoice');
    if (!containers.length) {
        return;
    }
    const sync = (container) => {
        container.inert = container.getAttribute('aria-hidden') === 'true';
    };
    const observer = new MutationObserver((mutations) => mutations.forEach((mutation) => sync(mutation.target)));
    containers.forEach((container) => {
        sync(container);
        observer.observe(container, {attributes: true, attributeFilter: ['aria-hidden']});
    });
};

/**
 * Hides the text « No selection » of the empty lists of the autocomplete fields from the screen readers, now and
 * each time Moodle draws a list again.
 */
const emptySelections = () => {
    const hide = () => document.querySelectorAll('.form-autocomplete-selection[role="listbox"] > :not([role]):not([aria-hidden])')
        .forEach((element) => element.setAttribute('aria-hidden', 'true'));
    hide();
    new MutationObserver((mutations) => {
        if (mutations.some((mutation) => mutation.addedNodes.length)) {
            hide();
        }
    }).observe(document.body, {childList: true, subtree: true});
};

/**
 * Repairs the page, and again once it is loaded.
 *
 * @param {Object<string, string>} names Names of the fields of IOMAD with an empty label, by their name.
 * @param {{site: string, page: string}} menus Names of the menu of the site and of the menu of the page.
 */
export const init = (names = {}, menus = {site: '', page: ''}) => {
    repair(document, names);
    nameMenus(menus);
    clearChoices();
    emptySelections();
    window.addEventListener('load', () => {
        repair(document, names);
        nameMenus(menus);
    });
};
