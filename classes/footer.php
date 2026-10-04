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
 * Footer of every page: the site, a text of the administrator, the legal links and the accessibility mention.
 *
 * Each IOMAD company can set its own text and links, else those of the site are used. Links left
 * empty in the settings are found from Moodle when it can: the site policies for privacy,
 * the support form for contact. The accessibility link and mention appear once the statement is published.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class footer {
    /**
     * Whether the footer is shown.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return get_config('theme_epure', 'footer') !== '0';
    }

    /**
     * Context of the template theme_epure/footer.
     *
     * @return array
     */
    public static function export(): array {
        $setting = [company_style::class, 'footer_setting'];
        $context = \context_system::instance();
        $site = format_string(get_site()->fullname, true, ['context' => $context]);
        $text = $setting('footertext');

        $links = [];
        $add = function (string $label, ?string $url) use (&$links) {
            if ($url) {
                $links[] = ['label' => $label, 'url' => $url];
            }
        };
        $add(get_string('footerlegal', 'theme_epure'), self::url($setting('footerlegalurl')));
        $add(get_string('footerprivacy', 'theme_epure'), self::url($setting('footerprivacyurl')) ?? self::privacy_url());
        if (accessibility_statement::enabled()) {
            $add(get_string('a11ymention', 'theme_epure', get_string(
                'a11ystatus_' . accessibility_statement::status(),
                'theme_epure'
            )), accessibility_statement::url()->out(false));
        }
        $add(get_string('footercontact', 'theme_epure'), self::url($setting('footercontacturl')) ?? self::contact_url());
        // Links of the administrator, one per line: label|address.
        foreach (preg_split('/\R/', $setting('footerlinks')) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if (count($parts) === 2 && $parts[0] !== '') {
                $add(format_string($parts[0], true, ['context' => $context]), self::url($parts[1]));
            }
        }

        return [
            'site' => $site,
            'text' => $text === '' ? '' : format_text($text, FORMAT_MARKDOWN, ['context' => $context]),
            'links' => $links,
            'haslinks' => !empty($links),
            'copyright' => get_string('footercopyright', 'theme_epure', (object) ['year' => date('Y'), 'site' => $site]),
        ];
    }

    /**
     * A web address entered by the administrator, or null when it is empty or not valid.
     *
     * @param string $value Address.
     * @return string|null
     */
    protected static function url(string $value): ?string {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $clean = clean_param($value, PARAM_URL);
        return $clean === '' ? null : (new \moodle_url($clean))->out(false);
    }

    /**
     * Page of the privacy policies of the site, when Moodle has one.
     *
     * @return string|null
     */
    protected static function privacy_url(): ?string {
        global $CFG;
        if (($CFG->sitepolicyhandler ?? '') === 'tool_policy') {
            return (new \moodle_url('/admin/tool/policy/viewall.php'))->out(false);
        }
        if (!empty($CFG->sitepolicy)) {
            return self::url($CFG->sitepolicy);
        }
        if (get_config('tool_dataprivacy', 'showdataretentionsummary')) {
            return (new \moodle_url('/admin/tool/dataprivacy/summary.php'))->out(false);
        }
        return null;
    }

    /**
     * Page to contact the support of the site, when it is open to the user.
     *
     * @return string|null
     */
    protected static function contact_url(): ?string {
        global $CFG;
        $availability = (int) ($CFG->supportavailability ?? CONTACT_SUPPORT_AUTHENTICATED);
        if (
            $availability === CONTACT_SUPPORT_ANYONE
                || ($availability === CONTACT_SUPPORT_AUTHENTICATED && isloggedin() && !isguestuser())
        ) {
            return !empty($CFG->supportpage) ? self::url($CFG->supportpage)
                : (new \moodle_url('/user/contactsitesupport.php'))->out(false);
        }
        return null;
    }
}
