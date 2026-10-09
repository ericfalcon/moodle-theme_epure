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
 * Error pages in the colours of the theme: a card that says in plain words what happened, above Moodle's message.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class error_page {
    /** @var string[][] Error codes of Moodle by kind of error; the other codes are general errors. */
    const KINDS = [
        'notfound' => [
            'filenotfound', 'invalidcmorid', 'invalidcourseid', 'invalidcoursemodule', 'invalidcoursemoduleid', 'invalidid',
            'invalidrecord', 'invalidrecordunknown', 'invaliduser', 'invaliduserid', 'pagenotexist',
        ],
        'denied' => ['accessdenied', 'noguest', 'nopermissions', 'nopermissiontoshow'],
        'unavailable' => ['activityiscurrentlyhidden', 'coursehidden', 'notavailable', 'requireloginerror'],
        'session' => ['invalidsesskey', 'sessionerroruser', 'sessionerroruser2'],
    ];

    /** @var string[] Font Awesome icon of each kind of error. */
    const ICONS = [
        'notfound' => 'fa-compass',
        'denied' => 'fa-lock',
        'unavailable' => 'fa-eye-slash',
        'session' => 'fa-clock',
        'general' => 'fa-triangle-exclamation',
    ];

    /**
     * Kind of an error, from its code.
     *
     * @param string $errorcode Error code of Moodle.
     * @return string notfound, denied, unavailable, session or general.
     */
    public static function kind(string $errorcode): string {
        foreach (self::KINDS as $kind => $codes) {
            if (in_array($errorcode, $codes, true)) {
                return $kind;
            }
        }
        return 'general';
    }

    /**
     * Context of the card for the template theme_epure/error_card.
     *
     * @param string $kind Kind of the error.
     * @param string $message Message of Moodle, HTML.
     * @return array
     */
    public static function export(string $kind, string $message): array {
        global $CFG;
        $actions = [];
        if (isloggedin() && !isguestuser()) {
            $actions[] = [
                'url' => (new \moodle_url('/my/'))->out(false),
                'label' => get_string('myhome'),
            ];
        } else if (!isloggedin() && !empty($CFG->rolesactive)) {
            $actions[] = [
                'url' => get_login_url(),
                'label' => get_string('login'),
            ];
        }
        return [
            'kind' => $kind,
            'icon' => self::ICONS[$kind] ?? self::ICONS['general'],
            'title' => get_string('error_' . $kind, 'theme_epure'),
            'lead' => get_string('error_' . $kind . '_lead', 'theme_epure'),
            'message' => $message,
            'actions' => $actions,
            'hasactions' => !empty($actions),
        ];
    }

    /**
     * Whether the page is the « page not found » page of Moodle (error/index.php, or its route on Moodle 5.x).
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function is_not_found_page(\moodle_page $page): bool {
        if (!$page->has_set_url()) {
            return false;
        }
        return (bool) preg_match('~/error(/index\.php)?$~', $page->url->get_path(false));
    }
}
