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
 * Previous and next buttons of the course format Section tabs in the navigation bar of the phones, within reach of
 * the thumb: the links of the bar of the format at the bottom of the section, followed as they change with its
 * subsection tabs. A button passes its click to the link of the format, which opens the subsection itself.
 *
 * @module     theme_epure/mobile_steps
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Shows a button as the link of the format, or hides it when the format hides its link.
 *
 * @param {HTMLAnchorElement|null} source Link of the format (.gnav-prev or .gnav-next).
 * @param {HTMLAnchorElement} button Button of the bar.
 * @returns {boolean} Whether the button is shown.
 */
const copy = (source, button) => {
    const shown = !!source && !source.classList.contains('d-none') && !source.hidden;
    button.hidden = !shown;
    if (shown) {
        button.href = source.href;
        button.querySelector('.epure-mobilenav-step-kind').textContent =
            (source.querySelector('.gnav-kindtext')?.textContent || '').trim();
        button.querySelector('.epure-mobilenav-step-name').textContent =
            (source.querySelector('.gnav-name')?.textContent || '').trim();
    }
    return shown;
};

export const init = () => {
    const nav = document.querySelector('#region-main .gnav');
    const row = document.querySelector('.epure-mobilenav-steps');
    if (!nav || !row) {
        return;
    }
    const buttons = {prev: row.querySelector('.epure-mobilenav-prev'), next: row.querySelector('.epure-mobilenav-next')};
    const update = () => {
        const shown = ['prev', 'next'].filter((direction) => copy(nav.querySelector(`.gnav-${direction}`), buttons[direction]));
        row.hidden = !shown.length;
        document.body.classList.toggle('epure-has-mobilenav-steps', shown.length > 0);
    };
    Object.entries(buttons).forEach(([direction, button]) => button.addEventListener('click', (event) => {
        const source = nav.querySelector(`.gnav-${direction}`);
        if (source) {
            event.preventDefault();
            source.click();
        }
    }));
    update();
    new MutationObserver(update).observe(nav, {
        subtree: true,
        childList: true,
        characterData: true,
        attributes: true,
        attributeFilter: ['href', 'class', 'hidden'],
    });
};
