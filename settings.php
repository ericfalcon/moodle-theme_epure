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

    // Adds a file setting of the theme, whose change rebuilds the theme.
    $file = function (admin_settingpage $page, string $name, array $types) {
        $setting = new admin_setting_configstoredfile(
            'theme_epure/' . $name,
            get_string($name, 'theme_epure'),
            get_string($name . '_desc', 'theme_epure'),
            $name,
            0,
            ['maxfiles' => 1, 'accepted_types' => $types]
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    };
    // Adds a heading of the theme, with or without a description.
    $heading = function (admin_settingpage $page, string $name, string $description = '') {
        $page->add(new admin_setting_heading(
            'theme_epure/heading' . $name,
            get_string('settings' . $name, 'theme_epure'),
            $description
        ));
    };
    // Link to a page of Moodle's administration, for the settings of Moodle the theme follows.
    $adminlink = fn(string $section) => (new moodle_url('/admin/settings.php', ['section' => $section]))->out();

    // Identity: the logos first, as the colours of the logo are proposed for the brand colour, then the colours, the
    // e-mails and the vocabulary.
    $page = new admin_settingpage('theme_epure_brand', get_string('settingsbrand', 'theme_epure'));

    $heading($page, 'logos', get_string('settingslogos_desc', 'theme_epure'));
    $images = ['.svg', '.png', '.webp', '.jpg', '.jpeg'];
    foreach (['logo', 'logoonbrand', 'logomobile'] as $name) {
        $file($page, $name, $images);
    }
    $file($page, 'favicon', ['.ico', '.png', '.svg']);

    $heading($page, 'colours', get_string('settingscolours_desc', 'theme_epure'));

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
    $setting->set_updatedcallback('theme_epure\\mobile_app::appearance_updated');
    $page->add($setting);

    // Accent colour: the progress bars and the completed sections, in the brand colour when empty.
    $setting = new admin_setting_configcolourpicker(
        'theme_epure/accentcolor',
        get_string('accentcolor', 'theme_epure'),
        get_string('accentcolor_desc', 'theme_epure'),
        ''
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

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
    $setting->set_updatedcallback('theme_epure\\mobile_app::appearance_updated');
    $page->add($setting);

    $heading($page, 'emails');
    // E-mails in the colours of the brand.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/emailbranding',
        get_string('emailbranding', 'theme_epure'),
        get_string('emailbranding_desc', 'theme_epure'),
        1
    ));

    $page->add(new admin_setting_heading(
        'theme_epure/vocabularylink',
        get_string('settingsvocabulary', 'theme_epure'),
        get_string('vocabularylink', 'theme_epure', (new moodle_url('/theme/epure/vocabulary.php'))->out())
    ));

    $settings->add($page);

    // Navigation: the header, the search, the breadcrumb, the user menu.
    $page = new admin_settingpage('theme_epure_navigation', get_string('settingsnavigation', 'theme_epure'));

    $heading($page, 'primarynav', get_string('settingsprimarynav_desc', 'theme_epure', $adminlink('themesettingsadvanced')));

    // Quick search (Ctrl+K).
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/quicksearch',
        get_string('quicksearch', 'theme_epure'),
        get_string('quicksearch_desc', 'theme_epure'),
        1
    ));

    // Breadcrumb of the pages.
    $page->add(new admin_setting_configselect(
        'theme_epure/breadcrumb',
        get_string('breadcrumb', 'theme_epure'),
        get_string('breadcrumb_desc', 'theme_epure'),
        'show',
        [
            'show' => get_string('breadcrumbshow', 'theme_epure'),
            'desktop' => get_string('breadcrumbdesktop', 'theme_epure'),
            'hide' => get_string('breadcrumbhide', 'theme_epure'),
        ]
    ));

    $heading($page, 'usermenu', get_string('settingsusermenu_desc', 'theme_epure', $adminlink('themesettingsadvanced')));

    $settings->add($page);

    // Courses: the cards, the progress, the activities and the previous and next activities.
    $page = new admin_settingpage('theme_epure_courses', get_string('settingscourses', 'theme_epure'));

    $heading($page, 'cards');

    // My courses page: courses split by role, with cards suited to each one.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/mycoursesbyrole',
        get_string('mycoursesbyrole', 'theme_epure'),
        get_string('mycoursesbyrole_desc', 'theme_epure'),
        1
    ));

    // Catalogue of the courses, and presentation of a course on its enrolment page.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/catalogue',
        get_string('catalogue', 'theme_epure'),
        get_string('catalogue_desc', 'theme_epure'),
        1
    ));

    $heading($page, 'progress');

    // Course page: banner with the image, the progress or the figures of the course.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/coursebanner',
        get_string('coursebanner', 'theme_epure'),
        get_string('coursebanner_desc', 'theme_epure'),
        1
    ));

    // Course page: progress of the learner in each section and subsection.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/sectionprogress',
        get_string('sectionprogress', 'theme_epure'),
        get_string('sectionprogress_desc', 'theme_epure'),
        1
    ));

    // Dashboard: overview of the learner above the blocks.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/learnerdashboard',
        get_string('learnerdashboard', 'theme_epure'),
        get_string('learnerdashboard_desc', 'theme_epure'),
        1
    ));

    $heading($page, 'activities');

    // Activity icons: in the colour of the brand, in Moodle's colours by purpose, or hidden.
    $page->add(new admin_setting_configselect(
        'theme_epure/activityicons',
        get_string('activityicons', 'theme_epure'),
        get_string('activityicons_desc', 'theme_epure'),
        'brand',
        [
            'brand' => get_string('activityiconsbrand', 'theme_epure'),
            'moodle' => get_string('activityiconsmoodle', 'theme_epure'),
            'none' => get_string('activityiconsnone', 'theme_epure'),
        ]
    ));

    // Pages of the activities: strip with the position and the progress, previous and next activities.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/activitynav',
        get_string('activitynav', 'theme_epure'),
        get_string('activitynav_desc', 'theme_epure'),
        1
    ));

    $settings->add($page);

    // Accessibility: the display preferences of the users and the default of the site, then the statement of the
    // platform, in the French format (RGAA).
    $page = new admin_settingpage('theme_epure_a11y', get_string('a11ysettings', 'theme_epure'));

    $heading($page, 'preferences', get_string('settingspreferences_desc', 'theme_epure'));

    $page->add(new admin_setting_configcheckbox(
        'theme_epure/prefpanel',
        get_string('prefpanel', 'theme_epure'),
        get_string('prefpanel_desc', 'theme_epure'),
        1
    ));

    $sizes = [];
    foreach (theme_epure\a11y::TEXT_SIZES as $size) {
        $sizes[$size] = get_string('a11ytext_' . $size, 'theme_epure');
    }
    $setting = new admin_setting_configselect(
        'theme_epure/preftext',
        get_string('preftext', 'theme_epure'),
        get_string('preftext_desc', 'theme_epure'),
        100,
        $sizes
    );
    $page->add($setting);

    $fonts = ['' => get_string('a11yfont_theme', 'theme_epure')];
    foreach (theme_epure\a11y::FONTS as $font) {
        $fonts[$font] = theme_epure\fonts::get($font)['family'];
    }
    $page->add(new admin_setting_configselect(
        'theme_epure/preffont',
        get_string('preffont', 'theme_epure'),
        get_string('preffont_desc', 'theme_epure'),
        '',
        $fonts
    ));

    foreach (theme_epure\a11y::SWITCHES as $switch) {
        $page->add(new admin_setting_configcheckbox(
            'theme_epure/pref' . $switch,
            get_string('a11y' . $switch, 'theme_epure'),
            get_string('a11y' . $switch . '_help', 'theme_epure') . ' ' . get_string('prefswitch_desc', 'theme_epure'),
            0
        ));
    }

    $statementurl = theme_epure\accessibility_statement::url()->out();
    $heading($page, 'statement', get_string('a11ysettings_desc', 'theme_epure', html_writer::link($statementurl, $statementurl)));

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

    // Appearance: the typography, the shapes, the dark mode and the density.
    $page = new admin_settingpage('theme_epure_display', get_string('settingsdisplay', 'theme_epure'));

    $heading($page, 'typography');

    $setting = new admin_setting_configselect(
        'theme_epure/font',
        get_string('font', 'theme_epure'),
        get_string('font_desc', 'theme_epure'),
        theme_epure\fonts::DEFAULT,
        theme_epure\fonts::options()
    );
    $setting->set_updatedcallback('theme_epure\\mobile_app::appearance_updated');
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
        $file($page, $name, ['.woff2', '.woff']);
    }

    $heading($page, 'shapes');

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

    $setting = new admin_setting_configselect(
        'theme_epure/density',
        get_string('density', 'theme_epure'),
        get_string('density_desc', 'theme_epure'),
        'comfortable',
        [
            'comfortable' => get_string('densitycomfortable', 'theme_epure'),
            'compact' => get_string('densitycompact', 'theme_epure'),
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $heading($page, 'darkmode');

    $page->add(new admin_setting_configselect(
        'theme_epure/darkmode',
        get_string('darkmode', 'theme_epure'),
        get_string('darkmode_desc', 'theme_epure'),
        'light',
        [
            'light' => get_string('darkmodelight', 'theme_epure'),
            'auto' => get_string('darkmodeauto', 'theme_epure'),
            'dark' => get_string('darkmodedark', 'theme_epure'),
        ]
    ));

    $settings->add($page);

    // Mobile: the phones, the Moodle app and the installable web app.
    $page = new admin_settingpage('theme_epure_mobile', get_string('settingsmobile', 'theme_epure'));

    $heading($page, 'phones');

    // Navigation bar at the bottom of the screen on phones.
    $page->add(new admin_setting_configcheckbox(
        'theme_epure/mobilenav',
        get_string('mobilenav', 'theme_epure'),
        get_string('mobilenav_desc', 'theme_epure'),
        1
    ));

    $page->add(new admin_setting_configselect(
        'theme_epure/mobileblocks',
        get_string('mobileblocks', 'theme_epure'),
        get_string('mobileblocks_desc', 'theme_epure'),
        'drawer',
        [
            'drawer' => get_string('mobileblocksdrawer', 'theme_epure'),
            'hidden' => get_string('mobileblockshidden', 'theme_epure'),
        ]
    ));

    $page->add(new admin_setting_configselect(
        'theme_epure/mobiledashboard',
        get_string('mobiledashboard', 'theme_epure'),
        get_string('mobiledashboard_desc', 'theme_epure'),
        'full',
        [
            'full' => get_string('mobiledashboardfull', 'theme_epure'),
            'overview' => get_string('mobiledashboardoverview', 'theme_epure'),
        ]
    ));

    $heading($page, 'apps');

    // The Moodle app in the colours of the brand: the theme sets the style sheet of the app (mobilecssurl).
    $setting = new admin_setting_configcheckbox(
        'theme_epure/mobileapp',
        get_string('mobileapp', 'theme_epure'),
        get_string('mobileapp_desc', 'theme_epure'),
        0
    );
    $setting->set_updatedcallback('theme_epure\\mobile_app::appearance_updated');
    $page->add($setting);

    // The platform installable as an application from the browser (web app manifest and service worker).
    $setting = new admin_setting_configcheckbox(
        'theme_epure/webapp',
        get_string('webapp', 'theme_epure'),
        get_string('webapp_desc', 'theme_epure'),
        0
    );
    $setting->set_updatedcallback('theme_epure\\web_app::setting_updated');
    $page->add($setting);

    $page->add(new admin_setting_configtext(
        'theme_epure/webappname',
        get_string('webappname', 'theme_epure'),
        get_string('webappname_desc', 'theme_epure'),
        '',
        PARAM_TEXT,
        30
    ));

    $file($page, 'webappicon', ['.png']);

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

    $file($page, 'loginimage', ['.jpg', '.jpeg', '.png', '.webp']);

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
