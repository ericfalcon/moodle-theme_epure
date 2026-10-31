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
 * The tree of the course index on Moodle 5.0: a subsection is a tree item inside the tree item of the activity that
 * holds it, without a group between them (WCAG 1.3.1, 4.1.2). As Moodle 5.1 does, the activity alone is the tree
 * item, and owns the list of the subsection. The index is drawn again as the course changes: each new subsection is
 * corrected too.
 *
 * @module     theme_epure/courseindex_tree
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Corrects the subsections of the index.
 *
 * @param {HTMLElement} index The course index.
 */
const correct = (index) => {
    index.querySelectorAll('[data-for="cm"][role="treeitem"] > [data-for="section"][role="treeitem"]').forEach((section) => {
        const item = section.parentElement;
        const title = section.querySelector('.courseindex-section-title');
        section.removeAttribute('role');
        if (section.hasAttribute('aria-owns')) {
            item.setAttribute('aria-owns', section.getAttribute('aria-owns'));
            section.removeAttribute('aria-owns');
        }
        if (title?.id) {
            item.setAttribute('aria-labelledby', title.id);
        }
    });
};

/**
 * Starts the correction.
 */
export const init = () => {
    const index = document.getElementById('courseindex');
    if (!index) {
        return;
    }
    correct(index);
    new MutationObserver(() => correct(index)).observe(index, {childList: true, subtree: true});
};
