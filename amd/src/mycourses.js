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
 * Search and status filters of the « My courses » page of Épure.
 *
 * @module     theme_epure/mycourses
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

/**
 * Text in lower case and without accents, so that « secu » finds « Sécurité ».
 *
 * @param {string} text Text.
 * @returns {string}
 */
const normalise = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

/**
 * Shows the courses matching the search and the selected status, and announces how many are shown.
 *
 * @param {HTMLElement} region The page.
 */
const update = async(region) => {
    const query = normalise(region.querySelector('[data-action="search"]').value);
    const filter = region.querySelector('[data-filter][aria-pressed="true"]')?.dataset.filter || 'all';
    let shown = 0;
    region.querySelectorAll('section[data-role]').forEach((section) => {
        let count = 0;
        section.querySelectorAll('[data-status]').forEach((item) => {
            const visible = (filter === 'all' || item.dataset.status === filter)
                && (query === '' || normalise(item.dataset.name).includes(query));
            item.hidden = !visible;
            count += visible ? 1 : 0;
        });
        const counter = section.querySelector('[data-region="count"]');
        if (counter) {
            counter.textContent = count;
        }
        section.querySelector('[data-region="empty"]').hidden = count > 0;
        shown += count;
    });
    region.querySelector('[data-region="status"]').textContent = await getString('mycourses_shown', 'theme_epure', shown);
};

/**
 * Starts the search and the filters.
 */
export const init = () => {
    const region = document.querySelector('[data-region="epure-mycourses"]');
    const search = region?.querySelector('[data-action="search"]');
    if (!search) {
        return;
    }
    search.addEventListener('input', () => update(region));
    region.querySelectorAll('[data-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            region.querySelectorAll('[data-filter]').forEach((other) => {
                other.setAttribute('aria-pressed', String(other === button));
            });
            update(region);
        });
    });
};
