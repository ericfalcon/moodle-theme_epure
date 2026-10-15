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
 * Quiz attempt: a choice is chosen by a click anywhere on its row, not only on its button or its label.
 *
 * @module     theme_epure/quiz_attempt
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const ROWS = '.que:is(.multichoice, .truefalse, .calculatedmulti, .multichoiceset) .answer > div';

export const init = () => {
    document.addEventListener('click', (e) => {
        const row = e.target.closest(ROWS);
        // The button and the label already choose; a link or a media in the text keeps its own click.
        if (!row || e.target.closest('input, label, a, button, audio, video, select, textarea')) {
            return;
        }
        const input = row.querySelector('input[type="radio"], input[type="checkbox"]');
        if (input && !input.disabled) {
            input.click();
            input.focus();
        }
    });
};
