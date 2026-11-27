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
 * The Moodle app in the colours of the brand.
 *
 * The app downloads the style sheet set in Site administration › Mobile app › Mobile appearance (mobilecssurl)
 * once per site, and keeps it until the address changes. With the setting of the theme, that address is the
 * style sheet of the theme, served by pluginfile.php: the app calls it with the token of the user, so the sheet
 * is the one of their IOMAD company (brand colour, header, font), and the site one on the login screen.
 * Each change of the appearance gives the address a new revision, for the apps to download it again.
 *
 * The sheet sets the variables of the app (Ionic): primary colour, header, tabs, links, progress bars,
 * in light and in dark mode, with the contrasts of the palette of the theme.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile_app {
    /** @var string File area of the style sheet. */
    public const FILEAREA = 'mobileapp';

    /**
     * Whether the theme gives the app its style sheet.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return (bool) get_config('theme_epure', 'mobileapp');
    }

    /**
     * Address of the style sheet of the app, at a revision.
     *
     * @param int $revision Revision.
     * @return \moodle_url
     */
    public static function url(int $revision): \moodle_url {
        return \moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'theme_epure',
            self::FILEAREA,
            $revision,
            '/',
            'app.css'
        );
    }

    /**
     * Whether an address is the style sheet of the theme.
     *
     * @param string|null $url Address.
     * @return bool
     */
    public static function is_ours(?string $url): bool {
        return (bool) preg_match('~/theme_epure/' . self::FILEAREA . '/\d+/app\.css$~', (string) $url);
    }

    /**
     * After a change of the appearance in the settings (brand colour, header, font, this setting): the caches of the
     * themes, and the style sheet of the app.
     */
    public static function appearance_updated(): void {
        theme_reset_all_caches();
        self::refresh();
    }

    /**
     * Sets the style sheet of the app after a change of the appearance: a new revision with the setting, else
     * no style sheet if it was the one of the theme (a style sheet of the site, set by hand, stays as it is).
     */
    public static function refresh(): void {
        global $CFG;
        if (during_initial_install()) {
            return;
        }
        if (self::enabled()) {
            set_config('mobilecssurl', self::url(time())->out(false));
        } else if (self::is_ours($CFG->mobilecssurl ?? '')) {
            set_config('mobilecssurl', '');
        }
    }

    /**
     * Style sheet of the app for the current user: their company, else the site. Empty when the user does not see
     * the site with Épure (another theme of the site or of their company).
     *
     * @return string CSS.
     */
    public static function current_css(): string {
        if (!theme_use::current()) {
            return '';
        }
        $company = company_style::current_company();
        $settings = $company ? company_style::settings((int) $company->id) : [];
        $font = ($settings['font'] ?? '') ?: (string) get_config('theme_epure', 'font');
        return self::css(company_style::page_brand(), company_style::header_style(), $font);
    }

    /**
     * Style sheet of the app.
     *
     * @param string|null $brand Brand colour.
     * @param string $headerstyle Header: light or brand.
     * @param string|null $font Font of the theme; a font uploaded by the administrator is not given to the app.
     * @return string CSS.
     */
    public static function css(?string $brand, string $headerstyle = 'light', ?string $font = null): string {
        $css = '';
        $variables = [];
        if ($font && isset(fonts::all()[$font])) {
            $css .= fonts::font_face_css(
                $font,
                fn($file) => (new \moodle_url('/theme/font.php/epure/theme/' . theme_get_revision() . '/' . $file))->out(false)
            );
            $variables['--ion-font-family'] = fonts::stack($font);
        }
        $css .= ':root{' . self::declarations(palette::derive($brand), $headerstyle, $variables) . '}';
        $css .= ':root.dark{' . self::declarations(palette::derive($brand, true), $headerstyle) . '}';
        return $css . self::preferences_css();
    }

    /**
     * The display preferences of « Aa » in the app: the classes that the app puts on its html element from the profile of
     * the user (db/mobile.php, mobile/init.js), the same as on the pages of the site. The size of the text, the reading
     * font, the spacing, the shorter lines, the underlined links, no motion, the stronger focus; the dark colours are
     * those of the app (its class dark, with the colours of the theme above), yellow on black over every colour, cream
     * as an overlay.
     *
     * @return string CSS.
     */
    public static function preferences_css(): string {
        $css = '';
        foreach (a11y::TEXT_SIZES as $size) {
            if ($size !== 100) {
                $css .= "html.epure-a11y-text-{$size}{font-size:{$size}% !important;}";
            }
        }
        $url = fn($file) => (new \moodle_url('/theme/font.php/epure/theme/' . theme_get_revision() . '/' . $file))->out(false);
        foreach (a11y::FONTS as $font) {
            $css .= fonts::font_face_css($font, $url);
            $stack = fonts::stack($font);
            $css .= "html.epure-a11y-font-{$font}{--ion-font-family:{$stack};}"
                . "html.epure-a11y-font-{$font} body,html.epure-a11y-font-{$font} core-format-text{font-family:{$stack};}";
        }
        $css .= 'html.epure-a11y-spacing core-format-text,html.epure-a11y-spacing ion-content{line-height:1.5;'
            . 'letter-spacing:.12em;word-spacing:.16em;}'
            . 'html.epure-a11y-lines core-format-text :is(p,li,blockquote){max-width:70ch;}'
            . 'html.epure-a11y-underline core-format-text a,html.epure-a11y-underline a.core-link{'
            . 'text-decoration:underline;text-underline-offset:.15em;}'
            . 'html.epure-a11y-motion *,html.epure-a11y-motion *::before,html.epure-a11y-motion *::after{'
            . 'animation:none !important;transition:none !important;scroll-behavior:auto !important;}'
            . 'html.epure-a11y-focusring :focus-visible{outline:3px solid #fff !important;outline-offset:0 !important;'
            . 'box-shadow:0 0 0 6px #000 !important;}';
        // Yellow on black, over the colours of the app and of the theme.
        $yellow = [
            '--ion-background-color' => '#000', '--ion-background-color-rgb' => '0,0,0',
            '--ion-text-color' => '#ff0', '--ion-text-color-rgb' => '255,255,0',
            '--ion-item-background' => '#000', '--ion-card-background' => '#000', '--ion-toolbar-background' => '#000',
            '--ion-toolbar-color' => '#ff0', '--ion-tab-bar-background' => '#000', '--ion-tab-bar-color' => '#ff0',
            '--ion-tab-bar-color-selected' => '#ff0', '--ion-border-color' => '#b8b800',
            '--ion-color-primary' => '#ff0', '--ion-color-primary-base' => '#ff0', '--ion-color-primary-rgb' => '255,255,0',
            '--ion-color-primary-contrast' => '#000', '--ion-color-primary-contrast-rgb' => '0,0,0',
            '--ion-color-primary-shade' => '#e0e000', '--ion-color-primary-tint' => '#ffff8a',
            '--primary' => '#ff0', '--primary-contrast' => '#000', '--primary-shade' => '#e0e000', '--primary-tint' => '#ffff8a',
            '--core-header-toolbar-background' => '#000', '--core-header-toolbar-color' => '#ff0',
            '--core-header-toolbar-border-color' => '#ff0', '--core-link-color' => '#8fd9ff',
            '--core-bottom-tabs-background' => '#000', '--core-bottom-tabs-color' => '#ff0',
            '--core-bottom-tabs-color-selected' => '#ff0', '--core-bottom-tabs-border-color-active' => '#ff0',
            '--core-tab-color-active' => '#ff0', '--core-tab-border-color-active' => '#ff0',
            '--core-progressbar-color' => '#ff0', '--core-progressbar-background' => '#2e2e00',
            '--a11y-shadow-focus-boxShadowColor' => '#fff',
        ];
        $css .= 'html.epure-a11y-scheme-yellow,html.epure-a11y-scheme-yellow.dark{';
        foreach ($yellow as $name => $value) {
            $css .= "{$name}:{$value} !important;";
        }
        $css .= '}html.epure-a11y-scheme-yellow core-format-text *{color:#ff0 !important;background-color:transparent !important;'
            . 'border-color:#b8b800 !important;}'
            . 'html.epure-a11y-scheme-yellow core-format-text :is(a,a *){color:#8fd9ff !important;}';
        // Cream: an overlay over the whole app, as on the site.
        $css .= 'html.epure-a11y-scheme-cream body::after{position:fixed;inset:0;z-index:2147483000;content:"";'
            . 'pointer-events:none;background-color:' . palette::CREAM . ';mix-blend-mode:multiply;}';
        return $css;
    }

    /**
     * Variables of the app for a palette.
     *
     * @param array $p Palette ({@see palette::derive()}).
     * @param string $headerstyle Header: light or brand.
     * @param array $variables Other variables.
     * @return string CSS declarations.
     */
    protected static function declarations(array $p, string $headerstyle, array $variables = []): string {
        // The primary colour fills buttons and marks the active tab: the fill of the palette, with its readable text.
        $primary = $p['fill'];
        $variables += [
            '--primary' => $primary,
            '--primary-shade' => palette::mix($primary, '#000000', 0.2),
            '--primary-tint' => $p['soft2'],
            '--primary-contrast' => $p['on'],
            '--ion-color-primary' => $primary,
            '--ion-color-primary-base' => $primary,
            '--ion-color-primary-rgb' => implode(',', palette::to_rgb($primary)),
            '--ion-color-primary-contrast' => $p['on'],
            '--ion-color-primary-contrast-rgb' => implode(',', palette::to_rgb($p['on'])),
            '--ion-color-primary-shade' => palette::mix($primary, '#000000', 0.2),
            '--ion-color-primary-tint' => $p['hover'],
            '--core-link-color' => $p['text'],
            '--a11y-shadow-focus-boxShadowColor' => $p['text'],
            '--core-bottom-tabs-color-selected' => $p['text'],
            '--core-bottom-tabs-border-color-active' => $primary,
            '--core-bottom-tabs-badge-color' => $primary,
            '--core-bottom-tabs-badge-text-color' => $p['on'],
            '--core-tab-color-active' => $p['text'],
            '--core-tab-border-color-active' => $primary,
            '--core-progressbar-color' => $primary,
            '--core-progressbar-background' => $p['soft2'],
            '--selected-item-color' => $primary,
            '--core-messages-discussion-badge' => $primary,
            '--core-messages-discussion-badge-text' => $p['on'],
        ];
        // A gradient is a brand-coloured header in the app, which has no gradient: its brand colour.
        if ($headerstyle !== 'light') {
            $variables += [
                '--core-header-toolbar-background' => $primary,
                '--core-header-toolbar-color' => $p['on'],
                '--core-header-toolbar-border-color' => $primary,
            ];
        } else {
            $variables['--core-header-toolbar-border-color'] = $primary;
        }
        $css = '';
        foreach ($variables as $name => $value) {
            $css .= "{$name}:{$value};";
        }
        return $css;
    }
}
