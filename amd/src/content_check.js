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
 * Accessibility of the contents written by the teachers: the editors are read for the common mistakes (images without a
 * text alternative, images used as links without one, links without text or whose text does not say where they go
 * even with the sentence around them, tables without headers, levels of titles skipped, a long text without any
 * title), without false alerts on what is right: decorative images (alt=""), tables of layout, headers on several
 * rows, short links clear in their sentence.
 *
 * As it is written, a line under each editor tells what it finds, with a button that selects each element concerned
 * in the editor. When the form is saved, a window lists them, field by field, with « Fix », « Open the accessibility
 * checker » (the one of the editor TinyMCE) and « Save anyway »: nothing is ever blocked.
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

/** @var {string} Blocks that give a link its context: the sentence, the item of a list, the cell, the caption. */
const BLOCKS = 'p, li, td, th, dd, dt, figcaption, blockquote, caption, h1, h2, h3, h4, h5, h6';

const text = (element) => element.textContent.replace(/\s+/g, ' ').trim();

/** @var {string[]} Mistakes, in their order in the window and in the line under the editor. */
const KEYS = ['images', 'imagelinks', 'links', 'tables', 'contrast', 'headings', 'noheadings'];

/** @var {Object<string, number[]>} The named colours most written, as red, green and blue (as theme_epure\content_colours). */
const NAMED = {
    black: [0, 0, 0], white: [255, 255, 255], red: [255, 0, 0], green: [0, 128, 0], blue: [0, 0, 255],
    yellow: [255, 255, 0], orange: [255, 165, 0], gray: [128, 128, 128], grey: [128, 128, 128], silver: [192, 192, 192],
    lightgray: [211, 211, 211], lightgrey: [211, 211, 211], darkgray: [169, 169, 169], darkgrey: [169, 169, 169],
    purple: [128, 0, 128], navy: [0, 0, 128], maroon: [128, 0, 0], lime: [0, 255, 0], aqua: [0, 255, 255],
    cyan: [0, 255, 255], fuchsia: [255, 0, 255], magenta: [255, 0, 255], teal: [0, 128, 128], olive: [128, 128, 0],
    pink: [255, 192, 203], gold: [255, 215, 0], brown: [165, 42, 42], beige: [245, 245, 220], ivory: [255, 255, 240],
    lightyellow: [255, 255, 224], lightblue: [173, 216, 230], lightgreen: [144, 238, 144], skyblue: [135, 206, 235],
    darkblue: [0, 0, 139], darkred: [139, 0, 0], darkgreen: [0, 100, 0], violet: [238, 130, 238],
};

/**
 * A colour written in a style, as red, green and blue: none for a transparent colour or what is not a colour.
 *
 * @param {string} value Value (« rgb(224, 62, 45) », « #e03e2d », « yellow »).
 * @returns {number[]|null}
 */
const colour = (value) => {
    value = (value || '').trim().toLowerCase();
    let match = value.match(/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)(?:[\s,/]+([\d.]+%?))?/);
    if (match) {
        const alpha = match[4] === undefined ? 1 : parseFloat(match[4]) / (match[4].endsWith('%') ? 100 : 1);
        return alpha < 1 ? null : [Number(match[1]), Number(match[2]), Number(match[3])];
    }
    match = value.match(/^#([0-9a-f]{6}|[0-9a-f]{3})$/);
    if (match) {
        const hex = match[1].length === 3 ? match[1].replace(/./g, '$&$&') : match[1];
        return [0, 2, 4].map((i) => parseInt(hex.substring(i, i + 2), 16));
    }
    return NAMED[value] || null;
};

/**
 * Contrast of two colours (WCAG).
 *
 * @param {number[]} a Colour.
 * @param {number[]} b Colour.
 * @returns {number}
 */
const contrast = (a, b) => {
    const luminance = (rgb) => rgb.map((v) => {
        v /= 255;
        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
    }).reduce((sum, v, i) => sum + v * [0.2126, 0.7152, 0.0722][i], 0);
    const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (l1 + 0.05) / (l2 + 0.05);
};

/**
 * The elements whose colour of text or of background makes a text not stand out enough from its background
 * (WCAG 1.4.3): 4.5:1, 3:1 for a large text. Around them, the dark text of the theme on white.
 *
 * @param {Element} root Content.
 * @returns {Element[]}
 */
