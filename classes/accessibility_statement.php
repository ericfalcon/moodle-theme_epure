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
 * Accessibility statement of the platform, in the French format (RGAA), and its mention on every page.
 *
 * The administrator fills it in the theme settings (Accessibility tab). Until a compliance status
 * is chosen, the statement is not published and the mention is not shown.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accessibility_statement {
    /** @var string[] Compliance statuses, as defined by the RGAA. */
    public const STATUSES = ['none', 'partial', 'full'];

    /** @var string[] Free text settings of the statement. */
    public const TEXTS = ['noncompliant', 'derogations', 'disproportionate', 'contact'];

    /**
     * The compliance status chosen by the administrator.
     *
     * @return string|null One of {@see self::STATUSES}, or null when the statement is not published.
     */
    public static function status(): ?string {
        $status = (string) get_config('theme_epure', 'a11ystatus');
        return in_array($status, self::STATUSES, true) ? $status : null;
    }

    /**
     * Whether the statement is published.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return self::status() !== null;
    }

    /**
     * Address of the statement.
     *
     * @return \moodle_url
     */
    public static function url(): \moodle_url {
        return new \moodle_url('/theme/epure/accessibility.php');
    }

    /**
     * Context of the template theme_epure/a11y_mention.
     *
     * @return array
     */
    public static function mention(): array {
        return [
            'status' => get_string('a11ystatus_' . self::status(), 'theme_epure'),
            'url' => self::url()->out(false),
        ];
    }

    /**
     * Context of the template theme_epure/accessibility_statement.
     *
     * @return array
     */
    public static function export(): array {
        $config = get_config('theme_epure');
        $entity = trim((string) ($config->a11yentity ?? '')) ?: format_string(get_site()->fullname);
        $status = self::status();
        $texts = [];
        foreach (self::TEXTS as $name) {
            $value = trim((string) ($config->{'a11y' . $name} ?? ''));
            $texts[$name] = $value === '' ? '' : format_text($value, FORMAT_MARKDOWN, ['context' => \context_system::instance()]);
        }
        $rate = trim((string) ($config->a11yrate ?? ''));
        return [
            'published' => $status !== null,
            'entity' => $entity,
            'site' => format_string(get_site()->fullname),
            'siteurl' => (new \moodle_url('/'))->out(false),
            'status' => $status ? get_string('a11ystatus_' . $status, 'theme_epure') : '',
            'statusphrase' => $status ? get_string('a11ystatement_status_' . $status, 'theme_epure') : '',
            'hasaudit' => $rate !== '',
            'rate' => s($rate),
            'auditor' => s(trim((string) ($config->a11yauditor ?? ''))),
            'auditdate' => s(trim((string) ($config->a11yauditdate ?? ''))),
            'statementdate' => s(trim((string) ($config->a11ystatementdate ?? ''))),
            'noncompliant' => $texts['noncompliant'],
            'derogations' => $texts['derogations'],
            'disproportionate' => $texts['disproportionate'],
            'contact' => $texts['contact'] ?: self::default_contact(),
        ];
    }

    /**
     * Contact given when the administrator has not entered one: the support email, else the support form.
     *
     * @return string HTML.
     */
    public static function default_contact(): string {
        global $CFG;
        if (!empty($CFG->supportemail)) {
            return \html_writer::link('mailto:' . $CFG->supportemail, s($CFG->supportemail));
        }
        $url = new \moodle_url('/user/contactsitesupport.php');
        return \html_writer::link($url, get_string('contactsitesupport', 'admin'));
    }
}
