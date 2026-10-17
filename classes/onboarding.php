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

namespace theme_epure;

use tool_usertours\step;
use tool_usertours\target;
use tool_usertours\tour;

/**
 * Getting started by role: a short guided tour for the learner (dashboard), the teacher (course page) and the
 * manager (dashboard), each opening with a welcome message.
 *
 * The tours are user tours of Moodle, created once with the theme: the administrators can edit, disable or delete them
 * (Site administration › Appearance › User tours). They show only with Épure (filter « Theme ») and to their
 * audience (filter « Épure audience », {@see usertours\audience_filter}). Their texts are strings of the theme, so
 * that they follow the language and the vocabulary of the user. A step whose element is not on the page is skipped.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class onboarding {
    /** @var string[] Audiences, from the most to the least specific. */
    public const AUDIENCES = ['manager', 'teacher', 'learner'];

    /**
     * The tours: for each audience, the pages it shows on and its steps, as [step => selector of the element it points
     * at, null for a message in the middle of the page]. Their strings are tour_{audience}_{step}_title and _content.
     */
    public const TOURS = [
        'learner' => [
            'pathmatch' => '/my/%',
            'steps' => [
                'welcome' => null,
                'resume' => '.epure-learner-resume',
                'week' => '.epure-week-items',
                'search' => '.epure-qs-button',
                'display' => '.epure-a11y-toggle',
                'usermenu' => '#user-menu-toggle',
                'end' => null,
            ],
        ],
        'teacher' => [
            'pathmatch' => '/course/view.php%',
            'steps' => [
                'welcome' => null,
                'figures' => '.epure-course-banner-figures',
                'editmode' => '.editmode-switch-form',
                'navigation' => '.secondary-navigation',
                'end' => null,
            ],
        ],
        'manager' => [
            'pathmatch' => '/my/%',
            'steps' => [
                'welcome' => null,
                'admin' => '.primary-navigation a[href$="/admin/search.php"]',
                'iomad' => '.primary-navigation a[href*="/blocks/iomad_company_admin/index.php"]',
                'search' => '.epure-qs-button',
                'usermenu' => '#user-menu-toggle',
                'end' => null,
            ],
        ],
    ];

    /**
     * The audiences of a user in a context: manager (site administrators, whoever configures the site or manages the
     * categories, IOMAD company managers), teacher (in a course: whoever teaches it; elsewhere: whoever manages
     * activities in a course), else learner.
     *
     * @param \context $context Context of the page.
     * @param int $userid User.
     * @return string[]
     */
    public static function audiences(\context $context, int $userid): array {
        if (!isloggedin() || isguestuser($userid)) {
            return [];
        }
        $audiences = [];
        $system = \context_system::instance();
        if (
            is_siteadmin($userid) || has_capability('moodle/site:config', $system, $userid)
                || has_capability('moodle/category:manage', $system, $userid) || iomad::is_manager($userid)
        ) {
            $audiences[] = 'manager';
        }
        $course = $context->get_course_context(false);
        $teaching = $course && (int) $course->instanceid !== (int) SITEID
            ? mycourses::is_teaching($course, $userid)
            : simple_view::manages_activities($userid);
        if ($teaching) {
            $audiences[] = 'teacher';
        }
        return $audiences ?: ['learner'];
    }

    /**
     * Creates the tours, once: the administrators may then change or delete them.
     *
     * @return void
     */
    public static function install(): void {
        global $DB;
        if (
            get_config('theme_epure', 'onboardingtours') !== false
                || !$DB->get_manager()->table_exists('tool_usertours_tours')
        ) {
            return;
        }
        $ids = [];
        foreach (self::TOURS as $audience => $definition) {
            $tour = new tour();
            $tour->set_name("tour_{$audience}_name,theme_epure");
            $tour->set_description("tour_{$audience}_description,theme_epure");
            $tour->set_pathmatch($definition['pathmatch']);
            $tour->set_enabled(tour::ENABLED);
            $tour->set_filter_values('theme', ['epure']);
            $tour->set_filter_values(usertours\audience_filter::get_filter_name(), [$audience]);
            $tour->set_display_step_numbers(true);
            $tour->persist(true);
            foreach ($definition['steps'] as $name => $selector) {
                $step = new step();
                $step->set_tourid($tour->get_id());
                $step->set_title("tour_{$audience}_{$name}_title,theme_epure");
                $step->set_content("tour_{$audience}_{$name}_content,theme_epure", FORMAT_HTML);
                $step->set_targettype($selector === null ? target::TARGET_UNATTACHED : target::TARGET_SELECTOR);
                $step->set_targetvalue($selector ?? '');
                $step->persist(true);
            }
            $ids[$audience] = $tour->get_id();
        }
        set_config('onboardingtours', json_encode($ids), 'theme_epure');
    }

    /**
     * Deletes the tours that are still there, when the theme is uninstalled.
     *
     * @return void
     */
    public static function remove(): void {
        $ids = json_decode((string) get_config('theme_epure', 'onboardingtours'), true) ?: [];
        foreach ($ids as $id) {
            try {
                tour::instance((int) $id)->remove();
            } catch (\dml_missing_record_exception $e) {
                // Already deleted by an administrator.
                continue;
            }
        }
        unset_config('onboardingtours', 'theme_epure');
    }
}
