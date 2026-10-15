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
 * Progress of each section of a course, as a bar and « 3/5 » at the end of its title and under its name in the course
 * index, with a tick once all is done; the state of each activity for the learner (late, due soon, in progress,
 * passed…), under its name and in the course index; and the activity to continue, with its section, highlighted.
 *
 * @module     theme_epure/course_page
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Bar of the progress of a section.
 *
 * @param {{done: number, total: number}} section Progress of the section.
 * @returns {HTMLElement}
 */
const progressBar = (section) => {
    const bar = document.createElement('span');
    bar.className = 'epure-section-bar';
    bar.setAttribute('aria-hidden', 'true');
    const fill = document.createElement('span');
    fill.style.width = `${Math.round(100 * section.done / section.total)}%`;
    bar.append(fill);
    return bar;
};

/**
 * Adds the progress to the title of a section on the course page.
 *
 * @param {{id: number, done: number, total: number, label: string}} section Progress of the section.
 */
const addBadge = (section) => {
    const title = document.querySelector(`#region-main [data-for="section"][data-id="${section.id}"] [data-for="section_title"]`)
        || document.querySelector(`[data-for="section"][data-id="${section.id}"] [data-for="section_title"]`);
    if (!title || title.querySelector('.epure-section-progress')) {
        return;
    }
    const badge = document.createElement('span');
    const complete = section.done === section.total;
    badge.className = 'epure-section-progress' + (complete ? ' epure-section-progress-complete' : '');
    badge.title = section.label;
    if (complete) {
        badge.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';
    } else {
        badge.append(progressBar(section));
    }
    const count = document.createElement('span');
    count.setAttribute('aria-hidden', 'true');
    count.textContent = `${section.done}/${section.total}`;
    const label = document.createElement('span');
    label.className = 'sr-only visually-hidden';
    label.textContent = section.label;
    badge.append(count, label);
    title.append(badge);
};

/**
 * Adds the bar of the progress under the name of a section in the course index, whose lines Moodle renders again.
 *
 * @param {{id: number, done: number, total: number, label: string}} section Progress of the section.
 */
const addIndexBar = (section) => {
    const item = document.querySelector(`#courseindex [data-for="section"][data-id="${section.id}"] [data-for="section_item"]`);
    if (!item || item.querySelector('.epure-section-bar')) {
        return;
    }
    const bar = progressBar(section);
    bar.classList.toggle('epure-section-bar-complete', section.done === section.total);
    bar.title = section.label;
    item.append(bar);
};

/** States of an activity still to do: the first one of the course is the activity to continue. */
const PENDING = ['late', 'soon', 'failed', 'progress', 'todo'];

/**
 * Highlights the activity to continue, the first one still to do in the order of the page, and its section (and
 * subsection), on the course page and in the course index.
 *
 * @param {Array} states States of the activities, in the order of the page.
 * @param {Map} current Current state of each activity.
 * @param {{activity: string, section: string}} labels Labels of the activity and of its section.
 */
const showCurrent = (states, current, labels) => {
    document.querySelectorAll('.epure-current-label').forEach((label) => label.remove());
    document.querySelectorAll('.epure-current, .epure-current-section').forEach((element) => {
        element.classList.remove('epure-current', 'epure-current-section');
    });
    const next = states.find((state) => PENDING.includes(current.get(state.cmid)?.state));
    if (!next) {
        return;
    }
    const label = (text) => {
        const element = document.createElement('span');
        element.className = 'epure-current-label';
        element.textContent = text;
        return element;
    };
    document.querySelectorAll(`[data-for="cmitem"][data-id="${next.cmid}"]`).forEach((item) => {
        item.classList.add('epure-current');
        item.querySelector('.activity-name-area, .activity-instance')?.prepend(label(labels.activity));
    });
    document.querySelector(`#courseindex [data-for="cm"][data-id="${next.cmid}"]`)?.classList.add('epure-current');
    (next.sections || []).forEach((id) => {
        document.querySelector(`#courseindex [data-for="section"][data-id="${id}"] [data-for="section_item"]`)
            ?.classList.add('epure-current-section');
        const header = document.querySelector(`#region-main [data-for="section"][data-id="${id}"] .course-section-header`)
            || document.querySelector(`#region-main [data-for="section"][data-id="${id}"] [data-for="section_title"]`);
        header?.append(label(labels.section));
    });
};

/** Completion of an activity in the course index (data-value), and the state it gives. */
const COMPLETION_STATES = {'1': 'done', '2': 'passed', '3': 'failed'};

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
    const {sections = [], states = [], all = {}, labels = {}} = JSON.parse(data.textContent);
    sections.forEach(addBadge);
    sections.forEach(addIndexBar);
    const initial = new Map(states.map((state) => [state.cmid, state]));
    const current = new Map(initial);
    initial.forEach((state, cmid) => showState(cmid, state));
    showCurrent(states, current, labels);

    const index = document.getElementById('courseindex');
    if (!index || (!current.size && !sections.length)) {
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
                // Unmarked, an activity that was completed when the page was shown is to do; another gets back its state.
                const reset = Object.values(COMPLETION_STATES).includes(before.state) ? all.todo : before;
                const after = completed ? all[completed] : reset;
                if (after && after.state !== current.get(cmid).state) {
                    current.set(cmid, after);
                    showState(cmid, after);
                    showCurrent(states, current, labels);
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
        // Lines rendered again lose the bars and the highlight: they are added again, once for all the changes.
        sections.forEach(addIndexBar);
        const next = states.find((state) => PENDING.includes(current.get(state.cmid)?.state));
        if (next && !index.querySelector(`[data-for="cm"][data-id="${next.cmid}"].epure-current`)) {
            showCurrent(states, current, labels);
        }
    }).observe(index, {childList: true, subtree: true, attributes: true, attributeFilter: ['data-value']});
};
