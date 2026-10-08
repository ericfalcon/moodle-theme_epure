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
 * Colours of the activity icons: those of Moodle, one per purpose (assessment, content, communication…),
 * or the colour of the brand, of the IOMAD company of the user with IOMAD.
 *
 * Moodle colours the icons with a CSS filter per purpose. With the brand, the theme writes in the page an SVG
 * filter that paints the icons in the readable brand colour (light and dark mode), computed for the company:
 * the CSS points the icons to it.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_icons {
    /** @var string[] Choices: the colour of the brand, or Moodle's colours by purpose. */
    public const CHOICES = ['brand', 'moodle'];

    /**
     * The colours of the icons of the current page: the choice of the company of the user, else the site setting.
     *
     * @return string brand or moodle.
     */
    public static function current(): string {
        $company = company_style::current_company();
        if ($company && ($choice = company_style::settings((int) $company->id)['activityicons'] ?? '')) {
            return $choice;
        }
        return get_config('theme_epure', 'activityicons') === 'moodle' ? 'moodle' : 'brand';
    }

    /**
     * The SVG filters painting the icons in the brand colour, light and dark, or nothing with Moodle's colours.
     *
     * @return string HTML.
     */
    public static function filters(): string {
        if (during_initial_install() || self::current() !== 'brand') {
            return '';
        }
        $brand = company_style::page_brand();
        $filter = function (string $id, string $colour): string {
            [$r, $g, $b] = array_map(fn($value) => round($value / 255, 4), palette::to_rgb($colour));
            return '<filter id="' . $id . '" color-interpolation-filters="sRGB">'
                . '<feColorMatrix type="matrix" values="0 0 0 0 ' . $r . ' 0 0 0 0 ' . $g . ' 0 0 0 0 ' . $b . ' 0 0 0 1 0"/>'
                . '</filter>';
        };
        return '<svg class="epure-icon-filters" width="0" height="0" aria-hidden="true" focusable="false">'
            . $filter('epure-icons-light', palette::derive($brand)['text'])
            . $filter('epure-icons-dark', palette::derive($brand, true)['text'])
            . '</svg>';
    }
}
