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
 * the custom word fields when needed. When another theme is chosen for the company, IOMAD's form is
 * shown as it is without Épure.
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
 * Moves the fields into the company form, or restores IOMAD's own form when the company uses another theme.
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
    // IOMAD's Appearance section as IOMAD shows it, to restore it with another theme.
    const original = appearance ? [...appearance.childNodes] : [];
    const help = menuHelp(form);
    const buttons = form.querySelector('#fgroup_id_buttonar');
    if (appearance) {
        // First in the Appearance section, which then holds IOMAD's fields in the sections of Épure.
        appearance.prepend(region);
    } else if (buttons) {
        buttons.before(region);
    } else {
        form.append(region);
    }
    initColour(region);
    hideIomadColours(form, region);
    region.querySelectorAll('[data-epure-vocab-choice]').forEach((select) => {
        select.addEventListener('change', () => toggleCustom(select));
        toggleCustom(select);
    });

    // With another theme, nothing of Épure remains: IOMAD's form is shown as it is without Épure. The fields
    // of Épure stay in the form, hidden, so that the settings are kept for a return to Épure.
    const choice = form.querySelector('select[name="theme"], input[name="theme"]');
    const apply = () => {
        const epure = !choice || choice.value === 'epure';
        if (epure) {
            arrange(form, region, help);
        } else {
            original.forEach((node) => appearance?.append(node));
        }
        const note = region.querySelector('.epure-company-iomadnote') || form.querySelector('.epure-company-iomadnote');
        if (note) {
            note.hidden = epure;
        }
        region.hidden = !epure;
    };
    choice?.addEventListener('change', apply);
    apply();
};

/**
 * The explanation of IOMAD's custom menu, which follows its field.
 *
 * @param {HTMLFormElement} form The company form.
 * @return {Node[]}
 */
const menuHelp = (form) => {
    const menu = form.querySelector('[name="custommenuitems"]')?.closest('.fitem, .form-group');
    const help = [];
    for (let node = menu?.nextSibling; node && !(node.matches?.('.fitem, .form-group, fieldset')); node = node.nextSibling) {
        help.push(node);
    }
    return help.some((node) => node.textContent.trim() !== '') ? help : [];
};

/**
 * Moves IOMAD's appearance fields into the sections of Épure, and hides the sections left empty.
 *
 * @param {HTMLFormElement} form The company form.
 * @param {HTMLElement} region The Épure fields.
 * @param {Node[]} help The explanation of IOMAD's custom menu.
 */
const arrange = (form, region, help) => {
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
    if (theme && !region.contains(theme)) {
        const note = theme.nextElementSibling?.tagName === 'P' ? theme.nextElementSibling : null;
        section('theme').append(theme);
        if (note) {
            note.classList.add('epure-company-iomadnote');
            section('theme').append(note);
        }
    }
    // Logos: IOMAD's logos and favicon before the logo for the brand-coloured header.
    move('logos', ['companylogo', 'companylogocompact', 'companyfavicon'],
        section('logos').querySelector('[data-epure-company-row]'));
    // Colours: IOMAD's colours (hidden with Épure) after the brand colour and the header colour.
    move('colours', ['headingcolor', 'maincolor', 'linkcolor'], region.querySelector('[data-epure-company-slot="iomadcolours"]'));
    // Advanced: the custom CSS and the custom menu, with the explanation of the menu.
    move('advanced', ['customcss', 'custommenuitems']);
    if (help.length) {
        let box = region.querySelector('.epure-company-menuhelp');
        if (!box) {
            box = document.createElement('div');
            box.className = 'epure-company-menuhelp small text-muted';
            section('advanced').append(box);
        }
        help.forEach((node) => box.append(node));
    }

    region.querySelectorAll('[data-epure-company-section], [data-epure-company-group]').forEach((item) => {
        item.hidden = !item.querySelector('.fitem, .form-group');
    });
    number(region);
};

/**
 * Numbers the sections shown (browsers do not always update CSS counters when a section is hidden).
 *
 * @param {HTMLElement} region The Épure fields.
 */
const number = (region) => {
    let step = 0;
    region.querySelectorAll('[data-epure-company-section]').forEach((item) => {
        if (!item.hidden) {
            step++;
            item.querySelector('.epure-company-section-title').dataset.step = step;
        }
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
    followManager('#epure-company-logocolours', 'companylogo');
};
