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
 * Accessible colour palette derived from a single brand colour.
 *
 * Every colour the theme needs (hover, tinted backgrounds, text and link colour)
 * is computed from the brand colour, and adjusted until it reaches the WCAG 2.2
 * AA contrast ratios against the surfaces it is drawn on.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class palette {
    /** @var string Default brand colour. */
    public const DEFAULT_BRAND = '#2559A8';

    /** @var string Surface colour in light mode. */
    public const SURFACE_LIGHT = '#FFFFFF';

    /** @var string Surface colour in dark mode. */
    public const SURFACE_DARK = '#171B22';

    /** @var string Dark ink used on light fills. */
    public const INK = '#111418';

    /** @var float WCAG AA ratio for normal text. */
    public const AA_TEXT = 4.5;

    /** @var float WCAG AA ratio for large text and user interface components. */
    public const AA_UI = 3.0;

    /**
     * Normalises a colour to the #RRGGBB form.
     *
     * Accepts #RGB, #RRGGBB, with or without the leading hash, in any case.
     *
     * @param string|null $colour The colour to normalise.
     * @return string|null The normalised colour, or null when the value is not a hex colour.
     */
    public static function normalise(?string $colour): ?string {
        if ($colour === null || !preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', trim($colour), $matches)) {
            return null;
        }
        $hex = $matches[1];
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return '#' . strtoupper($hex);
    }

    /**
     * Converts a #RRGGBB colour to its red, green and blue components.
     *
     * @param string $hex Normalised colour.
     * @return int[] Three integers between 0 and 255.
     */
    public static function to_rgb(string $hex): array {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * Converts red, green and blue components to a #RRGGBB colour.
     *
     * @param float[] $rgb Three values, clamped to 0..255.
     * @return string
     */
    public static function to_hex(array $rgb): string {
        $hex = '#';
        foreach ($rgb as $value) {
            $hex .= sprintf('%02X', (int) round(max(0, min(255, $value))));
        }
        return $hex;
    }

    /**
     * Mixes two colours.
     *
     * @param string $from Colour at ratio 0.
     * @param string $to Colour at ratio 1.
     * @param float $ratio Amount of the second colour, between 0 and 1.
     * @return string
     */
    public static function mix(string $from, string $to, float $ratio): string {
        $a = self::to_rgb($from);
        $b = self::to_rgb($to);
        $mixed = [];
        foreach ($a as $i => $value) {
            $mixed[] = $value + ($b[$i] - $value) * $ratio;
        }
        return self::to_hex($mixed);
    }

    /**
     * Relative luminance as defined by WCAG.
     *
     * @param string $hex Normalised colour.
     * @return float
     */
    public static function luminance(string $hex): float {
        $weights = [0.2126, 0.7152, 0.0722];
        $luminance = 0.0;
        foreach (self::to_rgb($hex) as $i => $channel) {
            $channel /= 255;
            $channel = $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
            $luminance += $channel * $weights[$i];
        }
        return $luminance;
    }

    /**
     * WCAG contrast ratio between two colours.
     *
     * @param string $a First colour.
     * @param string $b Second colour.
     * @return float Between 1 and 21.
     */
    public static function contrast(string $a, string $b): float {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Moves a colour towards a target until it reaches a contrast ratio against a background.
     *
     * @param string $base Starting colour.
     * @param string $target Colour to move towards (usually black or white).
     * @param string $against Background the result is drawn on.
     * @param float $minimum Ratio to reach.
     * @return array{0: string, 1: bool} The colour, and whether it had to be adjusted.
     */
    public static function towards(string $base, string $target, string $against, float $minimum): array {
        for ($step = 0; $step <= 25; $step++) {
            $colour = self::mix($base, $target, $step / 25);
            if (self::contrast($colour, $against) >= $minimum) {
                return [$colour, $step > 0];
            }
        }
        return [$target, true];
    }

    /**
     * Picks white or dark ink, whichever reads best on a fill.
     *
     * @param string $fill Background colour.
     * @return string
     */
    public static function on_colour(string $fill): string {
        return self::contrast(self::SURFACE_LIGHT, $fill) >= self::contrast(self::INK, $fill) ? self::SURFACE_LIGHT : self::INK;
    }

    /**
     * Derives the full palette from a brand colour.
     *
     * @param string|null $brand Brand colour; the default is used when it is not a valid colour.
     * @param bool $dark Whether to derive the dark mode variant.
     * @return array<string, string|float|bool> Keys: brand, fill, hover, on, text, soft, soft2,
     *     textadjusted, overlayalpha, oncontrast, textcontrast.
     */
    public static function derive(?string $brand, bool $dark = false): array {
        $brand = self::normalise($brand) ?? self::DEFAULT_BRAND;
        $surface = $dark ? self::SURFACE_DARK : self::SURFACE_LIGHT;

        // In dark mode, fills must stand out from the surface (3:1 for interface components).
        $fill = $dark ? self::towards($brand, '#FFFFFF', $surface, self::AA_UI)[0] : $brand;
        $on = self::on_colour($fill);

        $soft = $dark ? self::mix($surface, $brand, 0.2) : self::mix($brand, '#FFFFFF', 0.9);
        $soft2 = $dark ? self::mix($surface, $brand, 0.32) : self::mix($brand, '#FFFFFF', 0.8);

        // Brand-coloured text must stay readable on the most tinted background it is drawn on.
        [$text, $adjusted] = self::towards($brand, $dark ? '#FFFFFF' : '#000000', $soft2, self::AA_TEXT);

        if ($dark) {
            $hover = self::mix($fill, '#FFFFFF', 0.12);
        } else {
            $hover = self::mix($fill, '#000000', $on === self::SURFACE_LIGHT ? 0.14 : 0.08);
        }

        // Opacity of the brand-coloured veil over the login image: the lowest that keeps the text
        // readable (AA) whatever the image underneath, from pure black to pure white.
        $overlay = 0.95;
        for ($alpha = 0.7; $alpha <= 0.951; $alpha += 0.01) {
            $worst = min(
                self::contrast($on, self::mix($fill, '#000000', 1 - $alpha)),
                self::contrast($on, self::mix($fill, '#FFFFFF', 1 - $alpha))
            );
            if ($worst >= self::AA_TEXT) {
                $overlay = round($alpha, 2);
                break;
            }
        }

        return [
            'brand' => $brand,
            'fill' => $fill,
            'hover' => $hover,
            'on' => $on,
            'text' => $text,
            'soft' => $soft,
            'soft2' => $soft2,
            'textadjusted' => $adjusted,
            'overlayalpha' => $overlay,
            'oncontrast' => round(self::contrast($on, $fill), 2),
            'textcontrast' => round(self::contrast($text, $surface), 2),
        ];
    }
}
