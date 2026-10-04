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
 * Fonts bundled with the theme.
 *
 * All fonts are served by Moodle itself from theme/epure/fonts, so no request
 * leaves the site. They are all licensed under the SIL Open Font License 1.1.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fonts {
    /** @var string Font used when the setting is empty or unknown. */
    public const DEFAULT = 'ibmplexsans';

    /** @var string Setting value for the font uploaded by the administrator. */
    public const CUSTOM = 'custom';

    /** @var string Fallback stack appended to every family. */
    public const FALLBACK = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

    /**
     * Bundled fonts, keyed by setting value.
     *
     * @return array<string, array{family: string, file: string, weights: int[], readability: bool}>
     */
    public static function all(): array {
        return [
            'ibmplexsans' => ['family' => 'IBM Plex Sans', 'file' => 'ibm-plex-sans', 'weights' => [400, 500, 600, 700],
                'readability' => false],
            'inter' => ['family' => 'Inter', 'file' => 'inter', 'weights' => [400, 500, 600, 700], 'readability' => false],
            'sourcesans3' => ['family' => 'Source Sans 3', 'file' => 'source-sans-3', 'weights' => [400, 500, 600, 700],
                'readability' => false],
            'figtree' => ['family' => 'Figtree', 'file' => 'figtree', 'weights' => [400, 500, 600, 700], 'readability' => false],
            'lato' => ['family' => 'Lato', 'file' => 'lato', 'weights' => [400, 700], 'readability' => false],
            'atkinson' => ['family' => 'Atkinson Hyperlegible', 'file' => 'atkinson-hyperlegible', 'weights' => [400, 700],
                'readability' => true],
            'lexend' => ['family' => 'Lexend', 'file' => 'lexend', 'weights' => [400, 500, 600, 700], 'readability' => true],
            'opendyslexic' => ['family' => 'OpenDyslexic', 'file' => 'opendyslexic', 'weights' => [400, 700],
                'readability' => true],
        ];
    }

    /**
     * Returns the definition of a font, falling back to the default one.
     *
     * @param string|null $key Setting value.
     * @return array{family: string, file: string, weights: int[], readability: bool}
     */
    public static function get(?string $key): array {
        $all = self::all();
        return $all[$key] ?? $all[self::DEFAULT];
    }

    /**
     * CSS font-family value for a font, with the fallback stack.
     *
     * @param string|null $key Setting value.
     * @return string
     */
    public static function stack(?string $key): string {
        return '"' . self::get($key)['family'] . '", ' . self::FALLBACK;
    }

    /**
     * SCSS @font-face rules for a font.
     *
     * @param string|null $key Setting value.
     * @return string
     */
    public static function font_face_scss(?string $key): string {
        return self::font_face_css($key, fn($file) => "[[font:theme|{$file}]]");
    }

    /**
     * Font face rules of a bundled font, with the addresses of its files.
     *
     * @param string|null $key Font key.
     * @param callable $url Address of a font file, from its file name.
     * @return string
     */
    public static function font_face_css(?string $key, callable $url): string {
        $font = self::get($key);
        $css = '';
        foreach ($font['weights'] as $weight) {
            $file = $font['file'] . '-latin-' . $weight . '-normal.woff2';
            $css .= "@font-face {\n" .
                "  font-family: \"{$font['family']}\";\n" .
                "  font-style: normal;\n" .
                "  font-weight: {$weight};\n" .
                "  font-display: swap;\n" .
                "  src: url('" . $url($file) . "') format('woff2');\n" .
                "}\n";
        }
        return $css;
    }

    /**
     * SCSS for the font uploaded by the administrator.
     *
     * @param string $family Family name entered by the administrator.
     * @param string[] $urls Font file URLs keyed by weight (400 and 700).
     * @return array{0: string, 1: string} The @font-face rules and the font-family stack.
     */
    public static function custom_scss(string $family, array $urls): array {
        $family = trim(preg_replace('/[^\p{L}\p{N} _-]/u', '', $family)) ?: 'Epure Custom';
        $scss = '';
        foreach ($urls as $weight => $url) {
            $format = substr($url, -6) === '.woff2' ? 'woff2' : 'woff';
            $scss .= "@font-face {\n" .
                "  font-family: \"{$family}\";\n" .
                "  font-style: normal;\n" .
                "  font-weight: {$weight};\n" .
                "  font-display: swap;\n" .
                "  src: url('{$url}') format('{$format}');\n" .
                "}\n";
        }
        return [$scss, '"' . $family . '", ' . self::FALLBACK];
    }

    /**
     * Options for the font setting, grouped labels included.
     *
     * @return array<string, string>
     */
    public static function options(): array {
        $options = [];
        foreach (self::all() as $key => $font) {
            $options[$key] = $font['readability']
                ? get_string('fontreadability', 'theme_epure', $font['family'])
                : $font['family'];
        }
        $options[self::CUSTOM] = get_string('fontcustom', 'theme_epure');
        return $options;
    }
}
