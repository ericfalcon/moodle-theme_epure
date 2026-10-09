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
 * H5P contents in the colour of the brand.
 *
 * H5P shows its contents in a frame of their own, which the styles of the theme do not reach. Moodle lets a theme
 * add a style sheet to them ({@see \theme_epure\output\core_h5p_renderer}): it sets the theme variables of H5P
 * (core 1.28, the recent content types) from the palette, and replaces the fixed blues of the older content
 * types (JoubelUI buttons, score and progress bars, Course Presentation, Interactive Video).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class h5p {
    /**
     * URL of the style sheet of the H5P contents for the brand of the current page (of the company with IOMAD).
     *
     * @return \moodle_url
     */
    public static function stylesheet_url(): \moodle_url {
        return new \moodle_url('/theme/epure/h5p.php', [
            'brand' => ltrim(company_style::page_brand(), '#'),
            'rev' => theme_get_revision(),
        ]);
    }

    /**
     * Style sheet of the H5P contents for a brand colour.
     *
     * @param string|null $brand Brand colour.
     * @return string CSS.
     */
    public static function css(?string $brand): string {
        $p = palette::derive($brand);
        // The brand colour readable on white: H5P uses the same colour for its buttons and for text on white.
        $main = $p['text'];
        $on = palette::on_colour($main);
        $dark = palette::mix($main, '#000000', 0.15);
        $darker = palette::mix($main, '#000000', 0.3);
        $light = palette::mix($main, '#FFFFFF', 0.12);
        $tint = fn(float $ratio) => palette::mix($p['brand'], '#FFFFFF', $ratio);

        $variables = [
            '--h5p-theme-main-cta-base' => $main,
            '--h5p-theme-main-cta-dark' => $dark,
            '--h5p-theme-main-cta-light' => $light,
            '--h5p-theme-contrast-cta' => $on,
            '--h5p-theme-contrast-cta-white' => $main,
            '--h5p-theme-focus' => $main,
            '--h5p-theme-secondary-contrast-cta-hover' => $tint(0.92),
            '--h5p-theme-background' => $tint(0.98),
            '--h5p-theme-alternative-light' => $tint(0.97),
            '--h5p-theme-alternative-base' => $tint(0.94),
            '--h5p-theme-alternative-dark' => $tint(0.88),
            '--h5p-theme-alternative-darker' => $tint(0.8),
            '--h5p-theme-stroke-1' => $tint(0.85),
            '--h5p-theme-stroke-2' => $tint(0.92),
        ];
        $css = ':root{';
        foreach ($variables as $name => $value) {
            $css .= "{$name}:{$value};";
        }
        $css .= '}';

        // The older content types, whose colours are written in their style sheets.
        $rules = [
            '.h5p-joubelui-button,.joubel-simple-rounded-button,.h5p-question-buttons .h5p-joubelui-button' =>
                "background:{$main};color:{$on}",
            '.h5p-joubelui-button:hover,.h5p-joubelui-button:focus,.joubel-simple-rounded-button:hover,'
                . '.joubel-simple-rounded-button:focus' => "background:{$dark};color:{$on}",
            '.h5p-joubelui-button:active,.joubel-simple-rounded-button:active' =>
                "background:{$darker};box-shadow:inset 0 4px 0 {$darker}",
            '.joubel-progress-circle-active-border,.h5p-joubelui-score-bar-progress' => "background-color:{$main}",
            '.h5p-joubelui-progressbar-background' => "background-color:{$darker}",
            '.h5p-question-feedback,.joubel-icon-edit .h5p-icon-circle:before,.joubel-icon-edit .h5p-icon-pencil:before' =>
                "color:{$main}",
            '.h5p-course-presentation .h5p-summary-score-meter>span' => "background-color:{$main}",
            '.h5p-interactive-video .h5p-text-interaction>.h5p-interaction-button,'
                . '.h5p-interactive-video .h5p-table-interaction>.h5p-interaction-button' => "background-color:{$main};color:{$on}",
            '.h5p-interactive-video .h5p-text-interaction:hover>.h5p-interaction-button,'
                . '.h5p-interactive-video .h5p-table-interaction:hover>.h5p-interaction-button' => "background-color:{$dark}",
            '.h5p-interactive-video .h5p-poster.goto-clickable-visualize,'
                . '.h5p-interactive-video .h5p-dialog.goto-clickable-visualize .h5p-dialog-inner' => "border-color:{$main}",
            '.h5p-interactive-video :focus-visible' => "outline-color:{$main}",
            '.ui-state-active,.ui-widget-content .ui-state-active,.ui-widget-header .ui-state-active' =>
                "border-color:{$dark};background:{$main};color:{$on}",
            // Course presentations before 1.26 and audio before 1.5.24: blue written in their style sheets (progress
            // bar of the slides, buttons that open an element, audio buttons).
            '.h5p-course-presentation .h5p-progressbar .h5p-progressbar-part-show' =>
                "background:{$main};filter:none",
            '.h5p-course-presentation .h5p-element-button' => "background:{$main};color:{$on}",
            '.h5p-course-presentation .h5p-element-button:hover,.h5p-course-presentation .h5p-element-button:focus' =>
                "background:{$dark}",
            '.h5p-course-presentation .h5p-progressbar a:focus,.h5p-course-presentation .h5p-footer [role="button"]:focus,'
                . '.h5p-content:not(.using-mouse) .h5p-course-presentation .h5p-wrapper:focus::after' => "outline-color:{$main}",
            '.h5p-audio-inner:not(.h5p-audio-transparent) .h5p-audio-minimal-button' =>
                "background:{$main};border-color:{$main};color:{$on}",
            '.h5p-audio-inner:not(.h5p-audio-transparent) .h5p-audio-minimal-button:hover,'
                . '.h5p-audio-inner:not(.h5p-audio-transparent) .h5p-audio-minimal-play-paused,'
                . '.h5p-audio-inner:not(.h5p-audio-transparent) .h5p-audio-minimal-pause' =>
                "background:{$dark};border-color:{$dark};color:{$on}",
        ];
        foreach ($rules as $selector => $declarations) {
            $css .= "{$selector}{{$declarations}}";
        }
        return $css;
    }
}
