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
 * Course page in edit mode, for its teachers: beside the name of each activity that holds points of accessibility to
 * fix or to confirm, a link « 2 points of accessibility » to the guide « Accessibility of the course », at this
 * activity. The course editor renders an activity again when it changes: the link comes back.
 *
 * @module     theme_epure/course_accessibility
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the link of an activity beside its name, once.
 *
 * @param {{cmid: number, text: string, url: string}} cm Points of the activity.
 */
const addPill = (cm) => {
    document.querySelectorAll(`#region-main [data-for="cmitem"][data-id="${cm.cmid}"]`).forEach((item) => {
        const name = item.querySelector('.activity-name-area, .activity-instance');
        if (!name || name.querySelector('.epure-a11y-pill')) {
            return;
        }
        const link = document.createElement('a');
        link.className = 'epure-a11y-pill';
        link.href = cm.url;
        const icon = document.createElement('i');
        icon.className = 'fa fa-universal-access';
        icon.setAttribute('aria-hidden', 'true');
        link.append(icon, document.createTextNode(cm.text));
        name.append(link);
    });
};

/**
 * Starts the links, from the data in the page (script epure-course-accessibility-data).
 */
export const init = () => {
    const data = document.getElementById('epure-course-accessibility-data');
    if (!data) {
        return;
    }
    const {cms = []} = JSON.parse(data.textContent);
    const addAll = () => cms.forEach(addPill);
    addAll();
    const main = document.getElementById('region-main');
    if (main && cms.length) {
        let pending = false;
        new MutationObserver(() => {
            if (!pending) {
                pending = true;
                window.requestAnimationFrame(() => {
                    pending = false;
                    addAll();
                });
            }
        }).observe(main, {childList: true, subtree: true});
    }
};
