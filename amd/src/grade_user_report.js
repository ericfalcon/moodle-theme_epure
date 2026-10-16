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
 * User report of the gradebook: the course total in a card above the table, read from the table so that what Moodle
 * hides stays hidden; the comments of the teachers set apart; on phones, each grade as a card, its values named by
 * the headers of their columns.
 *
 * @module     theme_epure/grade_user_report
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Text of a cell, without its menus.
 *
 * @param {HTMLElement|null} cell Cell.
 * @returns {string}
 */
const text = (cell) => {
    if (!cell) {
        return '';
    }
    const copy = cell.cloneNode(true);
    copy.querySelectorAll('.action-menu, .sr-only, .visually-hidden').forEach((node) => node.remove());
    return copy.textContent.replace(/\s+/g, ' ').trim();
};

/**
 * Whether a value is empty for Moodle (« - », a space).
 *
 * @param {string} value Value.
 * @returns {boolean}
 */
const empty = (value) => value === '' || value === '-';

/**
 * The card of the course total, above the table.
 *
 * @param {HTMLTableElement} table Table of the report.
 * @param {Object} strings Strings.
 */
const totalCard = (table, strings) => {
    const total = table.querySelector('.courseitem')?.closest('tr');
    if (!total) {
        return;
    }
    const grade = text(total.querySelector('.column-grade'));
    if (empty(grade)) {
        return;
    }
    const range = text(total.querySelector('.column-range'));
    const max = range.split(/[–-]/).pop().trim();
    const percentage = parseFloat(text(total.querySelector('.column-percentage')).replace(',', '.'));
    const items = [...table.querySelectorAll('tbody tr')].filter((row) => row.querySelector('th .item') && row !== total);
    const graded = items.filter((row) => !empty(text(row.querySelector('.column-grade'))));

    const card = document.createElement('section');
    card.className = 'epure-grade-total';
    const label = document.createElement('p');
    label.className = 'epure-grade-total-label';
    label.textContent = strings.total;
    const value = document.createElement('p');
    value.className = 'epure-grade-total-value';
    value.textContent = grade;
    if (max && !isNaN(parseFloat(max))) {
        const of = document.createElement('span');
        of.className = 'epure-grade-total-max';
        of.textContent = ' / ' + max;
        value.append(of);
    }
    card.append(label, value);
    if (!isNaN(percentage)) {
        const bar = document.createElement('div');
        bar.className = 'progress epure-grade-total-bar';
        bar.setAttribute('role', 'progressbar');
        bar.setAttribute('aria-label', strings.total);
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');
        bar.setAttribute('aria-valuenow', String(Math.round(percentage)));
        const fill = document.createElement('div');
        fill.className = 'progress-bar';
        fill.style.width = Math.max(0, Math.min(100, percentage)) + '%';
        bar.append(fill);
        const percent = document.createElement('span');
        percent.className = 'epure-grade-total-percent';
        percent.textContent = text(total.querySelector('.column-percentage'));
        value.append(percent);
        card.append(bar);
    }
    if (items.length) {
        const count = document.createElement('p');
        count.className = 'epure-grade-total-count';
        count.textContent = strings.graded.replace('{graded}', graded.length).replace('{total}', items.length);
        card.append(count);
    }
    (table.closest('.table-responsive, .no-overflow') || table).before(card);
};

/**
 * Names the values of each row by their column, and marks the comments and the empty cells.
 *
 * @param {HTMLTableElement} table Table of the report.
 */
const labelCells = (table) => {
    const headers = {};
    table.querySelectorAll('thead th[id]').forEach((th) => {
        headers[th.id] = text(th);
    });
    table.classList.add('epure-grade-table');
    table.querySelectorAll('tbody td[headers]').forEach((cell) => {
        const column = cell.getAttribute('headers').split(' ').find((id) => headers[id]);
        if (column) {
            cell.dataset.label = headers[column];
        }
        if (empty(text(cell))) {
            cell.classList.add('epure-grade-empty');
        } else if (cell.classList.contains('column-feedback')) {
            cell.closest('tr').classList.add('epure-grade-hasfeedback');
        }
    });
};

export const init = (strings) => {
    document.querySelectorAll('table.user-grade').forEach((table) => {
        labelCells(table);
        totalCard(table, strings);
    });
};
