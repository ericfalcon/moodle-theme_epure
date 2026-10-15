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
 * Summary of a quiz attempt: the button to submit at the top of the page opens the confirmation of the button at the
 * bottom, the one Moodle listens to.
 *
 * @module     theme_epure/quiz_summary
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    document.addEventListener('click', (e) => {
        const top = e.target.closest('[data-action="epure-quiz-finish"]');
        const submit = document.querySelector('.btn-finishattempt button');
        if (top && submit) {
            e.preventDefault();
            submit.click();
        }
    });
};
