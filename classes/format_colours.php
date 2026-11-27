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

/**
 * The colour chosen for the active tab in the course format Section tabs (format_sectiontabs), for the course or for
 * the site: kept, in light and dark mode and under the cream overlay, moved as little as needed for the text on it to
 * reach 4.5:1 there; yellow on black puts its own colours instead (scss/epure/_preferences.scss).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_colours {
    /** @var string The elements of Section tabs that carry its colours. */
    protected const SELECTOR = '.gtabs, .gtabs-layout, .gsubtabs, .gnav';

    /**
     * The CSS of the colour of the active tab of a course in Section tabs, or '' when no colour is chosen.
     *
     * @param \stdClass|null $course The course of the page.
     * @return string
     */
    public static function css(?\stdClass $course): string {
        if (!$course || empty($course->id) || $course->id == SITEID || ($course->format ?? '') !== 'sectiontabs') {
            return '';
        }
        $colour = self::chosen($course);
        if ($colour === null) {
            return '';
        }
        $light = palette::derive($colour);
        $cream = palette::cream($colour, null, 'brand');
        return self::SELECTOR . ' { ' . self::vars($light['fill'], $light['on']) . ' }'
            . ' html.epure-a11y-scheme-cream :is(' . self::SELECTOR . ') { '
            . self::vars($cream['epure-cream-brand'], $cream['epure-cream-on-brand']) . ' }';
    }

    /**
     * The colour chosen for the active tab: that of the course, else that of the site.
     *
     * @param \stdClass $course
     * @return string|null
     */
    public static function chosen(\stdClass $course): ?string {
        try {
            $format = course_get_format($course);
        } catch (\Throwable $e) {
            return null;
        }
        if (!method_exists($format, 'get_tab_colour')) {
            return null;
        }
        return palette::normalise($format->get_tab_colour('activecolor'));
    }

    /**
     * The custom properties of the colours of Section tabs, which outweigh those it puts on its elements.
     *
     * @param string $fill The colour of the active tab.
     * @param string $on The colour of its text.
     * @return string
     */
    protected static function vars(string $fill, string $on): string {
        return "--gtabs-active: {$fill} !important; --gtabs-active-text: {$on} !important;";
    }
}
