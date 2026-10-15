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
 * Review of a quiz attempt: all the questions, or only those to look at again (not right).
 *
 * @module     theme_epure/quiz_review
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-epure-review]');
        if (!button) {
            return;
        }
        const mistakes = button.dataset.epureReview === 'mistakes';
        document.body.classList.toggle('epure-review-mistakes', mistakes);
        button.parentElement.querySelectorAll('[data-epure-review]').forEach((other) => {
            const pressed = other === button;
            other.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            other.classList.toggle('btn-primary', pressed);
            other.classList.toggle('btn-outline-primary', !pressed);
        });
    });
};
