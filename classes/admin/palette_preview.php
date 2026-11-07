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

namespace theme_epure\admin;

use html_writer;

/**
 * The palette computed from the colours of the settings, under them: a swatch and the code of each colour, with its
 * role, to read only. Built without a template, as the tree of the administration is also built without output (CLI).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class palette_preview {
    /**
     * HTML of the preview.
     *
     * @param array $groups Groups of colours, from {@see \theme_epure\palette::preview()}.
     * @return string
     */
    public static function html(array $groups): string {
        $html = html_writer::tag('p', get_string('palette_desc', 'theme_epure'), ['class' => 'small text-muted']);
        foreach ($groups as $group) {
            $items = '';
            foreach ($group['colours'] as $colour) {
                $hex = s($colour['hex']);
                $items .= html_writer::tag(
                    'li',
                    html_writer::span('', 'epure-palette-swatch', ['style' => 'background-color: ' . $hex, 'aria-hidden' => 'true'])
                    . html_writer::span(s($colour['label']), 'epure-palette-label')
                    . html_writer::tag('code', $hex, ['class' => 'epure-palette-hex'])
                );
            }
            $html .= html_writer::div(
                html_writer::tag('p', s($group['name']), ['class' => 'epure-palette-group'])
                    . html_writer::tag('ul', $items, ['class' => 'epure-palette-list']),
                'epure-palette'
            );
        }
        return $html;
    }
}