const lowContrast = (root) => {
    const found = new Set();
    const walker = root.ownerDocument.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        if (!node.nodeValue.trim()) {
            continue;
        }
        let fg = null;
        let bg = null;
        let owner = null;
        let large = false;
        for (let element = node.parentElement; element && element !== root.parentElement; element = element.parentElement) {
            if (!fg) {
                fg = colour(element.style.color || (element.tagName === 'FONT' ? element.getAttribute('color') : ''));
                owner = owner || (fg ? element : null);
            }
            if (!bg) {
                bg = colour(element.style.backgroundColor || element.getAttribute('bgcolor'));
                owner = owner || (bg ? element : null);
            }
            const size = element.style.fontSize.match(/^([\d.]+)(px|pt)$/);
            const px = size ? parseFloat(size[1]) * (size[2] === 'pt' ? 4 / 3 : 1) : 0;
            const bold = ['STRONG', 'B'].includes(element.tagName) || /^(bold|[6-9]00)$/.test(element.style.fontWeight);
            large = large || ['H1', 'H2', 'H3'].includes(element.tagName) || px >= 24 || (bold && px >= 18.66);
            if (element === root) {
                break;
            }
        }
        if (owner && contrast(fg || [17, 20, 24], bg || [255, 255, 255]) < (large ? 3 : 4.5)) {
            found.add(owner);
        }
    }
    return [...found];
};

/** @var {number} Words from which a text without any title is hard to go through with a screen reader. */
const LONG_TEXT = 400;

/**
 * The mistakes in a content, each with the elements concerned: images without a text alternative, links made of
 * images without a text alternative, links without text or whose short text says nothing, tables without headers,
 * levels of titles skipped, a long text without any title.
 *
 * @param {Element} root Content.
 * @returns {Object<string, Element[]>} Elements by mistake.
 */
export const problems = (root) => {
    const found = Object.fromEntries(KEYS.map((key) => [key, []]));
    // A link made only of images without a text alternative: a screen reader says « link », without where it leads.
    const imageLink = (link) => link.querySelector('img') && text(link) === '' && !link.getAttribute('aria-label')
        && !link.querySelector('img[alt]:not([alt=""])');
    root.querySelectorAll('img').forEach((img) => {
        // Only an image without alt: alt="" marks a decorative one (WCAG H67), with or without role="presentation".
        const link = img.closest('a[href]');
        if (!img.hasAttribute('alt') && !(link && imageLink(link))) {
            found.images.push(img);
        }
    });
    root.querySelectorAll('table').forEach((table) => {
        // A table of layout (role="presentation" or "none") has no header to give. Headers on several rows, or in the
        // first column, are headers.
        if (!table.querySelector('th') && !['presentation', 'none'].includes(table.getAttribute('role'))) {
            found.tables.push(table);
        }
    });
    root.querySelectorAll('a[href]').forEach((link) => {
        if (imageLink(link)) {
            found.imagelinks.push(link);
            return;
        }
        const label = (link.getAttribute('aria-label') || text(link)).toLowerCase().replace(/[.…:!]+$/, '');
        const image = link.querySelector('img[alt]:not([alt=""])');
        if (label === '' && !image) {
            found.links.push(link);
            return;
        }
        // A short text (« here ») is clear with the sentence, the item of the list or the cell around it (WCAG 2.4.4,
        // RGAA 6.1): only one alone in its block says nothing.
        const block = link.parentElement ? link.parentElement.closest(BLOCKS) : null;
        if (VAGUE_LINKS.includes(label) && (!block || text(block) === text(link))) {
            found.links.push(link);
        }
    });
    // A text whose colour does not stand out enough from its background (WCAG 1.4.3, RGAA 3.2).
    found.contrast = lowContrast(root);
    let previous = 0;
    const headings = root.querySelectorAll('h1, h2, h3, h4, h5, h6');
    headings.forEach((heading) => {
        const level = Number(heading.tagName.substring(1));
        if (previous && level > previous + 1) {
            found.headings.push(heading);
        }
        previous = level;
    });
    // A long text without any title (WCAG 1.3.1, RGAA 9.1): its sections cannot be reached one by one.
    if (!headings.length && text(root).split(' ').filter(Boolean).length >= LONG_TEXT) {
        found.noheadings.push(root.querySelector('p') || root);
    }
    return found;
};

/**
 * The mistakes in a content, counted.
 *
 * @param {string} html Content.
 * @returns {Object<string, number>} Counts by mistake.
 */
