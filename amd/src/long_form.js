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
 * screen with the state of the form (no changes, changes not saved, fields to correct), announced to screen readers,
 * and on wide screens a list of the sections beside the form that opens and shows each one, the sections with an error
 * marked.
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
        // Above the first section (the fields before it), none is marked.
        const current = items.filter((fieldset) => fieldset.getBoundingClientRect().top <= line).pop();
        setCurrent(nav, current ? current.id : '');
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
 * Puts the fields before the first section (IOMAD's forms start so, some have no section at all) in a card of their
 * own, as the sections.
 *
 * @param {HTMLFormElement} form Form.
 * @param {HTMLElement|null} actions Buttons to save, left out.
 */
const gatherLead = (form, actions) => {
    const lead = [];
    for (const child of form.children) {
        if (child.tagName === 'FIELDSET') {
            break;
        }
        if (child !== actions && child.matches('.fitem, .form-group')) {
            lead.push(child);
        }
    }
    if (lead.length) {
        const box = document.createElement('div');
        box.className = 'epure-longform-lead';
        lead[0].before(box);
        box.append(...lead);
    }
};

/**
 * Whether a field is the text of an editor (TinyMCE or Atto copy it into the form only now and then).
 *
 * @param {string} name Name of the field.
 * @returns {boolean}
 */
const isEditorText = (name) => name.endsWith('[text]');

/**
 * The values of a form, to tell whether they changed: its fields, but not its buttons, its key of session, the sections
 * that Moodle remembers as expanded, nor the texts of the editors (see editorsChanged).
 *
 * @param {HTMLFormElement} form Form.
 * @returns {string}
 */
const snapshot = (form) => JSON.stringify([...new FormData(form).entries()]
    .filter(([name, value]) => typeof value === 'string' && name !== 'sesskey' && !isEditorText(name)
        && !/^mform_(isexpanded|showmore|showadvanced)_/.test(name)));

/**
 * Follows the texts of the editors of a form: the text of TinyMCE as it is typed (it copies it into the form only when
 * it loses the focus, written its own way), the field itself otherwise. Until the user types, pastes or clicks in an
 * editor, its text is the one it starts with, whatever TinyMCE does to it when it gets ready.
 *
 * @param {HTMLFormElement} form Form.
 * @returns {Function} Whether the text of an editor changed.
 */
const followEditors = (form) => {
    const fields = [...form.querySelectorAll('textarea')].filter((field) => isEditorText(field.name));
    const start = new Map(fields.map((field) => [field, {editor: null, touched: false, value: field.value}]));
    return () => fields.some((field) => {
        const first = start.get(field);
        const editor = window.tinymce?.get(field.id);
        if (!editor?.initialized) {
            return field.value !== first.value;
        }
        if (first.editor !== editor) {
            first.editor = editor;
            first.touched = false;
            const touch = () => {
                first.touched = true;
            };
            editor.on('input keydown paste', touch);
            editor.getContainer()?.addEventListener('pointerdown', touch);
        }
        const value = editor.getContent();
        if (!first.touched) {
            first.value = value;
        }
        return value !== first.value;
    });
};

/**
 * The state of the form in the bar of the buttons to save: no changes, changes not saved (a field changed, or the text
 * of an editor), or fields to correct once Moodle sent the form back. Announced at each change.
 *
 * @param {HTMLFormElement} form Form.
 * @param {HTMLElement} actions The bar of the buttons to save.
 */
const followState = async(form, actions) => {
    const [none, unsaved, errors, error] = await getStrings([
        {key: 'longform_nochanges', component: 'theme_epure'},
        {key: 'longform_unsaved', component: 'theme_epure'},
        {key: 'longform_errors', component: 'theme_epure', param: form.querySelectorAll('.is-invalid').length},
        {key: 'longform_errors_one', component: 'theme_epure'},
    ]);
    const status = document.createElement('p');
    status.className = 'epure-longform-status';
    status.setAttribute('role', 'status');
    actions.prepend(status);
    const invalid = form.querySelectorAll('.is-invalid').length;
    let state = '';
    const show = (next, text) => {
        if (next !== state) {
            state = next;
            status.dataset.state = next;
            status.textContent = text;
        }
    };
    if (invalid) {
        // Sent back by Moodle: not saved, and so until it is saved again.
        show('errors', invalid === 1 ? error : errors);
        form.addEventListener('input', () => show('unsaved', unsaved), {once: true});
        return;
    }
    show('none', none);
    // Until the user acts on a field, the form is as loaded: what the scripts of Moodle set or turn off (dates, lists
    // with suggestions) is not a change. The texts of the editors are read every two seconds.
    let initial = snapshot(form);
    let touched = false;
    const editorsChanged = followEditors(form);
    const touch = (e) => {
        if (e.isTrusted && e.target.closest?.('.fitem')) {
            touched = true;
        }
    };
    const update = (e) => {
        if (e) {
            touch(e);
        }
        if (!touched) {
            initial = snapshot(form);
        }
        const changed = snapshot(form) !== initial || editorsChanged();
        show(changed ? 'unsaved' : 'none', changed ? unsaved : none);
    };
    form.addEventListener('pointerdown', touch);
    form.addEventListener('keydown', touch);
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    window.setInterval(() => update(), 2000);
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
    // Moodle 4.5 gives the group of the buttons an id, Moodle 5.x the name of the group.
    const actions = form.querySelector('[id^="fgroup_id_buttonar"], [data-groupname="buttonar"]')
        || form.querySelector('[name="submitbutton"]')?.closest('.fitem, .form-group');
    actions?.classList.add('epure-longform-actions');
    gatherLead(form, actions);
    if (actions) {
        followState(form, actions);
    }

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
