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
 * Column « Category » on IOMAD's page « Manage IOMAD course settings », after the column of the course.
 *
 * @module     theme_epure/iomad_courses
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the column to the table of the courses.
 *
 * @param {string} label Header of the column.
 */
export const init = (label) => {
    const source = document.getElementById('epure-iomad-course-categories');
    const table = document.querySelector('#iomad_courses_table, table.flexible');
    const sortlink = table?.querySelector('thead [data-sortby="coursename"], thead [data-column="coursename"]');
    if (!source || !sortlink) {
        return;
    }
    const {categories, courses} = JSON.parse(source.textContent);
    const header = sortlink.closest('th');
    const index = header.cellIndex;

    const th = document.createElement('th');
    th.className = 'header epure-iomad-category';
    th.scope = 'col';
    th.textContent = label;
    header.after(th);

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cell = row.cells[index];
        if (!cell || row.cells.length === 1) {
            return;
        }
        const td = document.createElement('td');
        td.className = 'cell epure-iomad-category';
        const link = cell.querySelector('a[href*="/course/view.php"]');
        const courseid = link ? new URL(link.href).searchParams.get('id') : null;
        td.textContent = categories[courses[courseid]] || '';
        cell.after(td);
    });
};
