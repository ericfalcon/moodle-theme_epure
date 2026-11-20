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
 * Preview of the files of a course: the file of a resource opens in a dialog on the page of the course, with the
 * previous and next files of the page (buttons, and the arrow keys on the buttons of the dialog). A true modal dialog:
 * the focus stays in it, Escape closes it and the focus goes back to the link. Ctrl, Shift or middle click on a link
 * keep the behaviour of the browser. The data of the files comes from theme_epure\file_preview.
 *
 * @module     theme_epure/file_preview
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';
import Ajax from 'core/ajax';
import Pending from 'core/pending';
import Templates from 'core/templates';

/** @var {Object[]} The files of the page, in the order of the course. */
let files = [];

/** @var {Object} Strings of the dialog. */
let strings = {};

/** @var {HTMLDialogElement|null} The dialog, created at the first opening. */
let dialog = null;

/** @var {Promise<HTMLDialogElement>|null} The dialog being made, from its template. */
let dialogready = null;

/** @var {number} The file shown. */
let current = -1;

/** @var {HTMLElement|null} The link that opened the dialog, which gets the focus back. */
let opener = null;

/**
 * The module of the resource of a link, 0 when it is not a link to a resource.
 *
 * @param {HTMLAnchorElement} link Link.
 * @returns {number}
 */
const cmidOf = (link) => {
    try {
        const url = new URL(link.href, window.location.href);
        return url.pathname.endsWith('/mod/resource/view.php') ? Number(url.searchParams.get('id')) || 0 : 0;
    } catch (e) {
        return 0;
    }
};

/**
 * Text with its values ({$a->name}…).
 *
 * @param {string} text String.
 * @param {Object} values Values.
 * @returns {string}
 */
const fill = (text, values) => text.replace(/\{\$a->(\w+)\}/g, (match, key) => String(values[key] ?? ''));

/**
 * The text of a document, read by the theme: titles, paragraphs, lists, tables; a PDF page by page.
 *
 * @param {Object} file File.
 * @returns {HTMLElement}
 */
const documentText = (file) => {
    const element = document.createElement('div');
    element.tabIndex = 0;
    element.setAttribute('role', 'region');
    element.setAttribute('aria-label', file.name);
    element.setAttribute('aria-busy', 'true');
    element.className = 'epure-preview-media epure-preview-document';
    element.textContent = strings.loading;
    Ajax.call([{methodname: 'theme_epure_document_text', args: {cmid: file.cmid}}])[0]
        .then(({html}) => {
            if (html) {
                // Written by the template theme_epure/document_text, which escapes all the text of the document.
                element.innerHTML = html;
            } else {
                element.textContent = strings.unreadable;
            }
            element.removeAttribute('aria-busy');
            return html;
        })
        .catch(() => {
            element.textContent = strings.unreadable;
            element.removeAttribute('aria-busy');
        });
    return element;
};

/**
 * Shows a PDF as it is, or its text: the button that switches between them says what it shows next. The reading aloud
 * presses it to read the text (data-state « pdf »: the PDF is shown).
 *
 * @param {boolean} text Whether to show the text.
 */
const textVersion = (text) => {
    const file = files[current];
    const toggle = dialog.querySelector('[data-action="textversion"]');
    toggle.dataset.state = text ? 'text' : 'pdf';
    toggle.textContent = text ? strings.showpdf : strings.showtext;
    dialog.querySelector('.epure-preview-body').replaceChildren(text ? documentText(file) : viewer(file));
};

/**
 * The viewer of a file: the reader of PDF of the browser, an image, the players of video and sound, the text in the page,
 * the text of a document of an office suite.
 *
 * @param {Object} file File.
 * @returns {HTMLElement}
 */
const viewer = (file) => {
    let element;
    switch (file.kind) {
        case 'image':
            element = document.createElement('img');
            element.alt = file.name;
            break;
        case 'video':
        case 'audio':
            element = document.createElement(file.kind);
            element.controls = true;
            element.preload = 'metadata';
            element.setAttribute('aria-label', file.name);
            break;
        case 'text':
            // In the page, with the font, the size and the colours of the user; a region that scrolls with the keyboard.
            element = document.createElement('pre');
            element.tabIndex = 0;
            element.setAttribute('role', 'region');
            element.setAttribute('aria-label', file.name);
            element.className = 'epure-preview-media epure-preview-text';
            fetch(file.url, {credentials: 'same-origin'})
                .then((response) => (response.ok ? response.text() : Promise.reject(response.status)))
                .then((text) => {
                    element.textContent = text;
                    return text;
                })
                .catch(() => {
                    element.textContent = file.filename;
                });
            return element;
        case 'document':
            return documentText(file);
        default:
            // PDF, in the reader of the browser.
            element = document.createElement('iframe');
            element.title = file.name;
    }
    element.className = `epure-preview-media epure-preview-${file.kind}`;
    element.src = file.url;
    return element;
};

/**
 * Shows a file in the dialog, and announces it.
 *
 * @param {number} index File.
 * @param {boolean} announce Whether to announce it (when going from a file to another).
 */
