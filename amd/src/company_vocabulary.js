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
    const form = document.querySelector('form.mform');
    if (!region || !form) {
        return;
    }
    const appearance = form.querySelector('#id_appearancecontainer');
    const buttons = form.querySelector('#fgroup_id_buttonar, [data-fieldtype="group"]:last-of-type');
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
