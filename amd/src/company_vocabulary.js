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
        // First in the Appearance section: the Épure settings of the company come before IOMAD's own.
        appearance.prepend(region);
    } else if (buttons) {
        buttons.before(region);
    } else {
        form.append(region);
    }
    region.hidden = false;
    initColour(region);
    hideIomadColours(form, region);

    region.querySelectorAll('[data-epure-vocab-choice]').forEach((select) => {
        select.addEventListener('change', () => toggleCustom(select));
        toggleCustom(select);
    });
};

/**
 * Hides IOMAD's colour fields while the company uses Épure, whose brand colour replaces them.
 *
 * IOMAD's heading, main and link colours only serve IOMAD's themes. They are shown again when
 * another theme is chosen for the company. A heading colour already saved is copied into the
 * Épure brand colour when that one is empty, so that the colour in use stays visible.
 *
 * @param {HTMLFormElement} form The company form.
 * @param {HTMLElement} region The Épure fields.
 */
const hideIomadColours = (form, region) => {
    const rows = ['headingcolor', 'maincolor', 'linkcolor']
        .map((name) => form.querySelector(`[name="${name}"]`)?.closest('.fitem, .form-group'))
        .filter((row) => row);
    const theme = form.querySelector('select[name="theme"], input[name="theme"]');
    if (!rows.length || !theme) {
        return;
    }
    const code = region.querySelector('#id_epure_brandcolor');
    const heading = form.querySelector('[name="headingcolor"]');
    if (code && heading && code.value.trim() === '' && /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.test(heading.value.trim())) {
        code.value = heading.value.trim();
        code.dispatchEvent(new Event('change', {bubbles: true}));
    }
    const update = () => {
        rows.forEach((row) => {
            row.hidden = theme.value === 'epure';
        });
    };
    theme.addEventListener('change', update);
    update();
};

/**
 * Keeps the colour picker and the colour code in step, and proposes the colours of the logo.
 *
 * @param {HTMLElement} region The Épure fields.
 */
const initColour = (region) => {
    const code = region.querySelector('#id_epure_brandcolor');
    const picker = region.querySelector('[data-action="pick-colour"]');
    if (!code || !picker) {
        return;
    }
    const valid = (value) => /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value.trim());
    const full = (value) => {
        let hex = value.trim().replace('#', '');
        if (hex.length === 3) {
            hex = hex.split('').map((c) => c + c).join('');
        }
        return '#' + hex.toUpperCase();
    };
    picker.addEventListener('input', () => {
        code.value = picker.value.toUpperCase();
        code.removeAttribute('aria-invalid');
    });
    // The colours of the logo and the eyedropper fill the code: the picker follows.
    const sync = () => {
        const value = code.value;
        if (value.trim() === '') {
            code.removeAttribute('aria-invalid');
        } else if (valid(value)) {
            code.removeAttribute('aria-invalid');
            picker.value = full(value).toLowerCase();
        } else {
            code.setAttribute('aria-invalid', 'true');
        }
    };
    code.addEventListener('input', sync);
    code.addEventListener('change', sync);

    initColours('#epure-company-logocolours');

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
            setLogo('#epure-company-logocolours', url.toString());
        }
    };
    // The file manager first shows a placeholder, then sets the preview address on the same image.
    new MutationObserver(follow).observe(manager, {childList: true, subtree: true, attributes: true, attributeFilter: ['src']});
    follow();
};
