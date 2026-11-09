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
 * The header painted by the custom SCSS (Advanced settings): the rules that give the header (.navbar) a background of
 * their own, its colours, and the text that reads on them. The theme adapts the text and the icons of the header to it
 * when it compiles its stylesheet, and the setting says what it found.
 *
 * Only the plain rules are read: a selector of the header and a background written with colours (#hex, rgb()), not
 * variables nor nested rules.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_header {
    /**
     * The rules of the custom SCSS that paint the header: their selector, the colours read in them, and what keeps the
     * theme from knowing the background for sure (a transparent colour, a variable or a function of colour, a nested
     * rule), for which it only gives a partial check.
     *
     * @param string $scss Custom SCSS.
     * @return array<int, array{selector: string, colours: string[], unsure: string[]}>
     */
    public static function rules(string $scss): array {
        // Without the comments, which may hold braces.
        $scss = (string) preg_replace(['~/\*.*?\*/~s', '~(^|[^:])//[^\n]*~'], ['', '$1'], $scss);
        $rules = [];
        // A rule of the header that holds other rules (SCSS nesting): its background is not read.
        if (preg_match_all('~([^{};]*\.navbar[^{};]*)\{[^{}]*\{~', $scss, $nested)) {
            foreach ($nested[1] as $selector) {
                if (($parts = self::header_selector($selector)) !== null) {
                    $rules[] = ['selector' => $parts, 'colours' => [], 'unsure' => ['nested']];
                }
            }
        }
        if (!preg_match_all('~([^{}]+)\{([^{}]*)\}~', $scss, $matches, PREG_SET_ORDER)) {
            return $rules;
        }
        foreach ($matches as [, $selector, $declarations]) {
            if (($parts = self::header_selector($selector)) === null) {
                continue;
            }
            $colours = [];
            $unsure = [];
            if (preg_match_all('~(?:^|;)\s*background(?:-color|-image)?\s*:([^;]*)~i', $declarations, $values)) {
                foreach ($values[1] as $value) {
                    $colours = array_merge($colours, self::colours($value));
                    if (self::transparent($value)) {
                        $unsure[] = 'transparent';
                    }
                    if (preg_match('~var\(|\$[\w-]|\b(?:hsla?|hwb|lab|lch|oklab|oklch|color-mix|color)\(|url\(~i', $value)) {
                        $unsure[] = 'variable';
                    }
                }
            }
            if ($colours || $unsure) {
                $rules[] = ['selector' => $parts, 'colours' => array_values(array_unique($colours)),
                    'unsure' => array_values(array_unique($unsure))];
            }
        }
        return $rules;
    }

    /**
     * The selector of a rule when it is the header itself, not an element inside it: each selector of the list ends with
     * the navbar.
     *
     * @param string $selector Selector.
     * @return string|null The selector, tidied; null when it is not the header.
     */
    protected static function header_selector(string $selector): ?string {
        $selector = trim((string) preg_replace('~\s+~', ' ', $selector));
        $parts = array_filter(array_map('trim', explode(',', $selector)), fn($part) => $part !== '');
        if (!$parts || array_filter($parts, fn($part) => !preg_match('~\.navbar(\.[\w-]+|\[[^\]]*\])*$~', $part))) {
            return null;
        }
        return implode(', ', $parts);
    }

    /**
     * Whether a value holds a colour that lets the background under it show: transparent, rgba() or a hexadecimal code
     * with an opacity below 1.
     *
     * @param string $value Value of a declaration.
     * @return bool
     */
    protected static function transparent(string $value): bool {
        if (preg_match('~\btransparent\b|#(?:[0-9a-f]{4}|[0-9a-f]{8})\b~i', $value)) {
            return true;
        }
        preg_match_all('~rgba?\([^)]*?[,/]\s*([\d.]+%?)\s*\)~i', $value, $alphas);
        foreach ($alphas[1] as $alpha) {
            $opacity = str_ends_with($alpha, '%') ? (float) $alpha / 100 : (float) $alpha;
            if ($opacity < 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * The colours written in a value: #rgb, #rrggbb and rgb() or rgba() (their transparency read apart, by transparent()).
     *
     * @param string $value Value of a declaration.
     * @return string[] Colours as #RRGGBB.
     */
    public static function colours(string $value): array {
        $colours = [];
        if (
            preg_match_all(
                '~#([0-9a-f]{6}|[0-9a-f]{3})\b|rgba?\(\s*(\d{1,3})[\s,]+(\d{1,3})[\s,]+(\d{1,3})~i',
                $value,
                $found,
                PREG_SET_ORDER
            )
        ) {
            foreach ($found as $match) {
                $colour = !empty($match[1]) ? palette::normalise('#' . $match[1])
                    : palette::to_hex([(int) $match[2], (int) $match[3], (int) $match[4]]);
                if ($colour) {
                    $colours[] = $colour;
                }
            }
        }
        return $colours;
    }

    /**
     * The SCSS that adapts the text and the icons of the header to each background of the custom SCSS, under the same
     * selector (so in the same modes): white or the dark ink, whichever reads on its least favourable colour.
     *
     * @param string $scss Custom SCSS.
     * @return string SCSS, empty without such a rule.
     */
    public static function adapt_scss(string $scss): string {
        $out = '';
        foreach (self::rules($scss) as $rule) {
            if (!$rule['colours']) {
                continue;
            }
            $on = palette::on_stops($rule['colours'])['on'];
            $out .= $rule['selector'] . ' { @include epure-header-text(' . $on . ', '
                . ($on === palette::SURFACE_LIGHT ? 'true' : 'false') . "); }\n";
        }
        return $out;
    }

    /**
     * What the setting of the custom SCSS says of the header: the colours found, the text chosen and the contrast it
     * reaches, with a warning below 4.5:1.
     *
     * @param string $scss Custom SCSS.
     * @return string Plain text, empty without such a rule.
     */
    public static function report(string $scss): string {
        $lines = [];
        foreach (self::rules($scss) as $rule) {
            if ($rule['unsure']) {
                // A background the theme cannot know for sure: a partial check, never a promise of contrast.
                $lines[] = get_string('customheaderpartial', 'theme_epure', (object) [
                    'selector' => $rule['selector'],
                    'reasons' => implode(', ', array_map(fn($reason) => get_string(
                        'customheader_' . $reason,
                        'theme_epure'
                    ), $rule['unsure'])),
                ]);
                continue;
            }
            $best = palette::on_stops($rule['colours']);
            $a = (object) [
                'selector' => $rule['selector'],
                'colours' => implode(' → ', $rule['colours']),
                'text' => get_string(
                    $best['on'] === palette::SURFACE_LIGHT ? 'customheaderwhite' : 'customheaderdark',
                    'theme_epure'
                ),
                'contrast' => format_float($best['contrast'], 1),
            ];
            $lines[] = get_string(
                $best['contrast'] >= palette::AA_TEXT ? 'customheaderok' : 'customheaderlow',
                'theme_epure',
                $a
            );
        }
        return implode(' ', $lines);
    }
}
