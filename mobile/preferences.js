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
 * The page « Display preferences » of the Moodle app: each choice saved in the profile of the user at once, as with the
 * button « Aa » of the site, then applied to the app (mobile/init.js).
 *
 * Run by the app (Site plugins), with this as the component of the page.
 *
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var that = this;

/**
 * Saves a preference, then applies the display.
 *
 * @param {string} name Name of the preference, without its prefix.
 * @param {string|number|boolean} value Its value; a switch is 1 or 0.
 * @returns {Promise}
 */
this.epureSave = function(name, value) {
    if (value === true || value === false) {
        value = value ? 1 : 0;
    }
    var site = that.CoreSitesProvider.getCurrentSite();
    return site.write('core_user_update_user_preferences', {
        preferences: [{type: 'theme_epure_a11y_' + name, value: String(value)}],
    }).then(function() {
        return window.theme_epure_display ? window.theme_epure_display() : null;
    }).catch(function() {
        that.CoreDomUtilsProvider.showErrorModal(that.TranslateService.instant('plugin.theme_epure.a11ynotsaved'));
    });
};
