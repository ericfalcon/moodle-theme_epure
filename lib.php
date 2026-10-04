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

/**
 * Theme callbacks for Épure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_epure\fonts;
use theme_epure\palette;

/**
 * Border radius presets, as [small, default, large] in rem.
 */
const THEME_EPURE_RADII = [
    'sharp' => ['0.2rem', '0.25rem', '0.375rem'],
    'soft' => ['0.375rem', '0.5rem', '0.75rem'],
    'round' => ['0.5rem', '0.875rem', '1.25rem'],
];

/**
 * Returns the @font-face rules and the font-family stack for the selected font.
 *
 * When the uploaded font is selected but no regular file has been uploaded,
 * the default bundled font is used.
 *
 * @param theme_config $theme The theme config object.
 * @return array{0: string, 1: string}
 */
function theme_epure_get_font($theme): array {
    $key = $theme->settings->font ?? null;
    if ($key === fonts::CUSTOM) {
        $urls = [];
        foreach ([400 => 'customfontregular', 700 => 'customfontbold'] as $weight => $setting) {
            if (!empty($theme->settings->$setting)) {
                $urls[$weight] = (string) $theme->setting_file_url($setting, $setting);
            }
        }
        if (isset($urls[400])) {
            return fonts::custom_scss($theme->settings->customfontname ?? '', $urls);
        }
        $key = fonts::DEFAULT;
    }
    return [fonts::font_face_scss($key), fonts::stack($key)];
}

/**
 * Serves the files uploaded in the theme settings.
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param context $context Context.
 * @param string $filearea File area.
 * @param array $args Extra arguments.
 * @param bool $forcedownload Whether or not to force the download.
 * @param array $options Additional options affecting the file serving.
 * @return bool
 */
function theme_epure_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Logo of an IOMAD company for the brand-coloured header: item = company, then a revision, then the file name.
    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'companylogoonbrand' && count($args) >= 3) {
        $companyid = (int) array_shift($args);
        $filename = array_pop($args);
        $file = get_file_storage()->get_file($context->id, 'theme_epure', 'companylogoonbrand', $companyid, '/', $filename);
        if ($file && !$file->is_directory()) {
            send_stored_file($file, YEARSECS, 0, $forcedownload, ['cacheability' => 'public'] + $options);
        }
        send_file_not_found();
    }
    $fileareas = ['customfontregular', 'customfontbold', 'logo', 'logoonbrand', 'loginimage'];
    if ($context->contextlevel == CONTEXT_SYSTEM && in_array($filearea, $fileareas)) {
        $theme = theme_config::load('epure');
        // Fonts and logos are requested on every page, including the login page.
        $options['cacheability'] = 'public';
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }
    send_file_not_found();
}

/**
 * Returns the main SCSS content: Boost's default preset followed by Épure's styles.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_epure_get_main_scss_content($theme) {
    global $CFG;

    $scss = theme_epure_get_font($theme)[0];
    // Reading fonts of the display preferences: woff2 files are only downloaded when a user chooses one.
    foreach (\theme_epure\a11y::FONTS as $key) {
        if ($key !== ($theme->settings->font ?? null)) {
            $scss .= fonts::font_face_scss($key);
        }
    }
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/epure/scss/epure.scss');
    return $scss;
}

/**
 * Returns the SCSS variables computed from the settings, prepended to the main SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_epure_get_pre_scss($theme) {
    $light = palette::derive($theme->settings->brandcolor ?? null);
    $dark = palette::derive($theme->settings->brandcolor ?? null, true);
    $radius = THEME_EPURE_RADII[$theme->settings->radius ?? ''] ?? THEME_EPURE_RADII['soft'];

    $variables = [
        'primary' => $light['fill'],
        'link-color' => $light['text'],
        'link-hover-color' => $light['text'],
        'font-family-sans-serif' => theme_epure_get_font($theme)[1],
        'border-radius-sm' => $radius[0],
        'border-radius' => $radius[1],
        'border-radius-lg' => $radius[2],
        // Every page uses the full width of the administration pages: Boost narrows the front
        // page, the dashboard, My courses and the course pages to 830 or 1120 px.
        'course-content-maxwidth' => 'none',
        'medium-content-maxwidth' => 'none',
        'epure-brand' => $light['fill'],
        'epure-brand-hover' => $light['hover'],
        'epure-on-brand' => $light['on'],
        'epure-brand-text' => $light['text'],
        'epure-brand-soft' => $light['soft'],
        'epure-brand-soft-2' => $light['soft2'],
        'epure-header-brand' => ($theme->settings->headerstyle ?? '') === 'brand' ? 'true' : 'false',
        'epure-on-brand-is-light' => $light['on'] === palette::SURFACE_LIGHT ? 'true' : 'false',
        'epure-login-overlay' => 'rgba(' . implode(', ', palette::to_rgb($light['fill'])) . ', ' . $light['overlayalpha'] . ')',
        'epure-dark-brand' => $dark['fill'],
        'epure-dark-brand-hover' => $dark['hover'],
        'epure-dark-on-brand' => $dark['on'],
        'epure-dark-brand-text' => $dark['text'],
        'epure-dark-brand-soft' => $dark['soft'],
        'epure-dark-brand-soft-2' => $dark['soft2'],
    ];

    $scss = '';
    foreach ($variables as $name => $value) {
        $scss .= '$' . $name . ': ' . $value . ";\n";
    }
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre . "\n";
    }
    return $scss;
}

/**
 * Returns the custom SCSS appended after the main SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_epure_get_extra_scss($theme) {
    return !empty($theme->settings->scss) ? $theme->settings->scss : '';
}

/**
 * Returns the precompiled CSS used when the SCSS cannot be compiled.
 *
 * @return string
 */
function theme_epure_get_precompiled_css() {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/style/moodle.css');
}

/**
 * User preferences of the theme: the display preferences of the button « Aa ».
 *
 * @return array[]
 */
function theme_epure_user_preferences(): array {
    return \theme_epure\a11y::definitions();
}
