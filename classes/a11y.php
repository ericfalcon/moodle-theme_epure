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
 * Display preferences of each user (button « Aa » in the header), saved in their profile.
 *
 * They are applied as classes on the html element, read by the theme's styles, so that they
 * hold on every page from the first paint: text size, reading font, text spacing, enhanced
 * contrast, underlined links and reduced motion.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class a11y {
    /** @var int[] Text sizes, in percent. */
    public const TEXT_SIZES = [90, 100, 115, 130];

    /** @var string[] Reading fonts, besides the font of the theme (empty value). */
    public const FONTS = ['atkinson', 'opendyslexic'];

    /** @var string[] Colour schemes a user can choose, besides the one of the site (empty value). */
    public const SCHEMES = ['light', 'dark', 'auto'];

    /** @var string[] Preferences that are on or off. */
    public const SWITCHES = ['spacing', 'contrast', 'underline', 'motion'];

    /**
     * Definitions of the preferences, for Moodle's user preference API.
     *
     * @return array[]
     */
    public static function definitions(): array {
        $own = [\core_user::class, 'is_current_user'];
        $definitions = [
            'theme_epure_a11y_text' => ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED, 'default' => 100,
                'choices' => self::TEXT_SIZES, 'permissioncallback' => $own],
            'theme_epure_a11y_font' => ['type' => PARAM_ALPHA, 'null' => NULL_NOT_ALLOWED, 'default' => '',
                'choices' => array_merge([''], self::FONTS), 'permissioncallback' => $own],
            'theme_epure_a11y_scheme' => ['type' => PARAM_ALPHA, 'null' => NULL_NOT_ALLOWED, 'default' => '',
                'choices' => array_merge([''], self::SCHEMES), 'permissioncallback' => $own],
        ];
        foreach (self::SWITCHES as $switch) {
            $definitions['theme_epure_a11y_' . $switch] = ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
                'default' => 0, 'choices' => [0, 1], 'permissioncallback' => $own];
        }
        // Reading mode of the pages of the activities, without the drawers.
        $definitions['theme_epure_focus'] = ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
            'default' => 0, 'choices' => [0, 1], 'permissioncallback' => $own];
        return $definitions;
    }

    /**
     * Preferences of the current user.
     *
     * @return array{text: int, font: string, scheme: string, spacing: bool, contrast: bool, underline: bool, motion: bool}
     */
    public static function preferences(): array {
        $enabled = isloggedin() && !isguestuser();
        $defaults = self::site_defaults();
        // A preference the user never chose is the one of the site.
        $preference = fn(string $name) => $enabled ? get_user_preferences('theme_epure_a11y_' . $name, null) : null;
        $text = (int) ($preference('text') ?? $defaults['text']);
        $font = (string) ($preference('font') ?? $defaults['font']);
        $scheme = (string) ($preference('scheme') ?? '');
        $preferences = [
            'text' => in_array($text, self::TEXT_SIZES, true) ? $text : 100,
            'font' => in_array($font, self::FONTS, true) ? $font : '',
            'scheme' => in_array($scheme, self::SCHEMES, true) ? $scheme : '',
        ];
        foreach (self::SWITCHES as $switch) {
            $preferences[$switch] = (bool) ($preference($switch) ?? $defaults[$switch]);
        }
        return $preferences;
    }

    /**
     * Display of the site for the users who did not choose theirs, and for the visitors: the settings « Display by
     * default » of the theme.
     *
     * @return array{text: int, font: string, spacing: bool, contrast: bool, underline: bool, motion: bool}
     */
    public static function site_defaults(): array {
        $config = get_config('theme_epure');
        $text = (int) ($config->preftext ?? 100);
        $font = (string) ($config->preffont ?? '');
        $defaults = [
            'text' => in_array($text, self::TEXT_SIZES, true) ? $text : 100,
            'font' => in_array($font, self::FONTS, true) ? $font : '',
        ];
        foreach (self::SWITCHES as $switch) {
            $defaults[$switch] = !empty($config->{'pref' . $switch});
        }
        return $defaults;
    }

    /**
     * Whether the users get the button « Aa » of the display preferences.
     *
     * @return bool
     */
    public static function panel_enabled(): bool {
        return get_config('theme_epure', 'prefpanel') !== '0';
    }

    /**
     * Classes of the html element for the preferences of the current user.
     *
     * @return string Classes separated by spaces, empty with the default display.
     */
    public static function html_classes(): string {
        $preferences = self::preferences();
        $classes = [];
        if ($preferences['text'] !== 100) {
            $classes[] = 'epure-a11y-text-' . $preferences['text'];
        }
        if ($preferences['font'] !== '') {
            $classes[] = 'epure-a11y-font-' . $preferences['font'];
        }
        foreach (self::SWITCHES as $switch) {
            if ($preferences[$switch]) {
                $classes[] = 'epure-a11y-' . $switch;
            }
        }
        if ($class = self::scheme_class($preferences['scheme'] ?: self::default_scheme())) {
            $classes[] = $class;
        }
        // The reading mode only changes the pages of the activities, whose body has the class epure-activity.
        if (isloggedin() && !isguestuser() && get_user_preferences('theme_epure_focus', 0)) {
            $classes[] = 'epure-focus';
        }
        return implode(' ', $classes);
    }

    /**
     * Colour scheme of the page when the user did not choose one: the one of their IOMAD company, else of the site.
     *
     * @return string light, dark or auto.
     */
    public static function default_scheme(): string {
        return company_style::dark_mode();
    }

    /**
     * Class of the html element for a colour scheme.
     *
     * @param string $scheme light, dark or auto.
     * @return string The class, empty for the light scheme.
     */
    public static function scheme_class(string $scheme): string {
        return ['dark' => 'epure-dark', 'auto' => 'epure-dark-auto'][$scheme] ?? '';
    }

    /**
     * Context of the template theme_epure/a11y_panel.
     *
     * @return array
     */
    public static function panel_context(): array {
        $preferences = self::preferences();
        $sizes = [];
        foreach (self::TEXT_SIZES as $size) {
            $sizes[] = ['value' => $size, 'label' => get_string('a11ytext_' . $size, 'theme_epure'),
                'checked' => $size === $preferences['text']];
        }
        $fonts = [
            ['value' => '', 'label' => get_string('a11yfont_theme', 'theme_epure'), 'checked' => $preferences['font'] === ''],
        ];
        foreach (self::FONTS as $font) {
            $fonts[] = ['value' => $font, 'label' => fonts::get($font)['family'], 'checked' => $font === $preferences['font'],
                'class' => 'epure-a11y-sample-' . $font];
        }
        $default = get_string('a11yscheme_' . self::default_scheme(), 'theme_epure');
        $schemes = [['value' => '', 'label' => get_string('a11yscheme_site', 'theme_epure', $default),
            'checked' => $preferences['scheme'] === '']];
        foreach (self::SCHEMES as $scheme) {
            $schemes[] = ['value' => $scheme, 'label' => get_string('a11yscheme_' . $scheme, 'theme_epure'),
                'checked' => $scheme === $preferences['scheme']];
        }
        $switches = [];
        foreach (self::SWITCHES as $switch) {
            $switches[] = ['name' => $switch, 'label' => get_string('a11y' . $switch, 'theme_epure'),
                'help' => get_string('a11y' . $switch . '_help', 'theme_epure'), 'checked' => $preferences[$switch]];
        }
        return ['sizes' => $sizes, 'fonts' => $fonts, 'schemes' => $schemes, 'switches' => $switches,
            'defaultscheme' => self::default_scheme()];
    }
}
