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
 * The text of the document of a resource, under the document shown in its page (a PDF embedded by the browser, which
 * the page cannot read): a button shows it, read by the theme (theme_epure\document_text), for whoever reads with a
 * screen reader, in the font and the colours of « Aa », or hears it read aloud (the reading aloud presses the button).
 *
 * @module     theme_epure/resource_text
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {getStrings} from 'core/str';
import Templates from 'core/templates';

/**
 * Adds the button and the place of the text under the document of the page.
 *
 * @param {number} cmid Module of the resource.
 */
export const init = async(cmid) => {
    const embedded = document.querySelector('#region-main .resourcecontent');
    if (!embedded || document.querySelector('.epure-resource-text')) {
        return;
    }
    const [show, hide, loading, unreadable] = await getStrings([
        {key: 'resourcetext_show', component: 'theme_epure'},
        {key: 'resourcetext_hide', component: 'theme_epure'},
        {key: 'preview_loading', component: 'theme_epure'},
        {key: 'preview_unreadable', component: 'theme_epure'},
    ]);
    const {html, js} = await Templates.renderForPromise('theme_epure/resource_text', {});
    const box = Templates.appendNodeContents(embedded.parentElement, html, js).find((node) => node.nodeType === Node.ELEMENT_NODE);
    embedded.after(box);
    const button = box.querySelector('button');
    const region = box.querySelector('[role="region"]');
    let loaded = false;
    button.addEventListener('click', () => {
        const open = button.dataset.state === 'pdf';
        button.dataset.state = open ? 'text' : 'pdf';
        button.textContent = open ? hide : show;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        region.hidden = !open;
        if (!open || loaded) {
            return;
        }
        loaded = true;
        region.setAttribute('aria-busy', 'true');
        region.textContent = loading;
        Ajax.call([{methodname: 'theme_epure_document_text', args: {cmid}}])[0]
            .then(({html: text}) => {
                // Written by the template theme_epure/document_text, which escapes all the text of the document.
                if (text) {
                    region.innerHTML = text;
                } else {
                    region.textContent = unreadable;
                }
                region.removeAttribute('aria-busy');
                return text;
            })
            .catch(() => {
                region.textContent = unreadable;
                region.removeAttribute('aria-busy');
            });
    });
};
