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
 * hold on every page from the first paint: text size, reading font, text spacing, shorter lines,
 * enhanced contrast, underlined links and reduced motion.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class a11y {
    /** @var int[] Text sizes, in percent: up to 200 % (WCAG 1.4.4). */
    public const TEXT_SIZES = [90, 100, 115, 130, 150, 175, 200];

    /** @var string[] Reading fonts, besides the font of the theme (empty value). */
    public const FONTS = ['atkinson', 'lexend', 'opendyslexic'];

    /** @var string[] Colour schemes a user can choose, besides the one of the site (empty value): light, dark, as the
     * device, yellow on black and cream (low vision, visual stress). */
    public const SCHEMES = ['light', 'dark', 'auto', 'yellow', 'cream'];

    /** @var string[] Reading guides, besides none (empty value): a ruler under the line read, or a mask around it. */
    public const GUIDES = ['ruler', 'mask'];

    /** @var string[] Preferences that are on or off. */
    public const SWITCHES = ['spacing', 'lines', 'contrast', 'underline', 'motion', 'stillimages', 'scrollbars'];

    /** @var array[] Profiles: the choices set in one click, all the others back to their default (the colour scheme
     * kept). Dyslexia: a font drawn to be read easily, more spacing, shorter lines, a ruler under the line read. Low
     * vision: text at 150 %, more contrast, underlined links, the scroll bars shown. Focus: shorter lines, nothing that
     * moves, a mask around the lines read. Seizures: nothing that moves nor flashes. */
    public const PROFILES = [
        'dyslexia' => ['text' => 115, 'font' => 'lexend', 'guide' => 'ruler', 'switches' => ['spacing', 'lines']],
        'lowvision' => ['text' => 150, 'font' => 'atkinson', 'guide' => '',
            'switches' => ['contrast', 'underline', 'scrollbars']],
        'focus' => ['text' => 100, 'font' => '', 'guide' => 'mask', 'switches' => ['lines', 'motion', 'stillimages']],
        'seizures' => ['text' => 100, 'font' => '', 'guide' => '', 'switches' => ['motion', 'stillimages']],
    ];

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
            'theme_epure_a11y_guide' => ['type' => PARAM_ALPHA, 'null' => NULL_NOT_ALLOWED, 'default' => '',
                'choices' => array_merge([''], self::GUIDES), 'permissioncallback' => $own],
            // The bar of the reading aloud, with the voices of the device: for each user, not a display of the site.
            'theme_epure_a11y_speech' => ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED, 'default' => 0,
                'choices' => [0, 1], 'permissioncallback' => $own],
        ];
        foreach (self::SWITCHES as $switch) {
            $definitions['theme_epure_a11y_' . $switch] = ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
                'default' => 0, 'choices' => [0, 1], 'permissioncallback' => $own];
        }
        // Reading mode of the pages of the activities, without the drawers.
        $definitions['theme_epure_focus'] = ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
            'default' => 0, 'choices' => [0, 1], 'permissioncallback' => $own];
        // The key « ? » opens the help of the keyboard shortcuts, or not (WCAG 2.1.4).
        $definitions[shortcuts::PREFERENCE] = ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
            'default' => 1, 'choices' => [0, 1], 'permissioncallback' => $own];
        // The keys of navigation (N, P, J, K, G then a letter): off by default (WCAG 2.1.4).
        $definitions[shortcuts::NAV_PREFERENCE] = ['type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
            'default' => 0, 'choices' => [0, 1], 'permissioncallback' => $own];
        // The news of Épure closed by an administrator: the release they read.
        $definitions[whatsnew::PREFERENCE] = ['type' => PARAM_NOTAGS, 'null' => NULL_NOT_ALLOWED, 'default' => '',
            'permissioncallback' => $own];
        // The page « My courses »: the courses the user teaches as cards or as a table.
        $definitions[mycourses::VIEW_PREFERENCE] = ['type' => PARAM_ALPHA, 'null' => NULL_NOT_ALLOWED,
            'default' => 'cards', 'choices' => ['cards', 'table'], 'permissioncallback' => $own];
        return $definitions;
    }

    /**
     * Preferences of the current user.
     *
     * @return array{text: int, font: string, scheme: string, guide: string, spacing: bool, lines: bool, contrast: bool,
     *     underline: bool, motion: bool}
     */
    public static function preferences(): array {
        // Without the button « Aa » (turned off by the administrator, for another panel for instance), the users can no
        // longer change their preferences: the display of the site.
        $enabled = self::panel_enabled();
        $defaults = self::site_defaults();
        // The visitors keep their choices in a cookie of their browser; a user logged in in their profile, and, for what
        // they never chose there, the choices they made in this browser before logging in.
        $cookie = $enabled ? self::cookie() : [];
        $user = $enabled && !self::visitor();
        // A preference the user never chose is the one of the site.
        $preference = fn(string $name) => ($user ? get_user_preferences('theme_epure_a11y_' . $name, null) : null)
            ?? $cookie[$name] ?? null;
        $text = (int) ($preference('text') ?? $defaults['text']);
        $font = (string) ($preference('font') ?? $defaults['font']);
        $scheme = (string) ($preference('scheme') ?? '');
        $guide = (string) ($preference('guide') ?? '');
        $preferences = [
            'text' => in_array($text, self::TEXT_SIZES, true) ? $text : 100,
            'font' => in_array($font, self::FONTS, true) ? $font : '',
            'scheme' => in_array($scheme, self::SCHEMES, true) ? $scheme : '',
            'guide' => in_array($guide, self::GUIDES, true) ? $guide : '',
        ];
        foreach (self::SWITCHES as $switch) {
            $preferences[$switch] = (bool) ($preference($switch) ?? $defaults[$switch]);
        }
        $preferences['speech'] = (bool) ($preference('speech') ?? false);
        return $preferences;
    }

    /**
     * Whether the current user is a visitor, not logged in or logged in as a guest: their choices are kept in a cookie
     * of their browser, not in a profile.
     *
     * @return bool
     */
    public static function visitor(): bool {
        return !isloggedin() || isguestuser();
    }

    /**
     * The choices kept in the cookie of the browser (a11y_panel.js): each name with its value, as text; what is not
     * a known preference, or not one of its values, is left out.
     *
     * @return string[]
     */
    protected static function cookie(): array {
        $raw = $_COOKIE[self::COOKIE] ?? '';
        $values = is_string($raw) && strlen($raw) < 1000 ? json_decode($raw, true) : null;
        if (!is_array($values)) {
            return [];
        }
        // Each value among the choices of its preference, as Moodle checks those of a profile
        // (self::definitions()): the switches 0 or 1 only, the sizes, fonts, colours and guides known.
        $choices = [];
        foreach (self::definitions() as $name => $definition) {
            if (str_starts_with($name, 'theme_epure_a11y_') && isset($definition['choices'])) {
                $choices[substr($name, strlen('theme_epure_a11y_'))] = array_map('strval', $definition['choices']);
            }
        }
        $valid = [];
        foreach ($values as $name => $value) {
            if (isset($choices[$name]) && (is_string($value) || is_int($value))
                    && in_array((string) $value, $choices[$name], true)) {
                $valid[$name] = (string) $value;
            }
        }
        return $valid;
    }

    /**
     * Display of the site for the users who did not choose theirs, and for the visitors: the settings « Display by
     * default » of the theme.
     *
     * @return array{text: int, font: string, spacing: bool, lines: bool, contrast: bool, underline: bool, motion: bool,
     *     stillimages: bool, scrollbars: bool}
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

    /** @var string Cookie of the choices of the visitors (not logged in, or guests), in their browser. */
    public const COOKIE = 'theme_epure_a11y';

    /** @var string[] Plugins that give every page a panel of display preferences of their own. */
    public const OTHER_PANELS = ['local_accessibility', 'local_a11y', 'local_accessibilitywidget'];

    /**
     * The plugins of the site that give every page a panel of display preferences of their own, with Épure's: two
     * panels, two keys Alt + A, two texts enlarged. The administrator chooses one (the setting « Button Aa » says so).
     *
     * @return string[] Their names.
     */
    public static function other_panels(): array {
        $found = [];
        foreach (self::OTHER_PANELS as $plugin) {
            if (\core_component::get_component_directory($plugin) !== null && get_config($plugin, 'version') !== false) {
                $found[] = get_string('pluginname', $plugin);
            }
        }
        return $found;
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
        if ($preferences['guide'] !== '') {
            $classes[] = 'epure-a11y-guide-' . $preferences['guide'];
        }
        if ($preferences['speech']) {
            $classes[] = 'epure-a11y-speech';
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
     * @param string $scheme light, dark, auto, yellow (on black, a dark scheme) or cream.
     * @return string The classes, empty for the light scheme.
     */
    public static function scheme_class(string $scheme): string {
        return ['dark' => 'epure-dark', 'auto' => 'epure-dark-auto', 'yellow' => 'epure-dark epure-a11y-scheme-yellow',
            'cream' => 'epure-a11y-scheme-cream'][$scheme] ?? '';
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
        $guides = [['value' => '', 'label' => get_string('a11yguide_none', 'theme_epure'),
            'checked' => $preferences['guide'] === '']];
        foreach (self::GUIDES as $guide) {
            $guides[] = ['value' => $guide, 'label' => get_string('a11yguide_' . $guide, 'theme_epure'),
                'checked' => $guide === $preferences['guide']];
        }
        $profiles = [];
        foreach (self::PROFILES as $profile => $choices) {
            $profiles[] = ['name' => $profile, 'label' => get_string('a11yprofile_' . $profile, 'theme_epure'),
                'help' => get_string('a11yprofile_' . $profile . '_help', 'theme_epure'),
                'choices' => json_encode($choices)];
        }
        $switches = [];
        foreach (array_merge(self::SWITCHES, ['speech']) as $switch) {
            $switches[] = ['name' => $switch, 'label' => get_string('a11y' . $switch, 'theme_epure'),
                'help' => get_string('a11y' . $switch . '_help', 'theme_epure'), 'checked' => $preferences[$switch]];
        }
        return ['visitor' => self::visitor(), 'cookie' => self::COOKIE,
            'sizes' => $sizes, 'fonts' => $fonts, 'schemes' => $schemes, 'guides' => $guides, 'switches' => $switches,
            'profiles' => $profiles, 'defaultscheme' => self::default_scheme()];
    }
}
