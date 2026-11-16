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
 * The models of the login page by sector in the tab of the login page: a model fills the tagline and the text left
 * empty, or still those of a model (what the administrator wrote stays), and ticks or unticks the catalogue. Said in a
 * status; saved with the page.
 *
 * @module     theme_epure/login_model
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = (selector) => {
    const select = document.querySelector(selector);
    const tagline = document.getElementById('id_s_theme_epure_logintagline');
    const text = document.getElementById('id_s_theme_epure_logintext');
    if (!select || !tagline || !text) {
        return;
    }
    const status = select.parentElement.querySelector('[data-region="epure-loginmodel-status"]');
    const options = [...select.options].filter((option) => option.value);
    // The texts of the models: a field that holds one of them was not written by the administrator.
    const given = (field, key) => field.value.trim() === '' || options.some((option) => option.dataset[key] === field.value);
    select.addEventListener('change', () => {
        const option = select.selectedOptions[0];
        if (!option || !option.value) {
            status.textContent = '';
            return;
        }
        [[tagline, 'tagline'], [text, 'text']].forEach(([field, key]) => {
            if (given(field, key)) {
                field.value = option.dataset[key];
                field.dispatchEvent(new Event('change', {bubbles: true}));
            }
        });
        const catalogue = document.getElementById('id_s_theme_epure_catalogue');
        if (catalogue) {
            catalogue.checked = option.dataset.catalogue === '1';
            catalogue.dispatchEvent(new Event('change', {bubbles: true}));
        }
        status.textContent = status.dataset.done;
    });
};
