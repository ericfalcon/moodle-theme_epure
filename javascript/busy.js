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
 * « Moodle is working » indicator of the install and upgrade pages of Épure.
 *
 * Once a form is sent or a button link followed, the page waits, sometimes for minutes, for the
 * answer of the server. After a short delay, an overlay tells it, keeps people from sending
 * twice, and is read by screen readers. Inline and without dependency: it must work while
 * Moodle is being upgraded.
 *
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    var overlay = document.getElementById('epure-busy');
    if (!overlay) {
        return;
    }
    // Moodle prints the end of the page inside its footer, which can be hidden: the overlay goes to the page itself.
    document.body.appendChild(overlay);
    var timer = null;
    var clock = null;
    var turn = null;
    var elapsed = overlay.querySelector('[data-region="elapsed"]');
    var spinner = overlay.querySelector('.epure-busy-spinner');
    // Chrome stops the animations of CSS once the next page is asked for, and may hold a circle turned by a transform,
    // while it still paints what the scripts change (the time below goes on): the arc is painted by the script, its
    // place on the ring changed every 40 ms. When the system asks for less motion, it moves a quarter of the ring each
    // second, with the time, without sliding: still a sign that the page is alive.
    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var spin = function() {
        if (!spinner || !window.getComputedStyle) {
            return;
        }
        var style = window.getComputedStyle(spinner);
        var arc = style.borderTopColor;
        var ring = style.borderRightColor;
        var width = style.borderTopWidth;
        var paint = function(angle) {
            spinner.style.background = 'conic-gradient(from ' + angle + 'deg, ' + arc + ' 0deg 90deg, ' + ring
                + ' 90deg 360deg) border-box';
        };
        var mask = 'radial-gradient(farthest-side, transparent calc(100% - ' + width + '), #000 calc(100% - ' + width
            + '))';
        spinner.style.animation = 'none';
        spinner.style.borderColor = 'transparent';
        spinner.style.setProperty('-webkit-mask', mask);
        spinner.style.setProperty('mask', mask);
        var start = Date.now();
        paint(0);
        turn = window.setInterval(function() {
            var time = Date.now() - start;
            paint(still ? Math.floor(time / 1000) % 4 * 90 : Math.round((time % 1000) * 0.36));
        }, still ? 250 : 40);
    };

    // « 12 s », « 1 min 05 s »: the time since the step started.
    var duration = function(seconds) {
        var minutes = Math.floor(seconds / 60);
        var rest = seconds % 60;
        return minutes ? minutes + ' min ' + (rest < 10 ? '0' : '') + rest + ' s' : rest + ' s';
    };
    var tick = function(start) {
        if (elapsed) {
            var seconds = Math.round((Date.now() - start) / 1000);
            elapsed.textContent = elapsed.getAttribute('data-format').replace('{time}', duration(seconds));
        }
    };

    var show = function() {
        var start = Date.now();
        timer = window.setTimeout(function() {
            overlay.hidden = false;
            overlay.querySelector('[data-region="message"]').textContent = overlay.getAttribute('data-message');
            document.body.setAttribute('aria-busy', 'true');
            tick(start);
            clock = window.setInterval(function() {
                tick(start);
            }, 1000);
            spin();
        }, 500);
    };
    var hide = function() {
        window.clearTimeout(timer);
        window.clearInterval(clock);
        window.clearInterval(turn);
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
