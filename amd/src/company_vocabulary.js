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
 * Moves the vocabulary fields into IOMAD's company form, and shows the custom word fields when needed.
 *
 * IOMAD's form has no extension point: the fields are rendered at the end of the page, then moved
 * into its Appearance section (or before its buttons), so that they are posted with the company.
 *
 * @module     theme_epure/company_vocabulary
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {init as initColours, setLogo} from 'theme_epure/logo_colours';

/**
 * Shows the custom word fields of a choice when « Other word » is selected.
 *
 * @param {HTMLSelectElement} select The choice.
 */
const toggleCustom = (select) => {
    const custom = document.querySelector(`[data-epure-vocab-custom="${select.id}"]`);
    if (custom) {
        custom.hidden = select.value !== 'custom';
    }
};

/**
 * Moves the fields into the company form.
 */
export const init = () => {
    const region = document.querySelector('[data-region="epure-company-vocabulary"]');
    // The company form, and not another form of the page (IOMAD can show a company selector above it).
    const anchor = document.querySelector('#id_appearancecontainer, #id_appearance, [name="headingcolor"], [name="shortname"]');
    const form = anchor?.closest('form') || document.querySelector('form.mform[action*="company_edit_form"]');
    if (!region || !form) {
        return;
    }
    const appearance = form.querySelector('#id_appearancecontainer')
        || form.querySelector('#id_appearance')?.querySelector('.fcontainer');
    const buttons = form.querySelector('#fgroup_id_buttonar');
    if (appearance) {
        appearance.append(region);
    } else if (buttons) {
        buttons.before(region);
    } else {
        form.append(region);
    }
    region.hidden = false;

    region.querySelectorAll('[data-epure-vocab-choice]').forEach((select) => {
        select.addEventListener('change', () => toggleCustom(select));
        toggleCustom(select);
    });
};

/**
 * Moves the colours of the logo under the « Heading colour » field, and follows the logo uploaded in the form.
 *
 * @param {string} selector Selector of the logo colours region.
 */
export const initLogoColours = (selector) => {
    const region = document.querySelector(selector);
    const field = document.querySelector('[name="headingcolor"]');
    const row = field?.closest('.fitem, .form-group');
    if (!region || !row) {
        // Without the colour field (theme not set to Épure for the company), nothing to propose.
        region?.remove();
        return;
    }
    region.dataset.input = field.id;
    const element = row.querySelector('.felement');
    if (element) {
        element.append(region);
    } else {
        row.after(region);
    }
    initColours(selector);

    // A logo uploaded in the form is not saved yet: its preview in the file manager gives its colours.
    const manager = document.querySelector('[name="companylogo"]')?.closest('.fitem, .form-group');
    if (!manager) {
        return;
    }
    const follow = () => {
        const preview = manager.querySelector('.fp-content img[src*="draftfile.php"], .fp-content img[src*="pluginfile.php"]');
        if (preview) {
            const url = new URL(preview.src, window.location.href);
            url.searchParams.delete('preview');
            url.searchParams.delete('oid');
            setLogo(selector, url.toString());
        }
    };
    // The file manager first shows a placeholder, then sets the preview address on the same image.
    new MutationObserver(follow).observe(manager, {childList: true, subtree: true, attributes: true, attributeFilter: ['src']});
    follow();
};
