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

namespace theme_epure\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service of the live preview of the colours (theme_epure/palette_live): the colours the theme computes from a brand
 * colour, an accent colour and a style of header, as they are typed, before they are saved. It only computes colours.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class palette_preview extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'brand' => new external_value(PARAM_RAW_TRIMMED, 'Brand colour, empty for the one of the site', VALUE_DEFAULT, ''),
            'accent' => new external_value(PARAM_RAW_TRIMMED, 'Accent colour, empty for the brand colour', VALUE_DEFAULT, ''),
            'headerstyle' => new external_value(PARAM_ALPHA, 'Style of the header: light, brand or gradient', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * The colours of the preview, and the swatches of the palette.
     *
     * @param string $brand Brand colour.
     * @param string $accent Accent colour.
     * @param string $headerstyle Style of the header.
     * @return array
     */
    public static function execute(string $brand = '', string $accent = '', string $headerstyle = ''): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'brand' => $brand,
            'accent' => $accent,
            'headerstyle' => $headerstyle,
        ]);
        self::validate_context(\context_system::instance());
        // Empty: the colours of the site, as the forms of the entities and the companies show them.
        $brand = \theme_epure\palette::normalise($params['brand'])
            ?? \theme_epure\palette::normalise(get_config('theme_epure', 'brandcolor'));
        $accent = \theme_epure\palette::normalise($params['accent'])
            ?? \theme_epure\palette::normalise(get_config('theme_epure', 'accentcolor')) ?? $brand;
        $style = in_array($params['headerstyle'], \theme_epure\company_style::HEADER_STYLES, true) ? $params['headerstyle']
            : ((string) get_config('theme_epure', 'headerstyle') ?: 'light');
        $vars = [];
        foreach (\theme_epure\palette::preview_vars($brand, $accent, $style) as $name => $value) {
            $vars[] = ['name' => $name, 'value' => $value];
        }
        return [
            'vars' => $vars,
            'swatches' => \theme_epure\admin\palette_preview::html(\theme_epure\palette::preview($brand, $accent, $style)),
        ];
    }

    /**
     * Result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'vars' => new external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_ALPHANUMEXT, 'Custom property, without --'),
                'value' => new external_value(PARAM_RAW, 'Colour'),
            ])),
            'swatches' => new external_value(PARAM_RAW, 'Swatches of the palette, HTML'),
        ]);
    }
}
