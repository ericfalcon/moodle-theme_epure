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
 * Icon of the web app, as a square PNG.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// The icon is the same for everybody: no session.
define('NO_MOODLE_COOKIES', true);

// phpcs:ignore moodle.Files.RequireLogin.Missing -- The icon is read by the browsers and the systems, without a session.
require_once(__DIR__ . '/../../../config.php');

$size = optional_param('size', 512, PARAM_INT);
$colour = optional_param('colour', '', PARAM_ALPHANUM);

if (!get_config('theme_epure', 'webapp') || !function_exists('imagecreatetruecolor')) {
    send_file_not_found();
}
$png = \theme_epure\web_app::icon($size, $colour !== '' ? '#' . $colour : null);
header('Content-Type: image/png');
header('Cache-Control: public, max-age=' . YEARSECS . ', immutable');
header('Content-Length: ' . strlen($png));
echo $png;
