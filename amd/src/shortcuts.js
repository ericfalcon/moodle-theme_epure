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
 * Help of the keyboard shortcuts: the key « ? » (except while typing, or when another window is open), the link
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
