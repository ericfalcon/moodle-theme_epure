<?php
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
 * Page « You are offline » of the web app, kept by its service worker and shown without network.
 *
 * It is self-contained (no style sheet, no script of Moodle), as nothing else is available offline.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- The page is shown offline, to anybody.
require_once(__DIR__ . '/../../../config.php');

if (!\theme_epure\web_app::enabled()) {
    send_file_not_found();
}
$PAGE->set_context(context_system::instance());
$PAGE->set_url(\theme_epure\web_app::url('offline'));
$colours = \theme_epure\web_app::colours();
$name = format_string($SITE->fullname, true, ['context' => context_system::instance()]);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: private, max-age=0');
echo $OUTPUT->render_from_template('theme_epure/webapp_offline', [
    'lang' => current_language(),
    'sitename' => $name,
    'brand' => $colours['fill'],
    'onbrand' => $colours['on'],
]);
