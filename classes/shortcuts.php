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
 * Help of the keyboard shortcuts: a window that lists the shortcuts of the page, opened with the key « ? », from the
 * window « ? » of the footer and from the panel « Aa ».
 *
 * The key « ? » alone is a shortcut of one character (WCAG 2.1.4): it is ignored while typing, and the window has a box
 * to turn it off, kept in the preferences of the user (in the browser for a visitor); the window still opens from the
 * footer and the panel « Aa ».
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class shortcuts {
    /** @var string Preference of the user: the key « ? » opens the help (1, by default) or not (0). */
    public const PREFERENCE = 'theme_epure_shortcuts';

    /**
     * Whether the key « ? » opens the help for the current user.
     *
     * @return bool
     */
    public static function key_enabled(): bool {
        if (!isloggedin() || isguestuser()) {
            return true;
        }
        return (int) get_user_preferences(self::PREFERENCE, 1) === 1;
    }

    /**
     * Context of the template theme_epure/shortcuts: the shortcuts of the page, by group, for the current user.
     *
     * Each key: its name, and on a Mac its symbol (mac). The keys of Moodle and of its text editor are listed too.
     *
     * @return array
     */
    public static function export(): array {
        $key = fn(string $name, string $mac = '') => ['name' => $name, 'mac' => $mac];
        $ctrl = $key(get_string('shortcuts_ctrl', 'theme_epure'), '⌘');
        $alt = $key(get_string('shortcuts_alt', 'theme_epure'), '⌥');
        // Keys pressed together (« Ctrl + K »), or one of them (« ↑ or ↓ »).
        $item = fn(array $keys, string $action, bool $either = false) => ['keys' => array_map(
            fn($k, $i) => $k + ['first' => $i === 0, 'either' => $either],
            $keys,
            array_keys($keys)
        ), 'action' => get_string('shortcuts_' . $action, 'theme_epure')];

        $page = [];
        $page[] = $item([$key('?')], 'help');
        if (quick_search::enabled()) {
            $page[] = $item([$ctrl, $key('K')], 'quicksearch');
        }
        if (isloggedin() && !isguestuser() && a11y::panel_enabled()) {
            $page[] = $item([$alt, $key('A')], 'display');
        }
        $page[] = $item([$key(get_string('shortcuts_tab', 'theme_epure'))], 'skip');
        $page[] = $item([$key(get_string('shortcuts_esc', 'theme_epure'))], 'escape');

        $groups = [['title' => get_string('shortcuts_page', 'theme_epure'), 'items' => $page]];
        if (quick_search::enabled()) {
            $groups[] = ['title' => get_string('quicksearch', 'theme_epure'), 'items' => [
                $item([$key('↑'), $key('↓')], 'qsmove', true),
                $item([$key(get_string('shortcuts_enter', 'theme_epure'))], 'qsopen'),
                $item([$ctrl, $key(get_string('shortcuts_enter', 'theme_epure'))], 'qsnewtab'),
            ]];
        }
        if (isloggedin() && !isguestuser()) {
            $groups[] = ['title' => get_string('shortcuts_editor', 'theme_epure'), 'items' => [
                $item([$alt, $key('F10')], 'toolbar'),
                $item([$alt, $key('0')], 'editorhelp'),
            ]];
        }
        foreach ($groups as $index => &$group) {
            $group['id'] = 'epure-shortcuts-group-' . $index;
        }
        unset($group);
        return [
            'groups' => $groups,
            'keyenabled' => self::key_enabled(),
            'loggedin' => isloggedin() && !isguestuser(),
        ];
    }
}