export const check = (html) => {
    const found = problems(new DOMParser().parseFromString(html, 'text/html').body);
    return Object.fromEntries(KEYS.map((key) => [key, found[key].length]));
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

/** @var {string|null} Address of the guide « Accessibility of the course », for its teachers. */
let guideUrl = null;

/**
 * The window listing the mistakes.
 *
 * @param {Object[]} problems Each with label, found and editor.
 * @returns {Promise<Object>} The modal.
 */
const showProblems = async(problems) => {
    const keys = KEYS;
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
    if (guideUrl) {
        const guide = document.createElement('p');
        guide.className = 'epure-contentcheck-guide';
        const link = document.createElement('a');
        link.href = guideUrl;
        link.target = '_blank';
        link.textContent = await getString('courseaccessibility_all', 'theme_epure');
        guide.append(link);
        body.append(guide);
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
        // Saved anyway: this submission goes through, the next one is checked again (the form may come back, refused
        // for another field, and be changed before it is saved again).
        if (confirmed) {
            confirmed = false;
            return;
        }
        if (!submitter || submitter.name === 'cancel' || submitter.hasAttribute('formnovalidate')
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

/** @var {number} Wait after the last key before the content is read again, in milliseconds. */
const LIVE_DELAY = 800;

/**
 * Under an editor, a line that tells as it is written what makes the content harder to use (« Accessibility: 2 images
 * used as links »), with a button « Show » that selects each element concerned in the editor, one after the other.
 * Empty, the editor has no line.
 *
 * @param {HTMLTextAreaElement} textarea Field of the editor.
 * @param {Object} strings Texts of the line.
 */
const liveLine = (textarea, strings) => {
    const place = textarea.closest('.felement') || textarea.parentElement;
    if (!place || place.querySelector('.epure-contentcheck-live')) {
        return;
    }
    const line = document.createElement('div');
    line.className = 'epure-contentcheck-live';
    line.hidden = true;
    const status = document.createElement('span');
    status.className = 'epure-contentcheck-live-text';
    status.setAttribute('role', 'status');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-link btn-sm epure-contentcheck-live-show';
    button.hidden = true;
    line.append(status, button);
    place.append(line);

    let nodes = [];
    let index = 0;
    let timer = null;
    const update = () => {
        const editor = textarea.id ? getInstanceForElementId(textarea.id) : null;
        const root = editor ? editor.getBody() : new DOMParser().parseFromString(textarea.value, 'text/html').body;
        if (!root) {
            return;
        }
        const found = problems(root);
        nodes = KEYS.flatMap((key) => found[key]);
        index = 0;
        const parts = KEYS.filter((key) => found[key].length)
            .map((key) => strings[key].replace('NUMBER', found[key].length));
        const empty = text(root) === '' && !root.querySelector('img, video, audio, iframe, table');
        const message = parts.length ? strings.problems.replace('LIST', parts.join(' · ')) : strings.none;
        // Read again only when it changes: the screen readers are not told the same line at each key.
        if (status.textContent !== message) {
            status.textContent = message;
        }
        line.hidden = empty;
        line.classList.toggle('epure-contentcheck-live-ok', !parts.length);
        button.hidden = !editor || !nodes.length;
        button.textContent = strings.show;
    };
    const later = () => {
        clearTimeout(timer);
        timer = setTimeout(update, LIVE_DELAY);
    };
    button.addEventListener('click', () => {
        const editor = getInstanceForElementId(textarea.id);
        if (!editor || !nodes.length) {
            return;
        }
        const node = nodes[index % nodes.length];
        index++;
        editor.focus();
        editor.selection.select(node);
        node.scrollIntoView({block: 'center'});
        button.textContent = strings.next.replace('NUMBER', index > nodes.length ? nodes.length : index)
            .replace('TOTAL', nodes.length);
    });
    // The field is read at once (a plain text area, or the editor not started yet); the editor TinyMCE starts after the
    // page: its content is followed once it is there.
    textarea.addEventListener('input', later);
    update();
    // Looked for as long as the page is open: a slow server or a long form can start it late. Meanwhile, and with the
    // plain text editor, the value of the field is followed too when a script changes it, without any event.
    let value = textarea.value;
    const attach = () => {
        const editor = textarea.id ? getInstanceForElementId(textarea.id) : null;
        if (editor) {
            editor.on('input change undo redo SetContent', later);
            update();
        } else if (document.contains(textarea)) {
            if (textarea.value !== value) {
                value = textarea.value;
                update();
            }
            setTimeout(attach, 500);
        }
    };
    attach();
};

/**
 * Starts the lines under the editors of a form.
 *
 * @param {HTMLFormElement} form Form.
 */
const watchEditors = async(form) => {
    const fields = Array.from(form.querySelectorAll('textarea'))
        .filter((textarea) => textarea.closest('[data-fieldtype="editor"]'));
    if (!fields.length) {
        return;
    }
    // The texts of the line, and the short ones of each mistake, as in the guide « Accessibility of the course ».
    const keys = ['none', 'problems', 'show', 'next', ...KEYS];
    const params = {problems: 'LIST', next: {index: 'NUMBER', total: 'TOTAL'}};
    const values = await getStrings(keys.map((key) => ({
        key: KEYS.includes(key) ? 'courseaccessibility_short_' + key : 'contentcheck_live_' + key,
        component: 'theme_epure',
        param: params[key] ?? 'NUMBER',
    })));
    const strings = Object.fromEntries(keys.map((key, i) => [key, values[i]]));
    fields.forEach((textarea) => liveLine(textarea, strings));
};

/**
 * Watches the forms of the page that have editors.
 *
 * @param {string|null} guide Address of the guide « Accessibility of the course », for its teachers.
 */
export const init = (guide = null) => {
    guideUrl = guide;
    document.querySelectorAll('form.mform').forEach((form) => {
        if (form.querySelector('textarea')) {
            watch(form);
            watchEditors(form);
        }
    });
};
