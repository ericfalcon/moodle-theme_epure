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
 * Progress of each section of a course, at the end of its title: « 3/5 », with a tick once all is done; and the state
 * of each activity for the learner (late, due soon, in progress, passed…), under its name and in the course index.
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

/** Completion of an activity in the course index (data-value), and the state it gives. */
const COMPLETION_STATES = {1: 'done', 2: 'passed', 3: 'failed'};

/**
 * Element of a state: icon and text, as the template theme_epure/activity_state.
 *
 * @param {{state: string, icon: string, text: string}} state State.
 * @returns {HTMLElement}
 */
const stateElement = (state) => {
    const element = document.createElement('span');
    element.className = `epure-state epure-state-${state.state}`;
    const icon = document.createElement('i');
    icon.className = `icon ${state.icon} fa-fw`;
    icon.setAttribute('aria-hidden', 'true');
    element.append(icon, document.createTextNode(state.text));
    return element;
};

/**
 * Shows the state of an activity on the course page: under its name when it tells more than Moodle's completion
 * button, and as the icon of its line in the course index.
 *
 * @param {number} cmid Activity.
 * @param {{state: string, icon: string, text: string, label: string, notable: boolean}} state State.
 */
const showState = (cmid, state) => {
    document.querySelectorAll(`[data-for="cmitem"][data-id="${cmid}"]`).forEach((item) => {
        item.dataset.epureState = state.state;
        item.querySelector(':scope .epure-state')?.remove();
        const name = item.querySelector('.activity-name-area, .activity-instance');
        if (state.notable && name) {
            name.append(stateElement(state));
        }
    });
    showIndexState(cmid, state);
};

/**
 * Icon of the state of an activity in the course index, whose lines Moodle renders again when they change.
 *
 * @param {number} cmid Activity.
 * @param {{state: string, icon: string, label: string}} state State.
 */
const showIndexState = (cmid, state) => {
    const item = document.querySelector(`#courseindex [data-for="cm"][data-id="${cmid}"]`);
    const place = item?.querySelector('[data-for="cm_completion"]');
    if (!place) {
        return;
    }
    item.dataset.epureState = state.state;
    const current = place.querySelector('.icon');
    if (current?.dataset.epureState === state.state) {
        return;
    }
    const icon = document.createElement('i');
    icon.className = `icon ${state.icon} fa-fw`;
    icon.dataset.epureState = state.state;
    icon.setAttribute('role', 'img');
    icon.title = state.label;
    icon.setAttribute('aria-label', state.label);
    place.replaceChildren(icon);
};

/**
 * Starts the progress of the sections and the states of the activities, from the data in the page (script
 * epure-course-page-data): sections, the progress of each section with completion; states, the state of each
 * activity (cmid, state, icon, label, text, notable); all, all the states without dates, for an activity whose
 * completion changes on the page.
 */
export const init = () => {
    const data = document.getElementById('epure-course-page-data');
    if (!data) {
        return;
    }
    const {sections = [], states = [], all = {}} = JSON.parse(data.textContent);
    sections.forEach(addBadge);
    const initial = new Map(states.map((state) => [state.cmid, state]));
    const current = new Map(initial);
    initial.forEach((state, cmid) => showState(cmid, state));

    const index = document.getElementById('courseindex');
    if (!index || !current.size) {
        return;
    }
    // The index renders its lines again when they change, and tells the completion of each activity: a completion
    // marked on the page changes the state; unmarked, the activity gets back its state, or « to do ».
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes') {
                const cmid = Number(mutation.target.closest('[data-for="cm"]')?.dataset.id);
                if (!current.has(cmid)) {
                    return;
                }
                const completed = COMPLETION_STATES[mutation.target.dataset.value];
                const before = initial.get(cmid);
                const after = completed ? all[completed]
                    : (Object.values(COMPLETION_STATES).includes(before.state) ? all.todo : before);
                if (after && after.state !== current.get(cmid).state) {
                    current.set(cmid, after);
                    showState(cmid, after);
                }
                return;
            }
            const items = new Set();
            [mutation.target, ...mutation.addedNodes].forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                const item = node.closest('[data-for="cm"]');
                if (item) {
                    items.add(item);
                }
                node.querySelectorAll('[data-for="cm"]').forEach((child) => items.add(child));
            });
            items.forEach((item) => {
                const cmid = Number(item.dataset.id);
                if (current.has(cmid)) {
                    showIndexState(cmid, current.get(cmid));
                }
            });
        });
    }).observe(index, {childList: true, subtree: true, attributes: true, attributeFilter: ['data-value']});
};
