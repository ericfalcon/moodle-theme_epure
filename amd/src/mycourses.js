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
 * Search and status filters of the « My courses » page of Épure; for the courses the user teaches, the choice
 * between cards and a table (kept in their preferences) and the sorting of the table by its columns.
 *
 * @module     theme_epure/mycourses
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import {setUserPreference} from 'core_user/repository';

/** @var {string} The preference of the view of the courses the user teaches. */
const VIEW_PREFERENCE = 'theme_epure_mycourses_view';

/**
 * Text in lower case and without accents, so that « secu » finds « Sécurité ».
 *
 * @param {string} text Text.
 * @returns {string}
 */
const normalise = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

/**
 * Shows the courses matching the search and the selected status, as cards and as rows of the table, and
 * announces how many are shown.
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
            // Each course is a card and, for the courses the user teaches, a row of the table too: counted once.
            count += visible && item.tagName === 'LI' ? 1 : 0;
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
 * Shows the courses the user teaches as cards or as a table, and keeps the choice.
 *
 * @param {HTMLElement} section The section of the courses the user teaches.
 * @param {string} view cards or table.
 */
const showView = (section, view) => {
    section.querySelectorAll('[data-view]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.view === view));
    });
    section.querySelector('[data-region="cards"]').hidden = view !== 'cards';
    section.querySelector('[data-region="table"]').hidden = view !== 'table';
    const download = section.querySelector('[data-region="download"]');
    if (download) {
        download.hidden = view !== 'table';
    }
    setUserPreference(VIEW_PREFERENCE, view).catch(() => null);
};

/**
 * Sorts the table by a column: ascending, then descending at the next press. The rows without a value (no
 * completion, no deadline) stay last.
 *
 * @param {HTMLElement} table The table.
 * @param {HTMLButtonElement} button The button of the column.
 * @param {HTMLElement} status The live region of the page.
 */
const sortBy = async(table, button, status) => {
    const header = button.closest('th');
    const ascending = header.getAttribute('aria-sort') !== 'ascending';
    table.querySelectorAll('thead th').forEach((th) => th.setAttribute('aria-sort', 'none'));
    header.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
    const key = 'sort' + button.dataset.sort.charAt(0).toUpperCase() + button.dataset.sort.slice(1);
    const numeric = button.dataset.numeric === '1';
    const value = (row) => (numeric ? Number(row.dataset[key]) : row.dataset[key]);
    // No completion (-1) or no deadline (the largest integer of PHP).
    const missing = (row) => numeric && (Number(row.dataset[key]) < 0 || Number(row.dataset[key]) >= 9e15);
    const body = table.querySelector('tbody');
    const rows = [...body.rows].sort((a, b) => {
        if (missing(a) !== missing(b)) {
            return missing(a) ? 1 : -1;
        }
        const order = numeric ? value(a) - value(b) : value(a).localeCompare(value(b));
        return ascending ? order : -order;
    });
    rows.forEach((row) => body.appendChild(row));
    status.textContent = await getString(ascending ? 'mycourses_sortedasc' : 'mycourses_sorteddesc', 'theme_epure',
        button.textContent.trim());
};

/**
 * Starts the search, the filters, the choice of the view and the sorting of the table.
 */
export const init = () => {
    const region = document.querySelector('[data-region="epure-mycourses"]');
    const search = region?.querySelector('[data-action="search"]');
    // Started once: with IOMAD, the page may ask for it twice, and a second listener would undo each sort.
    if (!search || region.dataset.epureStarted) {
        return;
    }
    region.dataset.epureStarted = '1';
    search.addEventListener('input', () => update(region));
    region.querySelectorAll('[data-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            region.querySelectorAll('[data-filter]').forEach((other) => {
                other.setAttribute('aria-pressed', String(other === button));
            });
            update(region);
        });
    });
    const status = region.querySelector('[data-region="status"]');
    region.querySelectorAll('[data-view]').forEach((button) => {
        button.addEventListener('click', () => showView(button.closest('section'), button.dataset.view));
    });
    region.querySelectorAll('.epure-mycourses-table').forEach((table) => {
        table.querySelectorAll('[data-sort]').forEach((button) => {
            button.addEventListener('click', () => sortBy(table, button, status));
        });
    });
};
