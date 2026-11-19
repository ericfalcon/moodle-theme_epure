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
 * Guide « Which profile to choose? »: the profiles of the display preferences (« Aa »), whom each is for, what it
 * changes and how to adjust it, and the preferences to choose on their own. For the learners and those who help them
 * (disability advisers, trainers), on one page to print. Open without logging in: it holds nothing of the platform.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- The guide holds nothing of the platform, to give to the learners.
require(__DIR__ . '/../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(\theme_epure\a11y::profiles_guide_url());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('profilesguide', 'theme_epure'));
$PAGE->set_heading(get_string('profilesguide', 'theme_epure'));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_epure/profiles_guide', \theme_epure\a11y::profiles_guide());
echo $OUTPUT->footer();
