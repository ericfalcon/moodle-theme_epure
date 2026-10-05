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
 * Reading mode of the pages of the activities: the side panels are hidden, and the choice is kept in the profile.
 *
 * @module     theme_epure/activity_page
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Turns the reading mode on or off.
 *
 * @param {HTMLElement} button Button of the reading mode.
 * @param {boolean} on Whether the mode is on.
 */
const setFocus = (button, on) => {
    document.documentElement.classList.toggle('epure-focus', on);
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
    // The primary navigation and the blocks adapt their layout to the new width.
    window.dispatchEvent(new Event('resize'));
    Ajax.call([{methodname: 'core_user_update_user_preferences', args: {
        preferences: [{type: 'theme_epure_focus', value: on ? 1 : 0}],
    }}])[0].catch(() => null);
};

/**
 * Starts the reading mode.
 */
export const init = () => {
    const button = document.querySelector('[data-action="epure-focus"]');
    if (!button) {
        return;
    }
    button.addEventListener('click', () => setFocus(button, button.getAttribute('aria-pressed') !== 'true'));
    document.addEventListener('keydown', (event) => {
        // Escape first closes the dialogues and the menus that are open.
        if (event.key === 'Escape' && button.getAttribute('aria-pressed') === 'true' && !event.defaultPrevented
            && !document.querySelector('.modal.show, .dropdown-menu.show, .popover.show')) {
            setFocus(button, false);
        }
    });
};
