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
 * E-mails in the colours of the brand: the HTML e-mails of Moodle (and of IOMAD) wrapped with the logo,
 * a band of the brand colour and a footer, those of the IOMAD company of the recipient when they have one.
 *
 * Moodle renders every HTML e-mail with the template core/email_html; Épure gives it this context.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email {
    /**
     * Whether the e-mails are branded.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return get_config('theme_epure', 'emailbranding') !== '0';
    }

    /**
     * IOMAD company of a user, or null on a standard Moodle site or without a company.
     *
     * @param int $userid User.
     * @return \stdClass|null
     */
    public static function company_of(int $userid): ?\stdClass {
        global $DB;
        if (!$userid || !company_style::iomad_installed() || !$DB->get_manager()->table_exists('company_users')) {
            return null;
        }
        $companyid = $DB->get_field_sql(
            'SELECT MIN(companyid) FROM {company_users} WHERE userid = :userid',
            ['userid' => $userid]
        );
        return $companyid ? ($DB->get_record('company', ['id' => $companyid]) ?: null) : null;
    }

    /**
     * Context added to the template core/email_html.
     *
     * @param int $userid Recipient, 0 when unknown.
     * @return array
     */
    public static function export(int $userid): array {
        global $CFG;
        $company = self::company_of($userid);
        $settings = $company ? company_style::settings((int) $company->id) : [];
        $brand = ($company ? company_style::brand_colour($company) : null)
            ?? palette::normalise(get_config('theme_epure', 'brandcolor'));
        $palette = palette::derive($brand);

        $logo = $company ? company_style::logo_url((int) $company->id) : null;
        $logo = $logo ?? logos::url('logo');
        if (!$logo) {
            foreach (['logo', 'logocompact'] as $setting) {
                if (($file = (string) get_config('core_admin', $setting)) !== '') {
                    $logo = \moodle_url::make_pluginfile_url(
                        \context_system::instance()->id,
                        'core_admin',
                        $setting,
                        '300x200/',
                        theme_get_revision(),
                        $file
                    );
                    break;
                }
            }
        }

        $context = \context_system::instance();
        $site = format_string(get_site()->fullname, true, ['context' => $context]);
        $name = $company ? format_string($company->name, true, ['context' => $context]) : $site;
        $text = trim((string) (($settings['footertext'] ?? '') !== '' ? $settings['footertext']
            : get_config('theme_epure', 'footertext')));
        return [
            'brand' => $palette['fill'],
            'brandtext' => $palette['text'],
            'name' => $name,
            'logo' => $logo ? $logo->out(false) : '',
            'siteurl' => $CFG->wwwroot . '/',
            'footertext' => $text === '' ? '' : html_to_text(
                format_text($text, FORMAT_MARKDOWN, ['context' => $context, 'filter' => false]),
                0,
                false
            ),
            'preferencesurl' => (new \moodle_url('/message/notificationpreferences.php'))->out(false),
        ];
    }
}
