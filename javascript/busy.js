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

/*
 * « Moodle is working » indicator of the install and upgrade pages of Épure.
 *
 * Once a form is sent or a button link followed, the page waits, sometimes for minutes, for the
 * answer of the server. After a short delay, an overlay tells it, keeps people from sending
 * twice, and is read by screen readers. Inline and without dependency: it must work while
 * Moodle is being upgraded.
 */
(function() {
    var overlay = document.getElementById('epure-busy');
    if (!overlay) {
        return;
    }
    // Moodle prints the end of the page inside its footer, which can be hidden: the overlay goes to the page itself.
    document.body.appendChild(overlay);
    var timer = null;

    var show = function() {
        timer = window.setTimeout(function() {
            overlay.hidden = false;
            overlay.querySelector('[data-region="message"]').textContent = overlay.getAttribute('data-message');
            document.body.setAttribute('aria-busy', 'true');
        }, 500);
    };
    var hide = function() {
        window.clearTimeout(timer);
        overlay.hidden = true;
        overlay.querySelector('[data-region="message"]').textContent = '';
        document.body.removeAttribute('aria-busy');
    };

    document.addEventListener('submit', function(e) {
        var form = e.target;
        // Wait for the other handlers: a form stopped by its own checks does not leave the page.
        window.setTimeout(function() {
            if (!e.defaultPrevented && form.getAttribute('target') !== '_blank') {
                show();
            }
        }, 0);
    });
    document.addEventListener('click', function(e) {
        var link = e.target.closest ? e.target.closest('a.btn[href], .continuebutton a[href]') : null;
        if (!link || e.ctrlKey || e.metaKey || e.shiftKey || link.target === '_blank'
                || link.getAttribute('href').charAt(0) === '#') {
            return;
        }
        window.setTimeout(function() {
            if (!e.defaultPrevented) {
                show();
            }
        }, 0);
    });
    // Back to the page from the history of the browser: it is not working any more.
    window.addEventListener('pageshow', hide);
})();
