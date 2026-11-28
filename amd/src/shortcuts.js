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
 * Help of the keyboard shortcuts, and the keys of navigation once turned on in it (N and P: next and previous
 * activity; J and K: next and previous section of the course page; G then D, C, A or F: dashboard, « My courses »,
 * calendar, « To do »), off by default as keys of one letter get in the way of the voice dictation (WCAG 2.1.4).
 *
 * The help: the key « ? » (except while typing, or when another window is open), the link
 * « Keyboard shortcuts » of the footer and the button of the panel « Aa » open a window that lists them. On a Mac, the keys show
 * their symbols (⌘, ⌥). The key « ? » can be turned off in the window (WCAG 2.1.4): in the preferences of the user, or
 * in the browser for a visitor.
 *
 * @module     theme_epure/shortcuts
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {setUserPreference} from 'core_user/repository';

/** @var {string} Key of the browser storage of a visitor who turned the key « ? » off. */
const STORAGE = 'theme_epure_shortcuts';

/** @var {string} Key of the browser storage of a visitor who turned the keys of navigation on. */
const NAV_STORAGE = 'theme_epure_navkeys';

/** @var {Object} Pages reached with G then a letter, from the root of the site. */
const PAGES = {d: '/my/', c: '/my/courses.php', a: '/calendar/view.php?view=upcoming', f: '/theme/epure/todo.php'};

/**
 * Follows the link of the next or the previous activity of the page: Épure's, else that of the bar of Moodle at the
 * bottom of the page (« linear navigation », Moodle 5.3 and later), else that of Moodle's own navigation.
 *
 * @param {boolean} next Next, or previous.
 * @return {boolean} Whether there was one.
 */
const followActivity = (next) => {
    const link = document.querySelector(next
        ? '.epure-activity-nav-next[href], [data-for="course-linear-navigation-footer"] > a.course-linear-navigation-link,'
            + ' #next-activity-link'
        : '.epure-activity-nav-previous[href], .course-linear-navigation-footer-content > a.course-linear-navigation-link,'
            + ' #prev-activity-link');
    if (!link?.href) {
        return false;
    }
    window.location.href = link.href;
    return true;
};

/**
 * Moves the focus to the title of the next or the previous section of the course page, from the section that holds
 * the focus, or else from the top of the page.
 *
 * @param {boolean} next Next, or previous.
 * @return {boolean} Whether there was one.
 */
const moveSection = (next) => {
    // The heading of each section, once (its wrapper is marked as its title too).
    const titles = [...document.querySelectorAll('#region-main [data-for="section"]')]
        .map((section) => section.querySelector(':is(h2, h3, h4)[data-for="section_title"]')
            || section.querySelector('[data-for="section_title"]'))
        .filter((title, i, all) => title?.offsetParent && all.indexOf(title) === i);
    if (!titles.length) {
        return false;
    }
    const here = titles.findLastIndex((title) => title.closest('[data-for="section"]').contains(document.activeElement));
    const step = next ? 1 : -1;
    let index = here + step;
    if (here < 0) {
        index = next ? 0 : titles.length - 1;
    }
    const title = titles[index];
    if (!title) {
        return false;
    }
    title.tabIndex = -1;
    title.focus();
    title.scrollIntoView({block: 'start'});
    return true;
};

/**
 * Whether the user is typing: in a field, a list or an editor, the keys are theirs.
 *
 * @param {EventTarget} target The target of the key.
 * @return {boolean}
 */
const typing = (target) => target instanceof HTMLElement
    && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    && !(target.tagName === 'INPUT' && ['checkbox', 'radio', 'button', 'submit'].includes(target.type));

/**
 * Whether the platform is a Mac (or an iPad with a keyboard).
 *
 * @return {boolean}
 */
const isMac = () => /Mac|iPhone|iPad/.test(navigator.userAgentData?.platform || navigator.platform || '');

/**
 * Whether a key goes to the page and not to the shortcuts: with a modifier, while typing, or in another window.
 *
 * @param {KeyboardEvent} event The key.
 * @return {boolean}
 */
const blocked = (event) => event.ctrlKey || event.metaKey || event.altKey || event.defaultPrevented
    || typing(event.target) || Boolean(document.querySelector('dialog[open], .modal.show, .dropdown-menu.show'));

