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
 * The Moodle app, when it opens the site: the display preferences of « Aa » of the user, as classes of its html element,
 * which the style sheet of the theme for the app applies (theme_epure\mobile_app). Again when the app comes back to the
 * front, and after a change in the page « Display preferences » of the app (window.theme_epure_display).
 *
 * Run by the app (Site plugins, init), with this as the context of the plugin.
 *
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function(context) {
    var PREFERENCE = /^epure-(a11y-|dark$)/;
    var sites = context.CoreSitesProvider;

    /**
     * Puts the classes on the html element of the app, and its dark mode with the dark colours and yellow on black.
     *
     * @param {string} classes Classes separated by spaces.
     */
    var apply = function(classes) {
        var html = document.documentElement;
        Array.prototype.slice.call(html.classList).forEach(function(name) {
            if (PREFERENCE.test(name)) {
                html.classList.remove(name);
            }
        });
        var wanted = (classes || '').split(/\s+/).filter(Boolean);
        wanted.forEach(function(name) {
            html.classList.add(name);
        });
        if (wanted.indexOf('epure-dark') !== -1) {
            html.classList.add('dark');
        } else if (wanted.indexOf('epure-a11y-scheme-light') !== -1 || wanted.indexOf('epure-a11y-scheme-cream') !== -1) {
            html.classList.remove('dark');
        }
    };

    /**
     * Reads the classes of the user from the site, never from the cache of the app.
     *
     * @returns {Promise}
     */
    var refresh = function() {
        var site = sites && sites.getCurrentSite();
        if (!site) {
            return Promise.resolve();
        }
        return site.read('theme_epure_display_classes', {}, {getFromCache: false, saveToCache: false})
            .then(function(result) {
                apply(result.classes);
            }).catch(function() {
                // Without the site (offline), the app keeps the display it has.
            });
    };

    window.theme_epure_display = refresh;
    apply(INITIAL_CLASSES);
    refresh();
    document.addEventListener('resume', refresh);
})(this);
