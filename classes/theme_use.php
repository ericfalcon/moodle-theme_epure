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
 * Whether Épure is the theme in use: what the theme adds outside its pages (the words of the IOMAD
 * companies, the e-mails) only applies with it, so that another theme stays as it is without Épure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_use {
    /** @var array<string, bool> Whether each theme is Épure or one of its children. */
    protected static array $themes = [];

    /**
     * Whether a theme is Épure or a child of Épure.
     *
     * @param string|null $name Theme name.
     * @return bool
     */
    public static function is_epure(?string $name): bool {
        $name = (string) $name;
        if ($name === 'epure') {
            return true;
        }
        if ($name === '') {
            return false;
        }
        if (!array_key_exists($name, self::$themes)) {
            // Loading a theme can load strings: the answer is false until it is known.
            self::$themes[$name] = false;
            if (!\core_component::get_component_directory('theme_' . $name)) {
                return false;
            }
            try {
                self::$themes[$name] = in_array('epure', \theme_config::load($name)->parents ?? [], true);
            } catch (\Throwable $e) {
                self::$themes[$name] = false;
            }
        }
        return self::$themes[$name];
    }

    /**
     * The theme of the pages of the current user, as Moodle chooses it, without the course and category
     * themes: the theme of the session (IOMAD sets it from the address of a company), then the theme of the
     * user (IOMAD sets it to the theme of their company), then the theme of the site.
     *
     * @return string Theme name.
     */
    public static function current_name(): string {
        global $CFG, $SESSION, $USER;
        if (!empty($SESSION->theme)) {
            return (string) $SESSION->theme;
        }
        if (!empty($CFG->allowuserthemes) && !empty($USER->theme)) {
            return (string) $USER->theme;
        }
        return (string) ($CFG->theme ?? '');
    }

    /**
     * Whether the current user sees the pages with Épure.
     *
     * @return bool
     */
    public static function current(): bool {
        return self::is_epure(self::current_name());
    }

    /**
     * Whether an IOMAD company uses Épure (a company without a theme uses the theme of the site).
     *
     * @param \stdClass $company Company record.
     * @return bool
     */
    public static function company(\stdClass $company): bool {
        global $CFG;
        return self::is_epure(!empty($company->theme) ? $company->theme : ($CFG->theme ?? ''));
    }

    /**
     * Forgets the themes of the request (for tests).
     */
    public static function reset(): void {
        self::$themes = [];
    }
}
