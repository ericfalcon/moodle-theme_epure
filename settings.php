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
        'theme_epure/darkmode',
        get_string('darkmode', 'theme_epure'),
        get_string('darkmode_desc', 'theme_epure'),
        'light',
        [
            'light' => get_string('darkmodelight', 'theme_epure'),
            'auto' => get_string('darkmodeauto', 'theme_epure'),
            'dark' => get_string('darkmodedark', 'theme_epure'),
        ]
    );
    $page->add($setting);

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

    // Course page: banner with the image, the progress or the figures of the course.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/coursebanner',
        get_string('coursebanner', 'theme_epure'),
        get_string('coursebanner_desc', 'theme_epure'),
        1
    ));

    // E-mails in the colours of the brand.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/emailbranding',
        get_string('emailbranding', 'theme_epure'),
        get_string('emailbranding_desc', 'theme_epure'),
        1
    ));

    // Quick search (Ctrl+K).
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/quicksearch',
        get_string('quicksearch', 'theme_epure'),
        get_string('quicksearch_desc', 'theme_epure'),
        1
    ));

    // Navigation bar at the bottom of the screen on phones.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/mobilenav',
        get_string('mobilenav', 'theme_epure'),
        get_string('mobilenav_desc', 'theme_epure'),
        1
    ));

    // Catalogue of the courses, and presentation of a course on its enrolment page.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/catalogue',
        get_string('catalogue', 'theme_epure'),
        get_string('catalogue_desc', 'theme_epure'),
        1
    ));

    // Pages of the activities: strip with the position and the progress, previous and next activities.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/activitynav',
        get_string('activitynav', 'theme_epure'),
        get_string('activitynav_desc', 'theme_epure'),
        1
    ));

    // Dashboard: overview of the learner above the blocks.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/learnerdashboard',
        get_string('learnerdashboard', 'theme_epure'),
        get_string('learnerdashboard_desc', 'theme_epure'),
        1
    ));

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

    // Footer tab: text, legal links and links of the administrator.
    $page = new admin_settingpage('theme_epure_footer', get_string('footersettings', 'theme_epure'));

    $page->add(new admin_setting_configcheckbox(
        'theme_epure/footer',
        get_string('footer', 'theme_epure'),
        get_string('footer_desc', 'theme_epure'),
        1
    ));

    $page->add(new admin_setting_configtextarea(
        'theme_epure/footertext',
        get_string('footertext', 'theme_epure'),
        get_string('footertext_desc', 'theme_epure'),
        '',
        PARAM_RAW
    ));

    foreach (['footerlegalurl', 'footerprivacyurl', 'footercontacturl'] as $name) {
        $page->add(new admin_setting_configtext(
            'theme_epure/' . $name,
            get_string($name, 'theme_epure'),
            get_string($name . '_desc', 'theme_epure'),
            '',
            PARAM_URL
        ));
    }

    $page->add(new admin_setting_configtextarea(
        'theme_epure/footerlinks',
        get_string('footerlinksetting', 'theme_epure'),
        get_string('footerlinksetting_desc', 'theme_epure'),
        '',
        PARAM_RAW
    ));

    $settings->add($page);

    // Accessibility tab: statement of the platform, in the French format (RGAA).
    $page = new admin_settingpage('theme_epure_a11y', get_string('a11ysettings', 'theme_epure'));

    $statementurl = theme_epure\accessibility_statement::url()->out();
    $page->add(new admin_setting_heading(
        'theme_epure/a11yheading',
        '',
        get_string('a11ysettings_desc', 'theme_epure', html_writer::link($statementurl, $statementurl))
    ));

    $statuses = ['' => get_string('a11ystatus_unpublished', 'theme_epure')];
    foreach (theme_epure\accessibility_statement::STATUSES as $status) {
        $statuses[$status] = get_string('a11ystatus_' . $status, 'theme_epure');
    }
    $page->add(new admin_setting_configselect(
        'theme_epure/a11ystatus',
        get_string('a11ystatus', 'theme_epure'),
        get_string('a11ystatus_desc', 'theme_epure'),
        '',
        $statuses
    ));

    foreach (['a11yentity', 'a11yrate', 'a11yauditor', 'a11yauditdate', 'a11ystatementdate'] as $name) {
        $page->add(new admin_setting_configtext(
            'theme_epure/' . $name,
            get_string($name, 'theme_epure'),
            get_string($name . '_desc', 'theme_epure'),
            '',
            PARAM_TEXT
        ));
    }

    foreach (theme_epure\accessibility_statement::TEXTS as $name) {
        $page->add(new admin_setting_configtextarea(
            'theme_epure/a11y' . $name,
            get_string('a11y' . $name, 'theme_epure'),
            get_string('a11y' . $name . '_desc', 'theme_epure'),
            '',
            PARAM_RAW
        ));
    }

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
