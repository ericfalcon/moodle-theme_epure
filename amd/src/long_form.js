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
 * Long settings forms (course, activity, section): the sections as cards, the buttons to save at the bottom of the
 * screen, and on wide screens a list of the sections beside the form that opens and shows each one, the sections with
 * an error marked.
 *
 * The fields, their order and the saving stay Moodle's: only the way to reach them changes.
 *
 * @module     theme_epure/long_form
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';

/** @var {number} Width of the page from which the list of the sections goes beside the form, in pixels. */
const SIDE_WIDTH = 1140;

/** @var {number} Number of sections from which a form gets the list. */
const MIN_SECTIONS = 3;

/**
 * The sections of a form that are shown.
 *
 * @param {HTMLFormElement} form Form.
 * @returns {HTMLFieldSetElement[]}
 */
const sections = (form) => [...form.querySelectorAll('fieldset.collapsible')]
    .filter((fieldset) => fieldset.id && fieldset.querySelector('legend') && fieldset.getClientRects().length);

/**
 * Whether a section has a field with an error.
 *
 * @param {HTMLFieldSetElement} fieldset Section.
 * @returns {boolean}
 */
const hasError = (fieldset) => !!fieldset.querySelector('.is-invalid')
    || [...fieldset.querySelectorAll('.invalid-feedback, .form-control-feedback')]
        .some((feedback) => feedback.textContent.trim() !== '');

/**
 * Opens a section if it is folded, then shows it.
 *
 * @param {HTMLFieldSetElement} fieldset Section.
 */
const reveal = (fieldset) => {
    const toggler = fieldset.querySelector('a.fheader');
    if (toggler && toggler.getAttribute('aria-expanded') === 'false') {
        toggler.click();
    }
    const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    fieldset.scrollIntoView({block: 'start', behavior: still ? 'auto' : 'smooth'});
    (toggler || fieldset).focus({preventScroll: true});
};

/**
 * Builds the list of the sections.
 *
 * @param {HTMLFieldSetElement[]} items Sections.
 * @param {string} label Name of the list.
 * @param {string} error Text read after a section with an error.
 * @returns {HTMLElement}
 */
const buildList = (items, label, error) => {
    const nav = document.createElement('nav');
    nav.className = 'epure-longform-nav';
    nav.setAttribute('aria-label', label);
    const list = document.createElement('ul');
    items.forEach((fieldset) => {
        const link = document.createElement('a');
        link.href = '#' + fieldset.id;
        link.dataset.section = fieldset.id;
        link.textContent = fieldset.querySelector('legend').textContent.trim();
        const mark = document.createElement('span');
        mark.className = 'epure-longform-error sr-only visually-hidden';
        mark.textContent = ' (' + error + ')';
        link.append(mark);
        link.addEventListener('click', (event) => {
            event.preventDefault();
            // The section chosen stays the current one while the page scrolls to it, even if it cannot reach the top.
            link.closest('nav').dataset.hold = String(Date.now() + 1000);
            link.closest('nav').querySelectorAll('a').forEach((other) => other.removeAttribute('aria-current'));
            link.setAttribute('aria-current', 'true');
            reveal(fieldset);
        });
        const item = document.createElement('li');
        item.append(link);
        list.append(item);
    });
    nav.append(list);
    return nav;
};

/**
 * Marks the sections with an error in the list.
 *
 * @param {HTMLElement} nav List.
 * @param {HTMLFieldSetElement[]} items Sections.
 */
const markErrors = (nav, items) => {
    items.forEach((fieldset) => {
        const link = nav.querySelector('[data-section="' + fieldset.id + '"]');
        link?.classList.toggle('epure-longform-invalid', hasError(fieldset));
    });
};

/**
 * Marks a section as the current one in the list.
 *
 * @param {HTMLElement} nav List.
 * @param {string} id Identifier of the section.
 */
const setCurrent = (nav, id) => {
    nav.querySelectorAll('a').forEach((link) => {
        if (link.dataset.section === id) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    });
};

/**
 * Marks in the list the section being read: the last one whose title has gone past the top of the screen.
 *
 * @param {HTMLElement} nav List.
 * @param {HTMLFieldSetElement[]} items Sections.
 */
const followScroll = (nav, items) => {
    let waiting = false;
    const update = () => {
        waiting = false;
        if (Date.now() < Number(nav.dataset.hold || 0)) {
            return;
        }
        const line = (document.querySelector('.navbar.fixed-top')?.offsetHeight || 0) + 96;
        const current = items.filter((fieldset) => fieldset.getBoundingClientRect().top <= line).pop() || items[0];
        setCurrent(nav, current.id);
    };
    const later = () => {
        if (!waiting) {
            waiting = true;
            window.requestAnimationFrame(update);
        }
    };
    // Moodle scrolls the window, or the page itself with the drawers.
    window.addEventListener('scroll', later, {passive: true});
    document.getElementById('page')?.addEventListener('scroll', later, {passive: true});
    update();
};

/**
 * Starts the long form of the page, if there is one.
 */
export const init = async() => {
    const form = [...document.querySelectorAll('#region-main form.mform')]
        .sort((a, b) => sections(b).length - sections(a).length)[0];
    if (!form) {
        return;
    }
    // The sections as cards, as on the preferences.
    form.classList.add('epure-longform-form');
    // The buttons to save, at the bottom of the screen.
    const actions = form.querySelector('[id^="fgroup_id_buttonar"]')
        || form.querySelector('[name="submitbutton"]')?.closest('.fitem, .form-group');
    actions?.classList.add('epure-longform-actions');

    const items = sections(form);
    if (items.length < MIN_SECTIONS) {
        return;
    }
    const [label, error] = await getStrings([
        {key: 'formsections', component: 'theme_epure'},
        {key: 'error', component: 'moodle'},
    ]);
    const nav = buildList(items, label, error);
    const layout = document.createElement('div');
    layout.className = 'epure-longform';
    form.before(layout);
    layout.append(nav, form);

    markErrors(nav, items);
    form.addEventListener('focusout', () => markErrors(nav, items));
    form.addEventListener('submit', () => window.setTimeout(() => markErrors(nav, items)));
    followScroll(nav, items);

    // The list goes beside the form only when the page is wide enough, the drawers open or closed.
    const page = document.getElementById('page') || document.body;
    const place = () => document.body.classList.toggle('epure-longform-side', page.clientWidth >= SIDE_WIDTH);
    place();
    if ('ResizeObserver' in window) {
        new ResizeObserver(place).observe(page);
    } else {
        window.addEventListener('resize', place);
    }
};
