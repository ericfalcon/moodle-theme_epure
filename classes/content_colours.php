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
 * The colours written by the teachers in their texts (the colour of the text and of its background, in the editor):
 * the texts whose colour does not stand out enough from their background (WCAG 1.4.3, RGAA 3.2). Only the colours
 * written in the text are read; around them, the dark text of the theme on a white background. The same rules as
 * theme_epure/content_check, in the editor.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_colours {
    /** @var array<string, string> The named colours of CSS most written, as #RRGGBB. */
    public const NAMED = [
        'black' => '#000000', 'white' => '#FFFFFF', 'red' => '#FF0000', 'green' => '#008000', 'blue' => '#0000FF',
        'yellow' => '#FFFF00', 'orange' => '#FFA500', 'gray' => '#808080', 'grey' => '#808080', 'silver' => '#C0C0C0',
        'lightgray' => '#D3D3D3', 'lightgrey' => '#D3D3D3', 'darkgray' => '#A9A9A9', 'darkgrey' => '#A9A9A9',
        'purple' => '#800080', 'navy' => '#000080', 'maroon' => '#800000', 'lime' => '#00FF00', 'aqua' => '#00FFFF',
        'cyan' => '#00FFFF', 'fuchsia' => '#FF00FF', 'magenta' => '#FF00FF', 'teal' => '#008080', 'olive' => '#808000',
        'pink' => '#FFC0CB', 'gold' => '#FFD700', 'brown' => '#A52A2A', 'beige' => '#F5F5DC', 'ivory' => '#FFFFF0',
        'lightyellow' => '#FFFFE0', 'lightblue' => '#ADD8E6', 'lightgreen' => '#90EE90', 'skyblue' => '#87CEEB',
        'darkblue' => '#00008B', 'darkred' => '#8B0000', 'darkgreen' => '#006400', 'violet' => '#EE82EE',
    ];

    /** @var float Contrast of a large text: 24 px, or 18.66 px in bold, and more (WCAG 1.4.3). */
    public const LARGE_TEXT = 3.0;

    /**
     * The colour written in a value, as #RRGGBB: none for a transparent colour, a variable or what is not a colour.
     *
     * @param string $value Value of a declaration (« #e03e2d », « rgb(224, 62, 45) », « yellow »…).
     * @return string|null
     */
    public static function colour(string $value): ?string {
        $value = strtolower(trim(preg_replace('/!important/i', '', $value)));
        if ($value === '' || str_contains($value, 'var(') || str_contains($value, 'transparent')) {
            return null;
        }
        if (preg_match('~rgba\([^)]*,\s*([\d.]+)\s*\)~', $value, $alpha) && (float) $alpha[1] < 1) {
            return null;
        }
        if (preg_match('~#(?:[0-9a-f]{4}|[0-9a-f]{8})\b~', $value)) {
            return null;
        }
        $colours = custom_header::colours($value);
        if ($colours) {
            return $colours[0];
        }
        preg_match('/^[a-z]+/', $value, $name);
        return self::NAMED[$name[0] ?? ''] ?? null;
    }

    /**
     * A declaration of the inline style of an element.
     *
     * @param \DOMElement $element Element.
     * @param string $pattern Property, as a regular expression (« color », « background(?:-color)? »).
     * @return string|null Its value.
     */
    protected static function declared(\DOMElement $element, string $pattern): ?string {
        $style = $element->getAttribute('style');
        if ($style !== '' && preg_match('~(?:^|;)\s*(?:' . $pattern . ')\s*:\s*([^;]+)~i', $style, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * The texts whose colour does not stand out enough from their background: counted once for each element whose
     * colour (of the text or of the background) makes them so.
     *
     * @param \DOMXPath $xpath Document of the text.
     * @return int
     */
    public static function low_contrast(\DOMXPath $xpath): int {
        $found = [];
        foreach ($xpath->query('//body//text()[normalize-space()]') as $node) {
            $fg = null;
            $bg = null;
            $owner = null;
            $large = false;
            for ($element = $node->parentNode; $element instanceof \DOMElement; $element = $element->parentNode) {
                if ($element->nodeName === 'body') {
                    break;
                }
                if ($fg === null) {
                    $value = self::declared($element, 'color')
                        ?? ($element->nodeName === 'font' ? $element->getAttribute('color') : null);
                    if ($value !== null && ($colour = self::colour($value))) {
                        $fg = $colour;
                        $owner ??= $element;
                    }
                }
                if ($bg === null) {
                    $value = self::declared($element, 'background(?:-color)?') ?? ($element->getAttribute('bgcolor') ?: null);
                    if ($value !== null && ($colour = self::colour($value))) {
                        $bg = $colour;
                        $owner ??= $element;
                    }
                }
                $large = $large || in_array($element->nodeName, ['h1', 'h2', 'h3'], true) || self::large($element);
            }
            if (!$owner) {
                continue;
            }
            if (self::low($fg ?? palette::INK, $bg ?? palette::SURFACE_LIGHT, $large)) {
                $found[$owner->getNodePath()] = true;
            }
        }
        return count($found);
    }

    /**
     * Whether a text does not stand out enough from its background: 4.5:1, 3:1 for a large text.
     *
     * @param string $fg Colour of the text, #RRGGBB.
     * @param string $bg Colour of the background, #RRGGBB.
     * @param bool $large Whether the text is large.
     * @return bool
     */
    public static function low(string $fg, string $bg, bool $large): bool {
        return palette::contrast($fg, $bg) < ($large ? self::LARGE_TEXT : palette::AA_TEXT);
    }

    /**
     * Whether an element writes its text large: 24 px (18 pt) and more, or 18.66 px (14 pt) and more in bold.
     *
     * @param \DOMElement $element Element.
     * @return bool
     */
    protected static function large(\DOMElement $element): bool {
        $size = self::declared($element, 'font-size');
        if ($size === null || !preg_match('/^\s*([\d.]+)\s*(px|pt)/i', $size, $match)) {
            return false;
        }
        $px = (float) $match[1] * (strtolower($match[2]) === 'pt' ? 4 / 3 : 1);
        $bold = in_array($element->nodeName, ['strong', 'b'], true)
            || preg_match('/font-weight\s*:\s*(bold|[6-9]00)/i', $element->getAttribute('style'));
        return $px >= 24 || ($bold && $px >= 18.66);
    }
}
