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
 * Tables read as cards on phones: each cell named by its column (data-label), so that the style sheet shows each
 * row as a card, its first cell as the title, then the values with the names of their columns. The tables that
 * Moodle reloads in place (filters and pages of the participants) are named again.
 *
 * @module     theme_epure/table_cards
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const text = (element) => element.textContent.replace(/\s+/g, ' ').trim();

/**
 * The text of an element for the eyes: without the texts hidden for the screen readers.
 *
 * @param {HTMLElement} element Element.
 * @returns {string}
 */
const visibleText = (element) => {
    const clone = element.cloneNode(true);
    clone.querySelectorAll('.sr-only, .visually-hidden, .accesshide').forEach((hidden) => hidden.remove());
    return text(clone);
};

/**
 * Names of the columns of a table, by position, from the last row of its head (cells spanning several columns
 * repeat their name).
 *
 * @param {HTMLTableElement} table Table.
 * @returns {string[]}
 */
const columns = (table) => {
    const rows = table.tHead ? table.tHead.rows : [];
    const names = [];
    if (!rows.length) {
        return names;
    }
    Array.from(rows[rows.length - 1].cells).forEach((cell) => {
        // The name for the eyes: without the hidden texts of the sorting, nor the commands to hide the column.
        const clone = cell.cloneNode(true);
        clone.querySelectorAll('.commands').forEach((commands) => commands.remove());
        const name = visibleText(clone);
        for (let i = 0; i < (cell.colSpan || 1); i++) {
            names.push(name);
        }
    });
    return names;
};

/**
 * Names the cells of a table.
 *
 * @param {HTMLTableElement} table Table.
 */
const label = (table) => {
    const names = columns(table);
    table.classList.add('epure-cards');
    Array.from(table.tBodies).forEach((body) => {
        Array.from(body.rows).forEach((row) => {
            let position = 0;
            // The title of the card: the first cell with a text (after a checkbox to select the row, for instance).
            const title = Array.from(row.cells).find((cell) => visibleText(cell) !== '');
            if (title) {
                title.classList.add('epure-cards-title');
            }
            Array.from(row.cells).forEach((cell) => {
                if (names[position] && !cell.dataset.label) {
                    cell.dataset.label = names[position];
                }
                cell.classList.toggle('epure-cards-empty', text(cell) === '' && !cell.querySelector('input, img, svg, i'));
                position += cell.colSpan || 1;
            });
        });
    });
};

/**
 * Names the cells of the tables of the page, and again when Moodle reloads them.
 *
 * @param {string} selector Tables to show as cards.
 */
export const init = (selector) => {
    const run = () => document.querySelectorAll(selector).forEach(label);
    run();
    // Only the pages with such tables, or tables Moodle loads afterwards, are watched.
    if (!document.querySelector(selector + ', [data-region="core_table/dynamic"], .reportbuilder-report')) {
        return;
    }
    let pending = null;
    new MutationObserver(() => {
        clearTimeout(pending);
        pending = setTimeout(run, 50);
    }).observe(document.getElementById('region-main') || document.body, {childList: true, subtree: true});
};
