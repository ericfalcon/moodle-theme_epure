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
 * « Moodle is working » indicator of the pages that install or upgrade Moodle and its plugins.
 *
 * Two moments: once a step is started from a page (a form sent, a button followed), an overlay on
 * that page while the server works (end of the body); and while the server sends the page bit by
 * bit, as during an upgrade, a message from the top of the page until it is complete (top of the
 * body). Built without templates: these pages also run in Moodle's maintenance mode.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class busy {
    /** @var string[] Page types of the installation and upgrade of Moodle and its plugins. */
    public const PAGES = ['admin-index', 'admin-upgradesettings', 'admin-plugins', 'admin-environment',
        'admin-purgecaches'];

    /**
     * Whether the page installs or upgrades Moodle or its plugins.
     *
     * @param \moodle_page $page The page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        $pagetype = (string) $page->pagetype;
        return in_array($pagetype, self::PAGES, true) || str_starts_with($pagetype, 'admin-tool-installaddon');
    }

    /**
     * Message shown while the page itself is still loading, removed once it is complete. It only
     * appears after a second (style sheet), so that it never flashes on the pages that load quickly.
     *
     * @param \moodle_page $page The page.
     * @return string HTML.
     */
    public static function top_of_body(\moodle_page $page): string {
        if (!self::applies($page)) {
            return '';
        }
        return self::box('epure-busy-loading', 'epure-busy epure-busy-loading', false)
            . \html_writer::script("document.addEventListener('DOMContentLoaded', function() {"
                . "var e = document.getElementById('epure-busy-loading'); if (e) { e.remove(); } });");
    }

    /**
     * Overlay shown once a step is started from the page (see javascript/busy.js).
     *
     * @param \moodle_page $page The page.
     * @return string HTML.
     */
    public static function end_of_body(\moodle_page $page): string {
        global $CFG;
        if (!self::applies($page)) {
            return '';
        }
        return self::box('epure-busy', 'epure-busy', true)
            . \html_writer::script(file_get_contents($CFG->dirroot . '/theme/epure/javascript/busy.js'));
    }

    /**
     * The message: spinner, title and detail.
     *
     * @param string $id Id of the element.
     * @param string $class Classes of the element.
     * @param bool $overlay Whether it is the overlay, hidden until a step is started.
     * @return string HTML.
     */
    protected static function box(string $id, string $class, bool $overlay): string {
        $message = get_string('busy', 'theme_epure');
        $detail = get_string('busy_detail', 'theme_epure');
        $attributes = ['class' => $class, 'id' => $id];
        if ($overlay) {
            $attributes += ['data-message' => $message . ' ' . $detail, 'hidden' => 'hidden'];
            // The message is put in the live region when the overlay appears, to be read out.
            $status = \html_writer::tag('p', '', ['class' => 'sr-only visually-hidden', 'role' => 'status',
                'data-region' => 'message']);
        } else {
            $attributes['role'] = 'status';
            $status = '';
        }
        return \html_writer::div(
            \html_writer::div(
                \html_writer::span('', 'epure-busy-spinner', ['aria-hidden' => 'true'])
                    . \html_writer::tag('p', $message, ['class' => 'epure-busy-title'])
                    . \html_writer::tag('p', $detail, ['class' => 'epure-busy-detail'])
                    . $status,
                'epure-busy-box'
            ),
            '',
            $attributes
        );
    }
}
