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
 * Moves the Épure fields into IOMAD's company form, puts its Appearance section in order, and shows
 * the custom word fields when needed.
 *
 * IOMAD's form has no extension point: the fields are rendered at the end of the page, then moved
 * into its Appearance section (or before its buttons), so that they are posted with the company.
 * IOMAD's own appearance fields are moved into the numbered sections of Épure, organised as the
 * settings of the theme: theme, brand identity (the logos, then the colours, which are proposed from
 * the logo), typography and display, pages and navigation, footer, vocabulary, then the advanced
 * settings (custom CSS and menu).
 *
 * @module     theme_epure/company_vocabulary
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {init as initColours, followManager} from 'theme_epure/logo_colours';

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
        // First in the Appearance section, which then holds IOMAD's fields in the sections of Épure.
        appearance.prepend(region);
    } else if (buttons) {
        buttons.before(region);
    } else {
        form.append(region);
    }
    region.hidden = false;
    arrange(form, region);
    initColour(region);
    hideIomadColours(form, region);

    region.querySelectorAll('[data-epure-vocab-choice]').forEach((select) => {
        select.addEventListener('change', () => toggleCustom(select));
        toggleCustom(select);
    });
};

/**
 * Moves IOMAD's appearance fields into the sections of Épure, and removes the sections left empty.
 *
 * @param {HTMLFormElement} form The company form.
 * @param {HTMLElement} region The Épure fields.
 */
const arrange = (form, region) => {
    const row = (name) => form.querySelector(`[name="${name}"]`)?.closest('.fitem, .form-group');
    const section = (key) => region.querySelector(`[data-epure-company-section="${key}"]`)
        || region.querySelector(`[data-epure-company-group="${key}"]`);
    const move = (key, names, before = null) => {
        const target = section(key);
        names.map(row).filter((item) => item && !region.contains(item)).forEach((item) => {
            target?.insertBefore(item, before);
        });
    };

    // Theme: IOMAD's choice, with its note « The options below may only work with the IOMAD themes ».
    const theme = row('theme');
    if (theme) {
        const note = theme.nextElementSibling?.tagName === 'P' ? theme.nextElementSibling : null;
        section('theme').append(theme);
        if (note) {
            note.classList.add('epure-company-iomadnote');
            section('theme').append(note);
        }
    }
    // Logos: IOMAD's logos and favicon before the logo for the brand-coloured header.
    const logos = section('logos');
    move('logos', ['companylogo', 'companylogocompact', 'companyfavicon'], logos.querySelector('.fitem'));
    // Colours: IOMAD's colours (hidden with Épure) after the brand colour and the header colour.
    const slot = region.querySelector('[data-epure-company-slot="iomadcolours"]');
    move('colours', ['headingcolor', 'maincolor', 'linkcolor'], slot);
    slot?.remove();
    // Advanced: the custom CSS and the custom menu, with the explanation of the menu that follows it.
    const menu = row('custommenuitems');
    const help = [];
    for (let node = menu?.nextSibling; node && !(node.matches?.('.fitem, .form-group, fieldset')); node = node.nextSibling) {
        if (region.contains(node)) {
            break;
        }
        help.push(node);
    }
    move('advanced', ['customcss', 'custommenuitems']);
    if (help.some((node) => node.textContent.trim() !== '')) {
        const box = document.createElement('div');
        box.className = 'epure-company-menuhelp small text-muted';
        help.forEach((node) => box.append(node));
        section('advanced').append(box);
    }

    region.querySelectorAll('[data-epure-company-section], [data-epure-company-group]').forEach((item) => {
        item.hidden = !item.querySelector('.fitem, .form-group');
    });
    // IOMAD's note does not concern Épure, which uses the logos and the colours of these sections.
    const select = form.querySelector('select[name="theme"]');
    const note = region.querySelector('.epure-company-iomadnote');
    if (select && note) {
        const update = () => {
            note.hidden = select.value === 'epure';
        };
        select.addEventListener('change', update);
        update();
    }
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
    followManager('#epure-company-logocolours', 'companylogo');
};