const show = (index, announce) => {
    current = index;
    const file = files[index];
    const values = {name: file.name, index: index + 1, total: files.length};
    dialog.querySelector('.epure-preview-title').textContent = file.name;
    dialog.querySelector('.epure-preview-position').textContent = fill(strings.position, values);
    dialog.querySelector('.epure-preview-body').replaceChildren(viewer(file));
    // A PDF can be shown as text: for whoever reads it with difficulty, with a screen reader, or hears it read aloud.
    const toggle = dialog.querySelector('[data-action="textversion"]');
    toggle.hidden = file.kind !== 'pdf';
    toggle.dataset.state = 'pdf';
    toggle.textContent = strings.showtext;
    dialog.querySelector('[data-action="newtab"]').href = file.url;
    dialog.querySelector('[data-action="download"]').href = file.download;
    [['previous', index - 1], ['next', index + 1]].forEach(([action, other]) => {
        const button = dialog.querySelector(`[data-action="${action}"]`);
        button.hidden = !files[other];
        button.querySelector('.epure-preview-other').textContent = files[other]?.name || '';
    });
    dialog.querySelector('.epure-preview-status').textContent = announce ? fill(strings.opened, values) : '';
    // The view counts, as on the page of the resource; a failure leaves the preview as it is.
    Ajax.call([{methodname: 'theme_epure_view_resource', args: {cmid: file.cmid}}])[0].catch(() => null);
};

/**
 * Goes to the previous or the next file; the focus stays on the button, or goes to the other one at the end.
 *
 * @param {number} step -1 or 1.
 */
const go = (step) => {
    if (!files[current + step]) {
        return;
    }
    show(current + step, true);
    const here = dialog.querySelector(`[data-action="${step < 0 ? 'previous' : 'next'}"]`);
    const other = dialog.querySelector(`[data-action="${step < 0 ? 'next' : 'previous'}"]`);
    if (here.hidden) {
        (other.hidden ? dialog.querySelector('[data-action="close"]') : other).focus();
    }
};

/**
 * The dialog, created once, from its template.
 *
 * @returns {Promise<HTMLDialogElement>}
 */
const createDialog = async() => {
    const {html, js} = await Templates.renderForPromise('theme_epure/file_preview', {});
    const element = Templates.appendNodeContents(document.body, html, js).find((node) => node.nodeName === 'DIALOG');
    element.addEventListener('click', (e) => {
        const action = e.target.closest('[data-action]')?.dataset.action;
        if (action === 'close') {
            element.close();
        } else if (action === 'textversion') {
            textVersion(e.target.closest('[data-action]').dataset.state === 'pdf');
        } else if (action === 'previous' || action === 'next') {
            go(action === 'previous' ? -1 : 1);
        } else if (e.target === element) {
            // A click on the backdrop.
            element.close();
        }
    });
    element.addEventListener('keydown', (e) => {
        // The arrows go from a file to another from the buttons and links of the dialog, not in a player.
        if ((e.key === 'ArrowLeft' || e.key === 'ArrowRight') && e.target.closest('button, a, dialog') === e.target
                && !e.altKey && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            go(e.key === 'ArrowLeft' ? -1 : 1);
        }
    });
    element.addEventListener('close', () => {
        // Stops a video or a sound, and gives the focus back to the link.
        element.querySelector('.epure-preview-body').replaceChildren();
        document.documentElement.classList.remove('epure-preview-open');
        if (opener && document.contains(opener)) {
            opener.focus();
        }
    });
    return element;
};

/**
 * Opens a file in the dialog.
 *
 * @param {number} index File.
 * @param {HTMLElement} link The link that opened it.
 */
const open = async(index, link) => {
    const pending = new Pending('theme_epure/file_preview:open');
    opener = link;
    dialogready = dialogready || createDialog();
    dialog = await dialogready;
    show(index, false);
    if (!dialog.open) {
        document.documentElement.classList.add('epure-preview-open');
        dialog.showModal();
        dialog.querySelector('[data-action="close"]').focus();
    }
    pending.resolve();
};

/**
 * Starts the preview on the links to the resources of the page.
 */
export const init = async() => {
    const data = document.getElementById('epure-file-preview-data');
    if (!data || typeof HTMLDialogElement === 'undefined' || document.documentElement.dataset.epurePreview) {
        return;
    }
    document.documentElement.dataset.epurePreview = '1';
    files = JSON.parse(data.textContent).files || [];
    const keys = ['loading', 'opened', 'position', 'showpdf', 'showtext', 'unreadable'];
    const values = await getStrings(keys.map((key) => ({key: `preview_${key}`, component: 'theme_epure'})));
    keys.forEach((key, i) => {
        strings[key] = values[i];
    });
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.shiftKey || e.metaKey || e.altKey) {
            return;
        }
        const link = e.target.closest('#region-main a[href*="/mod/resource/view.php"]');
        const cmid = link ? cmidOf(link) : 0;
        const index = cmid ? files.findIndex((file) => file.cmid === cmid) : -1;
        if (index < 0) {
            return;
        }
        e.preventDefault();
        open(index, link);
    });
};
