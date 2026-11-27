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

namespace theme_epure\output;

use theme_epure\a11y;

/**
 * The Moodle app (db/mobile.php): the display preferences of « Aa » of the user applied to the app, and their page in
 * its menu, the same choices as the panel of the site, saved in the same profile.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * When the app opens the site: the script that puts the classes of the preferences on the app.
     *
     * @param array $args Arguments of the app.
     * @return array
     */
    public static function mobile_init(array $args): array {
        global $CFG;
        $script = file_get_contents($CFG->dirroot . '/theme/epure/mobile/init.js');
        $script = str_replace('INITIAL_CLASSES', json_encode(a11y::html_classes()), $script);
        return ['templates' => [], 'javascript' => $script, 'otherdata' => '', 'files' => []];
    }

    /**
     * The page « Display preferences » of the app.
     *
     * @param array $args Arguments of the app.
     * @return array
     */
    public static function mobile_preferences(array $args): array {
        global $CFG;
        $context = a11y::panel_context();
        $preferences = a11y::preferences();
        // The choices of the user, for the fields of the page; the switches as true or false.
        $otherdata = [
            'text' => (string) $preferences['text'],
            'font' => $preferences['font'],
            'scheme' => $preferences['scheme'],
        ];
        $switches = [];
        foreach (['spacing', 'lines', 'underline', 'motion', 'focusring'] as $switch) {
            $otherdata[$switch] = $preferences[$switch] ? 'true' : '';
            $switches[] = ['name' => $switch, 'label' => get_string('a11y' . $switch, 'theme_epure'),
                'help' => get_string('a11y' . $switch . '_help', 'theme_epure')];
        }
        $html = '<ion-list>'
            . self::select('text', get_string('a11ytext', 'theme_epure'), $context['sizes'])
            . self::select('font', get_string('a11yfont', 'theme_epure'), $context['fonts'])
            . self::select('scheme', get_string('a11yscheme', 'theme_epure'), $context['schemes']);
        foreach ($switches as $switch) {
            $html .= '<ion-item><ion-toggle [(ngModel)]="CONTENT_OTHERDATA.' . $switch['name'] . '"'
                . ' (ionChange)="epureSave(\'' . $switch['name'] . '\', CONTENT_OTHERDATA.' . $switch['name'] . ')">'
                . '<p>' . s($switch['label']) . '</p><p class="ion-text-wrap"><small>' . s($switch['help'])
                . '</small></p></ion-toggle></ion-item>';
        }
        $html .= '</ion-list>';
        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'javascript' => file_get_contents($CFG->dirroot . '/theme/epure/mobile/preferences.js'),
            'otherdata' => $otherdata,
            'files' => [],
        ];
    }

    /**
     * A list of choices of the page, saved when it changes.
     *
     * @param string $name Name of the preference.
     * @param string $label Its label.
     * @param array $options Its choices: value and label.
     * @return string Markup of the app.
     */
    protected static function select(string $name, string $label, array $options): string {
        $html = '<ion-item><ion-select label="' . s($label) . '" labelPlacement="stacked" interface="action-sheet"'
            . ' [(ngModel)]="CONTENT_OTHERDATA.' . $name . '" (ionChange)="epureSave(\'' . $name . '\', CONTENT_OTHERDATA.'
            . $name . ')">';
        foreach ($options as $option) {
            $html .= '<ion-select-option value="' . s((string) $option['value']) . '">' . s($option['label'])
                . '</ion-select-option>';
        }
        return $html . '</ion-select></ion-item>';
    }
}
