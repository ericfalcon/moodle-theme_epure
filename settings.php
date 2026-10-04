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
 * Admin settings for theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingepure', get_string('configtitle', 'theme_epure'));

    // General tab: identity, typography and shape.
    $page = new admin_settingpage('theme_epure_general', get_string('generalsettings', 'theme_epure'));

    $page->add(new admin_setting_heading(
        'theme_epure/vocabularylink',
        '',
        get_string('vocabularylink', 'theme_epure', (new moodle_url('/theme/epure/vocabulary.php'))->out())
    ));

    // Brand colour, with the contrast the theme reaches for the current value.
    $current = theme_epure\palette::derive(get_config('theme_epure', 'brandcolor') ?: null);
    $a = (object) [
        'oncontrast' => format_float($current['oncontrast'], 1),
        'textcontrast' => format_float($current['textcontrast'], 1),
        'text' => $current['text'],
    ];
    $description = get_string('brandcolor_desc', 'theme_epure');
    $description .= html_writer::tag('p', get_string('brandcolor_contrast', 'theme_epure', $a));
    if ($current['textadjusted']) {
        $description .= html_writer::tag('p', get_string('brandcolor_adjusted', 'theme_epure', $a));
    }
    $setting = new theme_epure\admin\setting_brandcolour(
        'theme_epure/brandcolor',
        get_string('brandcolor', 'theme_epure'),
        $description,
        theme_epure\palette::DEFAULT_BRAND
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_configselect(
        'theme_epure/font',
        get_string('font', 'theme_epure'),
        get_string('font_desc', 'theme_epure'),
        theme_epure\fonts::DEFAULT,
        theme_epure\fonts::options()
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_configtext(
        'theme_epure/customfontname',
        get_string('customfontname', 'theme_epure'),
        get_string('customfontname_desc', 'theme_epure'),
        '',
        PARAM_TEXT
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    foreach (['customfontregular', 'customfontbold'] as $name) {
        $setting = new admin_setting_configstoredfile(
            'theme_epure/' . $name,
            get_string($name, 'theme_epure'),
            get_string($name . '_desc', 'theme_epure'),
            $name,
            0,
            ['maxfiles' => 1, 'accepted_types' => ['.woff2', '.woff']]
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }

    $setting = new admin_setting_configselect(
        'theme_epure/radius',
        get_string('radius', 'theme_epure'),
        get_string('radius_desc', 'theme_epure'),
        'soft',
        [
            'sharp' => get_string('radiussharp', 'theme_epure'),
            'soft' => get_string('radiussoft', 'theme_epure'),
            'round' => get_string('radiusround', 'theme_epure'),
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // My courses page: courses split by role, with cards suited to each one.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/mycoursesbyrole',
        get_string('mycoursesbyrole', 'theme_epure'),
        get_string('mycoursesbyrole_desc', 'theme_epure'),
        1
    ));

    $settings->add($page);

    // Header tab: colour and logos.
    $page = new admin_settingpage('theme_epure_header', get_string('headersettings', 'theme_epure'));

    $setting = new admin_setting_configselect(
        'theme_epure/headerstyle',
        get_string('headerstyle', 'theme_epure'),
        get_string('headerstyle_desc', 'theme_epure'),
        'light',
        [
            'light' => get_string('headerstylelight', 'theme_epure'),
            'brand' => get_string('headerstylebrand', 'theme_epure'),
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    foreach (['logo', 'logoonbrand'] as $name) {
        $setting = new admin_setting_configstoredfile(
            'theme_epure/' . $name,
            get_string($name, 'theme_epure'),
            get_string($name . '_desc', 'theme_epure'),
            $name,
            0,
            ['maxfiles' => 1, 'accepted_types' => ['.svg', '.png', '.webp', '.jpg', '.jpeg']]
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }

    $settings->add($page);

    // Login tab.
    $page = new admin_settingpage('theme_epure_login', get_string('loginsettings', 'theme_epure'));

    $setting = new admin_setting_configselect(
        'theme_epure/loginlayout',
        get_string('loginlayout', 'theme_epure'),
        get_string('loginlayout_desc', 'theme_epure'),
        'split',
        [
            'split' => get_string('loginlayoutsplit', 'theme_epure'),
            'centered' => get_string('loginlayoutcentered', 'theme_epure'),
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $page->add(new admin_setting_configtext(
        'theme_epure/logintagline',
        get_string('logintagline', 'theme_epure'),
        get_string('logintagline_desc', 'theme_epure'),
        '',
        PARAM_TEXT,
        60
    ));

    $page->add(new admin_setting_configtext(
        'theme_epure/logintext',
        get_string('logintext', 'theme_epure'),
        get_string('logintext_desc', 'theme_epure'),
        '',
        PARAM_TEXT,
        80
    ));

    $setting = new admin_setting_configstoredfile(
        'theme_epure/loginimage',
        get_string('loginimage', 'theme_epure'),
        get_string('loginimage_desc', 'theme_epure'),
        'loginimage',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.jpg', '.jpeg', '.png', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // Advanced tab: raw SCSS.
    $page = new admin_settingpage('theme_epure_advanced', get_string('advancedsettings', 'theme_epure'));

    $setting = new admin_setting_scsscode(
        'theme_epure/scsspre',
        get_string('rawscsspre', 'theme_epure'),
        get_string('rawscsspre_desc', 'theme_epure'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_scsscode(
        'theme_epure/scss',
        get_string('rawscss', 'theme_epure'),
        get_string('rawscss_desc', 'theme_epure'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}

// Vocabulary of the platform: a page of its own, as applying it rewrites the language strings.
$ADMIN->add('themes', new admin_externalpage(
    'theme_epure_vocabulary',
    new lang_string('vocabulary', 'theme_epure'),
    new moodle_url('/theme/epure/vocabulary.php')
));
