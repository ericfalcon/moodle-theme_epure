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

namespace theme_epure\usertours;

use context;
use theme_epure\onboarding;
use tool_usertours\tour;

/**
 * Filter of the user tours by audience of Épure: learner, teacher or manager ({@see onboarding::audiences()}). It is
 * added to the filters of the tours by the hook before_serverside_filter_fetch.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audience_filter extends \tool_usertours\local\filter\base {
    /**
     * Name of the filter.
     *
     * @return string
     */
    public static function get_filter_name() {
        return 'epure_audience';
    }

    /**
     * The audiences.
     *
     * @return array
     */
    public static function get_filter_options() {
        $options = [];
        foreach (onboarding::AUDIENCES as $audience) {
            $options[$audience] = get_string('audience_' . $audience, 'theme_epure');
        }
        return $options;
    }

    /**
     * Whether the current user belongs to one of the audiences of the tour, in the context of the page.
     *
     * @param tour $tour Tour.
     * @param context $context Context of the page.
     * @return bool
     */
    public static function filter_matches(tour $tour, context $context) {
        global $USER;
        $values = $tour->get_filter_values(self::get_filter_name());
        if (empty($values)) {
            return true;
        }
        return (bool) array_intersect($values, onboarding::audiences($context, (int) $USER->id));
    }

    /**
     * The field of the filter in the form of a tour, with the strings of the theme.
     *
     * @param \MoodleQuickForm $mform Form.
     * @return void
     */
    public static function add_filter_to_form(\MoodleQuickForm &$mform) {
        $key = 'filter_' . self::get_filter_name();
        $options = [self::ANYVALUE => get_string('all')] + self::get_filter_options();
        $mform->addElement('select', $key, get_string('tourfilter', 'theme_epure'), $options, ['multiple' => true]);
        $mform->setDefault($key, self::ANYVALUE);
        $mform->addHelpButton($key, 'tourfilter', 'theme_epure');
    }
}
