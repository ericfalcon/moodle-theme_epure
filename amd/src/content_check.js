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
 * Accessibility of the contents, when a teacher saves a form: the editors are read for the common mistakes (images
 * without a text alternative, tables without headers, links whose text does not say where they go, levels of titles
 * skipped). When some are found, a window lists them, field by field, with « Fix », « Open the accessibility checker »
 * (the one of the editor TinyMCE) and « Save anyway »: nothing is ever blocked.
 *
 * @module     theme_epure/content_check
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getInstanceForElementId} from 'editor_tiny/editor';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import {getString, getStrings} from 'core/str';

/** @var {string[]} Texts of links that do not say where they go. */
const VAGUE_LINKS = ['ici', 'cliquez ici', 'cliquer ici', 'ce lien', 'lien', 'en savoir plus', 'plus', 'suite', 'lire la suite',
    'here', 'click here', 'this link', 'link', 'more', 'read more', 'learn more'];

const text = (element) => element.textContent.replace(/\s+/g, ' ').trim();

/**
 * The mistakes in a content.
 *
 * @param {string} html Content.
 * @returns {Object} Counts by mistake: images, tables, links, headings.
 */
export const check = (html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const found = {images: 0, tables: 0, links: 0, headings: 0};
    doc.querySelectorAll('img').forEach((img) => {
        // Decorative images: alt="" with role="presentation", as the editor marks them.
        const alt = img.getAttribute('alt');
        if (alt === null || (alt.trim() === '' && img.getAttribute('role') !== 'presentation')) {
            found.images++;
        }
    });
    doc.querySelectorAll('table').forEach((table) => {
        if (!table.querySelector('th')) {
            found.tables++;
        }
    });
    doc.querySelectorAll('a[href]').forEach((link) => {
        const label = (link.getAttribute('aria-label') || text(link)).toLowerCase().replace(/[.…:!]+$/, '');
        const image = link.querySelector('img[alt]:not([alt=""])');
        if ((label === '' && !image) || VAGUE_LINKS.includes(label)) {
            found.links++;
        }
    });
    let previous = 0;
    doc.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach((heading) => {
        const level = Number(heading.tagName.substring(1));
        if (previous && level > previous + 1) {
            found.headings++;
        }
        previous = level;
    });
    return found;
};

/**
 * The editors of a form, with their label and their content.
 *
 * @param {HTMLFormElement} form Form.
 * @returns {Object[]} Each with id, label, html and editor (TinyMCE, or null).
 */
const editors = (form) => Array.from(form.querySelectorAll('textarea')).map((textarea) => {
    const editor = textarea.id ? getInstanceForElementId(textarea.id) : null;
    if (!editor && !textarea.closest('[data-fieldtype="editor"]')) {
        return null;
    }
    const item = textarea.closest('.fitem');
    const label = item ? item.querySelector('label, .col-form-label p') : null;
    return {
        id: textarea.id,
        label: label ? text(label) : '',
        html: editor ? editor.getContent() : textarea.value,
        editor: editor || null,
    };
}).filter(Boolean);

/**
 * The window listing the mistakes.
 *
 * @param {Object[]} problems Each with label, found and editor.
 * @returns {Promise<Object>} The modal.
 */
const showProblems = async(problems) => {
    const keys = ['images', 'tables', 'links', 'headings'];
    const strings = await getStrings([
        {key: 'contentcheck_title', component: 'theme_epure'},
        {key: 'contentcheck_intro', component: 'theme_epure'},
        {key: 'contentcheck_save', component: 'theme_epure'},
        {key: 'contentcheck_fix', component: 'theme_epure'},
        {key: 'contentcheck_checker', component: 'theme_epure'},
    ]);
    const body = document.createElement('div');
    const intro = document.createElement('p');
    intro.textContent = strings[1];
    body.append(intro);
    for (const problem of problems) {
        const section = document.createElement('section');
        section.className = 'epure-contentcheck-field';
        if (problem.label) {
            const title = document.createElement('h3');
            title.textContent = problem.label;
            section.append(title);
        }
        const list = document.createElement('ul');
        for (const key of keys) {
            if (problem.found[key]) {
                const item = document.createElement('li');
                item.textContent = await getString('contentcheck_' + key, 'theme_epure', problem.found[key]);
                list.append(item);
            }
        }
        section.append(list);
        if (problem.editor) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-secondary btn-sm';
            button.textContent = strings[4];
            button.dataset.editor = problem.id;
            section.append(button);
        }
        body.append(section);
    }
    return ModalSaveCancel.create({
        title: strings[0],
        body: body.outerHTML,
        buttons: {save: strings[2], cancel: strings[3]},
        show: true,
        removeOnClose: true,
    });
};

/**
 * Checks the editors of a form when it is submitted with a button that saves it.
 *
 * @param {HTMLFormElement} form Form.
 */
const watch = (form) => {
    let confirmed = false;
    form.addEventListener('submit', async(event) => {
        const submitter = event.submitter;
        // Cancel and the buttons that do not save (add a field, for instance) go through.
        if (confirmed || !submitter || submitter.name === 'cancel' || submitter.hasAttribute('formnovalidate')
                || !/^submitbutton/.test(submitter.name || '')) {
            return;
        }
        const problems = editors(form).map((field) => ({...field, found: check(field.html)}))
            .filter((field) => Object.values(field.found).some((count) => count > 0));
        if (!problems.length) {
            return;
        }
        event.preventDefault();
        const modal = await showProblems(problems);
        modal.getRoot().on(ModalEvents.save, () => {
            confirmed = true;
            form.requestSubmit(submitter);
        });
        modal.getRoot().on('click', '[data-editor]', (clicked) => {
            const editor = getInstanceForElementId(clicked.currentTarget.dataset.editor);
            modal.destroy();
            if (editor) {
                window.require(['tiny_accessibilitychecker/checker'], (Checker) => {
                    const Class = Checker.default || Checker;
                    new Class(editor).displayDialogue();
                });
            }
        });
    });
};

/**
 * Watches the forms of the page that have editors.
 */
export const init = () => {
    document.querySelectorAll('form.mform').forEach((form) => {
        if (form.querySelector('textarea')) {
            watch(form);
        }
    });
};
