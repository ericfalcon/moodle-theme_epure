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

namespace theme_epure\output;

/**
 * Core renderer for Épure: uses the theme logos when they are set.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    use email_renderer;

    /**
     * Logo shown in the header.
     *
     * When the header uses the brand colour, the logo for coloured backgrounds is preferred.
     * Falls back to the compact logo set in Appearance > Logos.
     *
     * @param int $maxwidth Maximum width, used by the core logo only.
     * @param int $maxheight Maximum height, used by the core logo only.
     * @return \moodle_url|false
     */
    public function get_compact_logo_url($maxwidth = 300, $maxheight = 300) {
        // The logo of the user's IOMAD company comes first: its version for the brand-coloured header when it applies.
        $company = \theme_epure\company_style::current_company();
        if (
            $company && \theme_epure\company_style::header_style() === 'brand'
                && ($url = \theme_epure\company_style::logo_onbrand_url((int) $company->id))
        ) {
            return $url;
        }
        if ($url = $this->company_logo_url(['logocompact', 'logo'])) {
            return $url;
        }
        $settings = \theme_epure\company_style::header_style() === 'brand' ? ['logoonbrand', 'logo'] : ['logo'];
        foreach ($settings as $setting) {
            if ($url = $this->theme_file_url($setting)) {
                return $url;
            }
        }
        return parent::get_compact_logo_url($maxwidth, $maxheight);
    }

    /**
     * Logo shown on the login page and the site home.
     *
     * Falls back to the logo set in Appearance > Logos.
     *
     * @param int|null $maxwidth Maximum width, used by the core logo only.
     * @param int $maxheight Maximum height, used by the core logo only.
     * @return \moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        if ($url = $this->company_logo_url(['logo', 'logocompact'])) {
            return $url;
        }
        if ($url = $this->theme_file_url('logo')) {
            return $url;
        }
        return parent::get_logo_url($maxwidth, $maxheight);
    }

    /**
     * Head of the page, with the appearance of the user's IOMAD company when there is one.
     *
     * The company styles depend on the user, so they are added to the page rather than to
     * the cached theme stylesheet, as IOMAD's own theme does.
     *
     * @return string HTML
     */
    public function standard_head_html() {
        $output = parent::standard_head_html();
        $company = \theme_epure\company_style::current_company();
        if ($company && ($css = \theme_epure\company_style::css($company)) !== '') {
            $output .= \html_writer::tag('style', $css, ['id' => 'epure-company-style']);
        }
        // The installable web app: its manifest and its service worker; once turned off, the service worker is removed.
        if (\theme_epure\web_app::enabled()) {
            $output .= \theme_epure\web_app::head_html();
        }
        if (!during_initial_install() && ($js = \theme_epure\web_app::register_js()) !== '') {
            $output .= \html_writer::script($js);
        }
        // The logo for the phones, in place of the logo of the header (Boost shows no logo on phones).
        if (!$this->company_logo_url(['logocompact', 'logo']) && ($url = \theme_epure\logos::url('logomobile'))) {
            $output .= \html_writer::tag('style', '@media (max-width: 767.98px) {'
                . '.navbar .navbar-brand .logo { content: url("' . $url->out(false) . '"); } }', ['id' => 'epure-mobile-logo']);
        }
        return $output;
    }

    /**
     * Icon of the site in the tabs of the browsers: the one of the theme, else the one of Moodle.
     *
     * @return \moodle_url
     */
    public function favicon() {
        if (!during_initial_install() && ($url = \theme_epure\logos::url('favicon'))) {
            return $url;
        }
        return parent::favicon();
    }

    /**
     * Header of the page; on the page of a course, inside a banner with the image of the course and,
     * depending on the role of the user, their progress or the figures of the course; on the dashboard,
     * followed by the overview of the learner.
     *
     * @return string HTML.
     */
    public function full_header() {
        global $USER;
        $header = parent::full_header();
        if (\theme_epure\iomad_figures::site_applies($this->page)) {
            $header .= \html_writer::div($this->render_from_template('theme_epure/figures', [
                'label' => get_string('sitefigures', 'theme_epure'),
                'figures' => \theme_epure\iomad_figures::site(),
            ]), 'epure-site-figures');
        }
        if (\theme_epure\iomad_courses::applies($this->page)) {
            $this->page->requires->js_call_amd('theme_epure/iomad_courses', 'init', [get_string('category')]);
            $header .= \html_writer::tag(
                'script',
                json_encode(\theme_epure\iomad_courses::data(), JSON_HEX_TAG | JSON_HEX_AMP),
                ['type' => 'application/json', 'id' => 'epure-iomad-course-categories']
            );
        }
        if (\theme_epure\course_presentation::applies($this->page)) {
            return $this->render_from_template(
                'theme_epure/course_presentation',
                \theme_epure\course_presentation::export($this->page->course, $this, $header)
            );
        }
        if (\theme_epure\activity_page::applies($this->page)) {
            $this->page->requires->js_call_amd('theme_epure/activity_page', 'init');
            return $header . $this->render_from_template(
                'theme_epure/activity_strip',
                \theme_epure\activity_page::strip($this->page->cm, (int) $USER->id)
            );
        }
        if (\theme_epure\learner_dashboard::applies($this->page)) {
            $data = \theme_epure\learner_dashboard::export($this);
            return $data ? $header . $this->render_from_template('theme_epure/learner_dashboard', $data) : $header;
        }
        // The progress of the sections, with or without the banner.
        if (\theme_epure\course_page::section_progress_applies($this->page, (int) $USER->id)) {
            $sections = \theme_epure\course_page::sections($this->page->course, (int) $USER->id);
            if ($sections) {
                $this->page->requires->js_call_amd('theme_epure/course_page', 'init', [$sections]);
            }
        }
        if (!\theme_epure\course_page::applies($this->page)) {
            return $header;
        }
        $data = \theme_epure\course_page::export($this, $this->page->course, $header);
        return $this->render_from_template('theme_epure/course_banner', $data);
    }

    /**
     * Previous and next activities at the bottom of the page of an activity, as cards.
     *
     * Moodle shows none when the course index is there; Épure always shows them, without the menu
     * to jump to an activity, which the course index replaces.
     *
     * @return string HTML.
     */
    public function activity_navigation() {
        if (!\theme_epure\activity_page::applies($this->page)) {
            return parent::activity_navigation();
        }
        $data = \theme_epure\activity_page::navigation($this->page->cm);
        return $data ? $this->render_from_template('theme_epure/activity_navigation', $data) : '';
    }

    /**
     * Top of the page; on the pages that install or upgrade Moodle and its plugins, a message while the
     * server sends the page.
     *
     * @return string HTML.
     */
    public function standard_top_of_body_html() {
        return parent::standard_top_of_body_html() . \theme_epure\busy::top_of_body($this->page)
            . \theme_epure\activity_icons::filters();
    }

    /**
     * End of the page; on the pages that install or upgrade Moodle and its plugins, an indicator that Moodle
     * is working once a step is started, as the server can take minutes to answer.
     *
     * @return string HTML.
     */
    public function standard_end_of_body_html() {
        $output = parent::standard_end_of_body_html();
        // The page of the messages reuses the template of the drawer, hidden from assistive technologies
        // (aria-hidden) although the page shows it: the theme shows it to them too, and names the region.
        if ($this->page->pagetype === 'message-index') {
            $output .= \html_writer::script('document.querySelectorAll(\'[data-region="message-index"]\').forEach(function(e) {'
                . 'e.removeAttribute("aria-hidden"); e.removeAttribute("aria-expanded"); e.setAttribute("aria-label", '
                . json_encode(get_string('messages', 'message')) . '); });');
        }
        return $output . \theme_epure\busy::end_of_body($this->page);
    }

    /**
     * Output of the plugins in the header, with the button of the quick search and the button « Aa » of the
     * display preferences first.
     *
     * @return string HTML.
     */
    public function navbar_plugin_output() {
        $output = parent::navbar_plugin_output();
        // The preferences are saved in the profile: guests and visitors who are not logged in do not get them.
        if (isloggedin() && !isguestuser() && !during_initial_install() && \theme_epure\a11y::panel_enabled()) {
            $this->page->requires->js_call_amd('theme_epure/a11y_panel', 'init');
            $output = $this->render_from_template('theme_epure/a11y_panel', \theme_epure\a11y::panel_context()) . $output;
        }
        if (\theme_epure\quick_search::enabled() && !during_initial_install()) {
            $this->page->requires->js_call_amd('theme_epure/quick_search', 'init');
            $output = $this->render_from_template('theme_epure/quick_search', ['shortcut' => 'Ctrl K']) . $output;
        }
        return $output;
    }

    /**
     * Footer links, with the accessibility statement when it is published.
     *
     * @return string HTML.
     */
    public function standard_footer_html() {
        $output = parent::standard_footer_html();
        // The footer of Épure already has the link; without it, the link goes in Moodle's footer.
        if (\theme_epure\accessibility_statement::enabled() && !\theme_epure\footer::enabled()) {
            $output .= \html_writer::div(\html_writer::link(
                \theme_epure\accessibility_statement::url(),
                get_string('a11ystatement', 'theme_epure')
            ), 'epure-a11y-footer-link');
        }
        return $output;
    }

    /**
     * Attributes of the body element, with the class of the brand-coloured header when it applies.
     *
     * The header style is the one of the IOMAD company of the user, else the theme setting.
     *
     * @param string|array $additionalclasses Extra classes.
     * @return string
     */
    public function body_attributes($additionalclasses = []) {
        if (!is_array($additionalclasses)) {
            $additionalclasses = explode(' ', (string) $additionalclasses);
        }
        if (\theme_epure\company_style::header_style() === 'brand') {
            $additionalclasses[] = 'epure-header-brand';
        }
        if (\theme_epure\activity_page::applies($this->page)) {
            $additionalclasses[] = 'epure-activity';
        }
        if (\theme_epure\mobile_nav::applies($this->page)) {
            $additionalclasses[] = 'epure-has-mobilenav';
        }
        if (\theme_epure\quick_search::enabled() && !during_initial_install()) {
            $additionalclasses[] = 'epure-has-quicksearch';
        }
        if (!during_initial_install() && \theme_epure\activity_icons::current() !== 'moodle') {
            $additionalclasses[] = 'epure-icons-' . \theme_epure\activity_icons::current();
        }
        if (!during_initial_install()) {
            $additionalclasses = array_merge($additionalclasses, $this->epure_display_classes());
        }
        return parent::body_attributes($additionalclasses);
    }

    /**
     * Classes of the body for the settings of the display: breadcrumb, logo, blocks and dashboard of the phones.
     *
     * @return string[]
     */
    protected function epure_display_classes(): array {
        $classes = [];
        $breadcrumb = \theme_epure\company_style::choice('breadcrumb');
        if ($breadcrumb === 'hide' || $breadcrumb === 'desktop') {
            $classes[] = 'epure-breadcrumb-' . $breadcrumb;
        }
        if ($this->company_logo_url(['logocompact', 'logo']) || \theme_epure\logos::url('logomobile')) {
            $classes[] = 'epure-has-mobilelogo';
        }
        if (\theme_epure\company_style::choice('mobileblocks') === 'hidden') {
            $classes[] = 'epure-mobile-noblocks';
        }
        if (
            \theme_epure\company_style::choice('mobiledashboard') === 'overview'
                && \theme_epure\learner_dashboard::applies($this->page)
        ) {
            $classes[] = 'epure-mobile-overview';
        }
        return $classes;
    }

    /**
     * Logo of the user's IOMAD company, stored by IOMAD as core_admin/logo<id> and logocompact<id>.
     *
     * Resolved from the user's company, so it also works for company users, not only for
     * administrators who selected a company.
     *
     * @param string[] $settings Settings to try, in order.
     * @return \moodle_url|null
     */
    public function company_logo_url(array $settings): ?\moodle_url {
        $company = \theme_epure\company_style::current_company();
        if (!$company) {
            return null;
        }
        return \theme_epure\company_style::logo_url((int) $company->id, $settings);
    }

    /**
     * Footer of the login page, which has no main region hook: the same as on the other pages.
     *
     * @return string HTML.
     */
    public function epure_footer(): string {
        if (\theme_epure\footer::enabled()) {
            return $this->render_from_template('theme_epure/footer', \theme_epure\footer::export());
        }
        if (\theme_epure\accessibility_statement::enabled()) {
            return $this->render_from_template('theme_epure/a11y_mention', \theme_epure\accessibility_statement::mention());
        }
        return '';
    }

    /**
     * Whether the login page uses the split layout, with the brand visual beside the form.
     *
     * @return bool
     */
    public function epure_login_split(): bool {
        return \theme_epure\company_style::choice('loginlayout') !== 'centered';
    }

    /**
     * Full name of the site, shown beside the logo on the login visual: the name of the IOMAD company on its login page.
     *
     * @return string
     */
    public function epure_login_sitename(): string {
        global $SITE;
        $company = \theme_epure\company_style::current_company();
        return format_string($company->name ?? $SITE->fullname, true, ['context' => \context_system::instance()]);
    }

    /**
     * Headline shown on the login visual.
     *
     * @return string
     */
    public function epure_login_tagline(): string {
        $tagline = \theme_epure\company_style::footer_setting('logintagline');
        if ($tagline === '') {
            return get_string('logintagline_default', 'theme_epure');
        }
        return format_string($tagline, true, ['context' => \context_system::instance()]);
    }

    /**
     * Supporting text shown under the headline, or an empty string.
     *
     * @return string
     */
    public function epure_login_text(): string {
        $text = \theme_epure\company_style::footer_setting('logintext');
        return $text === '' ? '' : format_string($text, true, ['context' => \context_system::instance()]);
    }

    /**
     * URL of the login background image, or an empty string.
     *
     * @return string
     */
    public function epure_login_image_url(): string {
        $url = $this->theme_file_url('loginimage');
        return $url ? $url->out(false) : '';
    }

    /**
     * Logo shown on the brand-coloured login visual, or an empty string.
     *
     * The logo for coloured backgrounds is preferred; otherwise the main logo is shown on a white backing.
     *
     * @return string
     */
    public function epure_login_logo_url(): string {
        $url = $this->theme_file_url('logoonbrand') ?? \theme_epure\logos::main_url();
        return $url ? $url->out(false) : '';
    }

    /**
     * Whether the login logo needs a white backing, because it is not made for coloured backgrounds.
     *
     * @return bool
     */
    public function epure_login_logo_backing(): bool {
        return $this->theme_file_url('logoonbrand') === null;
    }

    /**
     * URL of a file uploaded in a theme setting, where the file area has the setting name.
     *
     * @param string $setting Setting name.
     * @return \moodle_url|null Null when no file is set.
     */
    protected function theme_file_url(string $setting): ?\moodle_url {
        return \theme_epure\logos::url($setting);
    }
}
