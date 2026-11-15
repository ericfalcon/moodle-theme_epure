<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * « What is new in Épure », for the administrator: after an update of the theme, on the home of the administration,
 * what changes for the site since the version installed before (the notes of update of the README, one line by
 * version, in English or in French), with a link to all the changes; closed once read, by each administrator.
 *
 * The version installed before is kept at each update (db/upgrade.php); for a site updated from a version before
 * this one, it is found from the version numbers of the releases. A new site shows nothing.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class whatsnew {
    /** @var string Preference of the user: the release whose news they closed. */
    public const PREFERENCE = 'theme_epure_whatsnew';

    /** @var int Versions shown at most: the rest is in the changes. */
    public const MAX = 6;

    /** @var string All the changes. */
    public const CHANGES_URL = 'https://github.com/ericfalcon/moodle-theme_epure/blob/main/CHANGES.md';

    /** @var string[] The releases since 1.0.0 by their version number, to find the one installed before an update. */
    protected const RELEASES = [
        2026110400 => '1.0.0',
        2026110401 => '1.0.1',
        2026110402 => '1.0.2',
        2026110500 => '1.1.0',
        2026110501 => '1.2.0',
        2026110502 => '1.3.0',
        2026110503 => '1.4.0',
        2026110504 => '1.4.1',
        2026110505 => '1.5.0',
        2026110600 => '1.5.1',
        2026110601 => '1.5.2',
        2026110602 => '1.5.3',
        2026110603 => '1.6.0',
        2026110604 => '1.6.1',
        2026110700 => '1.6.2',
        2026110701 => '1.7.0',
        2026110900 => '1.8.0',
        2026110901 => '1.9.0',
        2026110902 => '1.10.0',
        2026110903 => '1.11.0',
        2026110904 => '1.12.0',
        2026110905 => '1.13.0',
        2026110906 => '1.14.0',
        2026110907 => '1.15.0',
        2026111000 => '1.15.1',
        2026111200 => '1.15.2',
        2026111201 => '1.16.0',
        2026111202 => '1.17.0',
        2026111400 => '1.17.1',
        2026111500 => '1.17.2',
        2026111501 => '1.18.0',
        2026111600 => '1.19.0',
        2026111700 => '1.20.0',
        2026111800 => '1.21.0',
        2026111900 => '1.22.0',
        2026112000 => '1.23.0',
        2026112100 => '1.24.0',
    ];

    /**
     * The release of a version number: the last release at or before it; null before 1.0.0.
     *
     * @param int $version Version number.
     * @return string|null
     */
    public static function release_of(int $version): ?string {
        $release = null;
        foreach (self::RELEASES as $number => $name) {
            if ($number <= $version) {
                $release = $name;
            }
        }
        return $release;
    }

    /**
     * The release of the theme installed now.
     *
     * @return string
     */
    public static function current(): string {
        global $CFG;
        $plugin = new \stdClass();
        include($CFG->dirroot . '/theme/epure/version.php');
        return (string) $plugin->release;
    }

    /**
     * At each update of the theme: keeps the release installed before, from which the news are told.
     *
     * @param int $oldversion Version number installed before.
     */
    public static function upgraded(int $oldversion): void {
        $before = get_config('theme_epure', 'currentrelease') ?: self::release_of($oldversion);
        $current = self::current();
        if ($before && version_compare($before, $current, '<')) {
            set_config('whatsnewbase', $before, 'theme_epure');
        }
        set_config('currentrelease', $current, 'theme_epure');
    }

    /**
     * At the installation: the release installed, and no news.
     */
    public static function installed(): void {
        set_config('currentrelease', self::current(), 'theme_epure');
        unset_config('whatsnewbase', 'theme_epure');
    }

    /**
     * The notes of update of the versions after the one installed before, up to the one installed now, the most
     * recent first, from the README in the language of the user (French, else English).
     *
     * @param string $base Release installed before.
     * @param string $current Release installed now.
     * @return array[] Each with version and text.
     */
    public static function notes(string $base, string $current): array {
        global $CFG;
        $french = str_starts_with(current_language(), 'fr');
        $file = $CFG->dirroot . '/theme/epure/' . ($french ? 'README.fr.md' : 'README.md');
        $notes = [];
        foreach (is_readable($file) ? file($file, FILE_IGNORE_NEW_LINES) : [] as $line) {
            if (!preg_match('~^- \*\*(\d+\.\d+\.\d+)\*\*\s*:\s*(.+)$~u', $line, $m)) {
                continue;
            }
            [, $version, $text] = $m;
            if (version_compare($version, $base, '>') && version_compare($version, $current, '<=')) {
                // The words of the list of the README, without its marks; the sentence said by every version left out.
                $said = '~^(no new setting; upgrade the site\.|aucun nouveau réglage ; mettez le site à jour\.)\s*~u';
                $text = preg_replace($said, '', $text);
                $text = str_replace(['**', chr(96)], '', $text);
                $text = trim($text);
                $text = \core_text::strtoupper(\core_text::substr($text, 0, 1)) . \core_text::substr($text, 1);
                $notes[$version] = ['version' => $version, 'text' => $text];
            }
        }
        uksort($notes, fn($a, $b) => version_compare($b, $a));
        return array_values($notes);
    }

    /**
     * Context of the news on the home of the administration: null when there is nothing new, or when the user
     * closed them.
     *
     * @return array|null
     */
    public static function export(): ?array {
        $base = (string) get_config('theme_epure', 'whatsnewbase');
        $current = (string) get_config('theme_epure', 'currentrelease');
        if (
            $base === '' || $current === '' || get_user_preferences(self::PREFERENCE, '') === $current
                || !has_capability('moodle/site:config', \context_system::instance())
        ) {
            return null;
        }
        $notes = self::notes($base, $current);
        if (!$notes) {
            return null;
        }
        return [
            'title' => get_string('whatsnew_title', 'theme_epure', (object) ['from' => $base, 'to' => $current]),
            'notes' => array_slice($notes, 0, self::MAX),
            'more' => count($notes) > self::MAX,
            'changesurl' => self::CHANGES_URL,
            'dismissurl' => (new \moodle_url('/theme/epure/whatsnew.php', ['sesskey' => sesskey()]))->out(false),
        ];
    }

    /**
     * Closes the news for the current user, until the next update.
     */
    public static function dismiss(): void {
        set_user_preference(self::PREFERENCE, (string) get_config('theme_epure', 'currentrelease'));
    }
}
