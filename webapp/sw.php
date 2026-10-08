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
 * Service worker of the web app, for the whole site: it shows a page « You are offline » without network, and removes
 * itself once the web app is turned off or the user no longer sees Épure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- The browsers check the service worker, logged in or not.
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: text/javascript; charset=utf-8');
// Served from the theme, the service worker covers the whole site.
header('Service-Worker-Allowed: ' . \theme_epure\web_app::scope());
header('Cache-Control: no-cache, no-store, must-revalidate');
echo \theme_epure\web_app::service_worker();
