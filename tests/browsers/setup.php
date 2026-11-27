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
 * The site of the checks in other browsers (tests/browsers/check.js): Épure as the theme, a learner enrolled in a course
 * with sections and activities. Run by the continuous integration on a site of tests only, never on a real site.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/clilib.php');

if (!getenv('EPURE_BROWSERS')) {
    cli_error('Only for the continuous integration: EPURE_BROWSERS is not set.');
}

// A site waiting for an upgrade leads every page to the administration: said in the log.
if (moodle_needs_upgrading()) {
    echo "The site needs an upgrade.\n";
}

\core\session\manager::set_user(get_admin());
set_config('theme', 'epure');

// The learner.
$user = core_user::get_user_by_username('learner');
if (!$user) {
    $user = core_user::get_user(user_create_user((object) [
        'username' => 'learner',
        'password' => 'Browsers-2026!',
        'firstname' => 'Camille',
        'lastname' => 'Martin',
        'email' => 'learner@example.com',
        'auth' => 'manual',
        'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id,
    ]));
}

// The course, with its sections and activities.
$course = $DB->get_record('course', ['shortname' => 'BROWSERS']);
if (!$course) {
    $course = create_course((object) [
        'fullname' => 'First aid essentials',
        'shortname' => 'BROWSERS',
        'category' => core_course_category::get_default()->id,
        'format' => 'topics',
        'numsections' => 3,
        'enablecompletion' => 1,
        'summary' => 'What to do in the first minutes.',
    ]);
    foreach (['Key facts', 'Going further'] as $section => $name) {
        add_moduleinfo((object) [
            'modulename' => 'page',
            'module' => $DB->get_field('modules', 'id', ['name' => 'page']),
            'course' => $course->id,
            'section' => $section + 1,
            'visible' => 1,
            'name' => $name,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'content' => '<p>' . $name . '.</p>',
            'contentformat' => FORMAT_HTML,
            'display' => 5,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ], $course);
    }
}
enrol_try_internal_enrol($course->id, $user->id, $DB->get_field('role', 'id', ['shortname' => 'student']));

theme_reset_all_caches();
echo $course->id, "\n";
