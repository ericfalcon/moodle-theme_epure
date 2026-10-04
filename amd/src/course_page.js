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
 * Progress of each section of a course, at the end of its title: « 3/5 », with a tick once all is done.
 *
 * @module     theme_epure/course_page
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the progress to the title of a section.
 *
 * @param {{id: number, done: number, total: number, label: string}} section Progress of the section.
 */
const addBadge = (section) => {
    const title = document.querySelector(`[data-for="section"][data-id="${section.id}"] [data-for="section_title"]`);
    if (!title || title.querySelector('.epure-section-progress')) {
        return;
    }
    const badge = document.createElement('span');
    const complete = section.done === section.total;
    badge.className = 'epure-section-progress' + (complete ? ' epure-section-progress-complete' : '');
    badge.title = section.label;
    badge.innerHTML = (complete ? '<i class="fa fa-check" aria-hidden="true"></i>' : '') +
        `<span aria-hidden="true">${section.done}/${section.total}</span>`;
    const label = document.createElement('span');
    label.className = 'sr-only visually-hidden';
    label.textContent = section.label;
    badge.append(label);
    title.append(badge);
};

/**
 * Starts the progress of the sections.
 *
 * @param {Array} sections Progress of each section with completion.
 */
export const init = (sections) => {
    sections.forEach(addBadge);
};