/**
 * A key of navigation: N and P, J and K, G then a letter within two seconds.
 *
 * @param {KeyboardEvent} event The key.
 * @param {{pending: number}} state When G was pressed, 0 for not.
 * @param {boolean} loggedin Whether the user is logged in (the pages of G need it).
 */
const navigate = (event, state, loggedin) => {
    const key = event.key.toLowerCase();
    if (state.pending && Date.now() - state.pending < 2000 && PAGES[key] && loggedin) {
        state.pending = 0;
        event.preventDefault();
        window.location.href = M.cfg.wwwroot + PAGES[key];
        return;
    }
    state.pending = key === 'g' ? Date.now() : 0;
    const actions = {n: () => followActivity(true), p: () => followActivity(false), j: () => moveSection(true),
        k: () => moveSection(false)};
    if ((actions[key] && actions[key]()) || state.pending) {
        event.preventDefault();
    }
};

/**
 * Starts the help.
 */
export const init = () => {
    const dialog = document.getElementById('epure-shortcuts');
    if (!dialog || dialog.dataset.ready) {
        return;
    }
    dialog.dataset.ready = '1';
    const box = dialog.querySelector('[data-action="epure-shortcuts-key"]');
    const loggedin = dialog.dataset.loggedin === '1';
    if (!loggedin) {
        try {
            box.checked = window.localStorage.getItem(STORAGE) !== '0';
        } catch (e) {
            // Expected: the storage of the browser can be blocked; the key stays on.
        }
    }
    if (isMac()) {
        dialog.querySelectorAll('kbd[data-mac]').forEach((key) => {
            key.textContent = key.dataset.mac;
        });
    }
    let opener = null;

    const open = () => {
        if (dialog.open) {
            return;
        }
        opener = document.activeElement;
        dialog.showModal();
        dialog.querySelector('#epure-shortcuts-title').focus();
    };

    document.addEventListener('click', (event) => {
        // The window « ? » of the footer is a copy cleaned by Bootstrap: a link to the help, not a button.
        const button = event.target.closest('[data-action="epure-shortcuts"], a[href$="#epure-shortcuts"]');
        if (button) {
            event.preventDefault();
            open();
        }
    });
    dialog.querySelector('[data-action="epure-shortcuts-close"]').addEventListener('click', () => dialog.close());
    // A click on the backdrop closes the window.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
    dialog.addEventListener('close', () => {
        if (opener && document.contains(opener)) {
            opener.focus();
        }
    });
    // The keys of navigation: off by default, turned on in this window (the preference of the user, or the browser
    // of a visitor).
    const nav = dialog.querySelector('[data-action="epure-shortcuts-nav"]');
    if (nav && !loggedin) {
        try {
            nav.checked = window.localStorage.getItem(NAV_STORAGE) === '1';
        } catch (e) {
            // Expected: the storage of the browser can be blocked; the keys stay off.
        }
    }
    nav?.addEventListener('change', () => {
        if (loggedin) {
            setUserPreference('theme_epure_navkeys', nav.checked ? 1 : 0);
        } else {
            try {
                window.localStorage.setItem(NAV_STORAGE, nav.checked ? '1' : '0');
            } catch (e) {
                // Expected: the storage of the browser can be blocked; the choice lasts for the page.
            }
        }
    });
    const state = {pending: 0};
    document.addEventListener('keydown', (event) => {
        if (nav?.checked && !blocked(event)) {
            navigate(event, state, loggedin);
        }
    });
    box.addEventListener('change', () => {
        if (loggedin) {
            setUserPreference('theme_epure_shortcuts', box.checked ? 1 : 0);
        } else {
            try {
                window.localStorage.setItem(STORAGE, box.checked ? '1' : '0');
            } catch (e) {
                // Expected: the storage of the browser can be blocked; the choice lasts for the page.
            }
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== '?' || event.ctrlKey || event.metaKey || event.altKey || event.defaultPrevented
                || !box.checked || typing(event.target)
                || document.querySelector('dialog[open], .modal.show, .dropdown-menu.show')) {
            return;
        }
        event.preventDefault();
        open();
    });
};
