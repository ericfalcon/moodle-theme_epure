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

use theme_epure\vocabulary\company;

/**
 * Hook callbacks of theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Follows the theme of the IOMAD company of the user, and enables the string manager that applies the
     * IOMAD vocabulary, when words were chosen.
     *
     * Moodle reads the custom string manager from config.php; the plugin sets it for the request
     * instead, so that the administrator does not have to edit config.php. A string manager
     * already set in config.php is kept.
     *
     * @param \core\hook\after_config $hook The hook.
     */
    public static function after_config(\core\hook\after_config $hook): void {
        global $CFG;
        self::follow_company_theme();
        if (
            during_initial_install() || !empty($CFG->config_php_settings['customstringmanager'])
                || !company_style::iomad_installed() || !company::active()
        ) {
            return;
        }
        $CFG->config_php_settings['customstringmanager'] = string_manager::class;
        get_string_manager(true);
    }

    /**
     * Whether the vocabulary of the companies can be applied on this site.
     *
     * @return bool False when config.php sets another custom string manager.
     */
    public static function string_manager_available(): bool {
        global $CFG;
        $custom = ltrim((string) ($CFG->config_php_settings['customstringmanager'] ?? ''), '\\');
        return $custom === '' || $custom === string_manager::class;
    }

    /**
     * When the theme of a company changes, IOMAD writes the new theme on its users, but a user already
     * logged in keeps the theme of their session until they log in again: the pages keep the former theme.
     * The theme of the user is read again, so that the theme chosen for the company applies at once, whole.
     */
    protected static function follow_company_theme(): void {
        global $CFG, $DB, $USER;
        if (
            during_initial_install() || empty($CFG->allowuserthemes) || empty($USER->id) || isguestuser()
                || !company_style::iomad_installed()
        ) {
            return;
        }
        try {
            $theme = $DB->get_field('user', 'theme', ['id' => $USER->id]);
        } catch (\Throwable $e) {
            return;
        }
        if ($theme !== false && (string) $theme !== (string) ($USER->theme ?? '')) {
            $USER->theme = (string) $theme;
        }
    }

    /**
     * On the IOMAD company form shown with another theme (the theme of the site is IOMAD, for example), the
     * styles of the Épure fields, which the styles of Épure do not bring there: a company can choose Épure
     * whatever the theme of the site.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook The hook.
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $CFG, $PAGE;
        if (
            during_initial_install() || $PAGE->pagetype !== 'blocks-iomad_company_admin-company_edit_form'
                || !company_style::iomad_installed() || self::epure_page()
        ) {
            return;
        }
        $url = new \moodle_url('/theme/epure/css/company_form.css', ['v' => get_config('theme_epure', 'version')]);
        $hook->add_html(\html_writer::empty_tag('link', ['rel' => 'stylesheet', 'href' => $url->out(false)]));
    }

    /**
     * Adds the vocabulary fields to the IOMAD company form (Create company and Edit company).
     *
     * @param \core\hook\output\before_footer_html_generation $hook The hook.
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        global $CFG, $PAGE, $OUTPUT;
        if (
            $PAGE->pagetype !== 'blocks-iomad_company_admin-company_edit_form' || !company_style::iomad_installed()
                || during_initial_install()
        ) {
            return;
        }
        require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
        $companyid = 0;
        if (!optional_param('createnew', 0, PARAM_INT)) {
            $companyid = max(0, (int) \iomad::get_my_companyid(\context_system::instance(), false));
        }
        $context = $companyid ? \core\context\company::instance($companyid) : \context_system::instance();
        if (!\iomad::has_capability('block/iomad_company_admin:company_edit_appearance', $context)) {
            return;
        }
        $hook->add_html($OUTPUT->render_from_template(
            'theme_epure/company_vocabulary_fields',
            \theme_epure\vocabulary\company_form::context($companyid)
        ));
        $PAGE->requires->js_call_amd('theme_epure/company_vocabulary', 'init');
    }

    /**
     * Adds the classes of the display preferences of the user to the html element.
     *
     * @param \core\hook\output\before_html_attributes $hook The hook.
     */
    public static function before_html_attributes(\core\hook\output\before_html_attributes $hook): void {
        if (during_initial_install() || !self::epure_page()) {
            return;
        }
        if ($classes = a11y::html_classes()) {
            $hook->add_attribute('class', $classes);
        }
    }

    /**
     * Adds the footer under the page: the site, the legal links and the accessibility mention
     * (« Accessibility: partially compliant »), which French law (RGAA) asks for on every page;
     * and the navigation bar of the phones.
     *
     * @param \core\hook\output\after_standard_main_region_html_generation $hook The hook.
     */
    public static function after_standard_main_region_html_generation(
        \core\hook\output\after_standard_main_region_html_generation $hook
    ): void {
        global $OUTPUT, $PAGE;
        if (during_initial_install() || !self::epure_page()) {
            return;
        }
        // The navigation bar of the phones, fixed at the bottom of the screen.
        if (mobile_nav::applies($PAGE)) {
            $hook->add_html($OUTPUT->render_from_template('theme_epure/mobile_nav', mobile_nav::export($PAGE)));
        }
        if (footer::enabled()) {
            $hook->add_html($OUTPUT->render_from_template('theme_epure/footer', footer::export()));
        } else if (accessibility_statement::enabled()) {
            $hook->add_html($OUTPUT->render_from_template('theme_epure/a11y_mention', accessibility_statement::mention()));
        }
    }

    /**
     * Whether the page is displayed with Épure (the hooks also run for the other themes).
     *
     * @return bool
     */
    protected static function epure_page(): bool {
        global $PAGE;
        try {
            return $PAGE->theme->name === 'epure' || in_array('epure', $PAGE->theme->parents ?? [], true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
