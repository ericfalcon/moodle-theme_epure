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

    /** @var int Number of accessibility measures of the theme, strings a11ystatement_measure_01 and following. */
    public const MEASURES = 10;

    /** @var string[] Free text settings of the statement. */
    public const TEXTS = ['noncompliant', 'derogations', 'disproportionate', 'contact'];

    /** @var array<string, string[]> Steps of the guided form, each with its settings, in the order of the statement. */
    public const STEPS = [
        'entity' => ['a11yentity', 'a11ystatementdate'],
        'audit' => ['a11yauditor', 'a11yauditdate', 'a11yrate'],
        'status' => ['a11ystatus'],
        'contents' => ['a11ynoncompliant', 'a11yderogations', 'a11ydisproportionate'],
        'contact' => ['a11ycontact'],
    ];

    /** @var string[] Countries of the European Union and of the European Economic Area (directive (EU) 2016/2102). */
    public const EUROPE = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'GR', 'HR', 'HU', 'IE', 'IS', 'IT',
        'LI', 'LT', 'LU', 'LV', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];

    /** @var string[] France and its overseas territories, where the RGAA applies. */
    public const FRANCE = ['FR', 'GF', 'GP', 'MQ', 'RE', 'YT', 'PM', 'BL', 'MF', 'WF', 'PF', 'NC', 'TF'];

    /**
     * The model of the statement, from the default country of the site (Location settings): the French one (RGAA, law
     * of 2005, Défenseur des droits) in France or without a country, the European one (EN 301 549, directive (EU)
     * 2016/2102) in the rest of the European Union and of the EEA, the WCAG 2.1 AA elsewhere.
     *
     * @return string fr, eu or world.
     */
    public static function model(): string {
        global $CFG;
        $country = strtoupper((string) ($CFG->country ?? ''));
        if ($country === '' || in_array($country, self::FRANCE, true)) {
            return 'fr';
        }
        return in_array($country, self::EUROPE, true) ? 'eu' : 'world';
    }

    /**
     * A string of the statement in its model: « name_eu » or « name_world » when it exists, else « name » (France).
     *
     * @param string $name Name of the string.
     * @param mixed $a Value of the string.
     * @return string
     */
    public static function string(string $name, $a = null): string {
        $model = self::model();
        $variant = $name . '_' . $model;
        return get_string($model !== 'fr' && get_string_manager()->string_exists($variant, 'theme_epure')
            ? $variant : $name, 'theme_epure', $a);
    }

    /**
     * Which model the statement follows, and where to change it: for the settings and the guided form.
     *
     * @return string
     */
    public static function model_note(): string {
        global $CFG;
        $country = (string) ($CFG->country ?? '');
        $countries = get_string_manager()->get_list_of_countries();
        $note = get_string('a11ymodel_' . self::model(), 'theme_epure', (object) [
            'country' => $country !== '' && isset($countries[$country]) ? $countries[$country]
                : get_string('a11ymodel_nocountry', 'theme_epure'),
            'url' => (new \moodle_url('/admin/settings.php', ['section' => 'locationsettings']))->out(false),
        ]) . ' ' . get_string('a11ymodel_legal', 'theme_epure');
        // With IOMAD, one statement for the whole platform: a choice to make knowingly.
        return iomad::installed() ? $note . ' ' . get_string('a11ymodel_iomad', 'theme_epure') : $note;
    }

    /**
     * The names of the settings of a statement, in the order of its steps.
     *
     * @return string[]
     */
    public static function names(): array {
        return array_merge(...array_values(self::STEPS));
    }

    /**
     * Name of the setting holding the own statement of an IOMAD company, in JSON.
     *
     * @param int $companyid Company.
     * @return string
     */
    public static function company_setting(int $companyid): string {
        return 'companya11y_' . $companyid;
    }

    /**
     * The settings of a statement: those of the platform (0), or the own statement of an IOMAD company (empty values
     * when it has none).
     *
     * @param int $companyid Company, 0 for the platform.
     * @return \stdClass The settings by name ({@see self::names()}).
     */
    public static function values(int $companyid = 0): \stdClass {
        $values = [];
        if ($companyid) {
            $json = get_config('theme_epure', self::company_setting($companyid));
            $own = $json ? json_decode($json, true) : [];
            foreach (self::names() as $name) {
                $values[$name] = is_array($own) ? trim((string) ($own[$name] ?? '')) : '';
            }
        } else {
            $config = get_config('theme_epure');
            foreach (self::names() as $name) {
                $values[$name] = trim((string) ($config->$name ?? ''));
            }
        }
        return (object) $values;
    }

    /**
     * Saves settings of the own statement of an IOMAD company; the others it holds stay.
     *
     * @param int $companyid Company.
     * @param array $values Settings by name ({@see self::names()}).
     */
    public static function save_company(int $companyid, array $values): void {
        $own = array_intersect_key($values, array_flip(self::names())) + (array) self::values($companyid);
        if (array_filter($own, fn($value) => $value !== '')) {
            set_config(self::company_setting($companyid), json_encode($own), 'theme_epure');
        } else {
            unset_config(self::company_setting($companyid), 'theme_epure');
        }
    }

    /**
     * The IOMAD company whose own statement applies on the page: the company of the user, or of the login page opened,
     * when it published one; else 0, the statement of the platform.
     *
     * @return int
     */
    public static function company(): int {
        $company = company_style::current_company();
        return $company && self::status((int) $company->id) !== null ? (int) $company->id : 0;
    }

    /**
     * Whether an IOMAD company can have a statement of its own: IOMAD installed and the company existing.
     *
     * @param int $companyid Company.
     * @return bool
     */
    public static function company_exists(int $companyid): bool {
        return $companyid > 0 && iomad::installed() && iomad::company($companyid) !== null;
    }

    /**
     * The compliance status of a statement.
     *
     * @param int|null $companyid IOMAD company, 0 for the platform; null for the statement that applies on the page.
     * @return string|null One of {@see self::STATUSES}, or null when the statement is not published.
     */
    public static function status(?int $companyid = null): ?string {
        $status = self::values($companyid ?? self::company())->a11ystatus;
        return in_array($status, self::STATUSES, true) ? $status : null;
    }

    /**
     * Whether the statement that applies on the page is published: the one of the company, else the one of the platform.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return self::status() !== null;
    }

    /**
     * Address of the guided form, at a step.
     *
     * @param int $step Step, from 1.
     * @param int $companyid IOMAD company, 0 for the platform.
     * @return \moodle_url
     */
    public static function edit_url(int $step = 1, int $companyid = 0): \moodle_url {
        return new \moodle_url('/theme/epure/a11ystatement.php', array_filter(['step' => $step, 'companyid' => $companyid]));
    }

    /**
     * Address of the statement.
     *
     * @param int|null $companyid IOMAD company, 0 for the platform; null for the statement that applies on the page.
     * @return \moodle_url
     */
    public static function url(?int $companyid = null): \moodle_url {
        $companyid = $companyid ?? self::company();
        return new \moodle_url('/theme/epure/accessibility.php', $companyid ? ['companyid' => $companyid] : []);
    }

    /**
     * Whether a user can change the statement of the platform (0, the administrators) or the own statement of an IOMAD
     * company (those who change its appearance).
     *
     * @param int $companyid Company, 0 for the platform.
     * @return bool
     */
    public static function can_edit(int $companyid): bool {
        if (!$companyid) {
            return has_capability('moodle/site:config', \context_system::instance());
        }
        return self::company_exists($companyid) && iomad::has_capability(
            'block/iomad_company_admin:company_edit_appearance',
            iomad::company_context($companyid)
        );
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
     * @param int $companyid IOMAD company whose own statement it is, 0 for the platform.
     * @return array
     */
    public static function export(int $companyid = 0): array {
        $config = self::values($companyid);
        $company = $companyid ? iomad::company($companyid) : null;
        $name = $company ? format_string($company->name, true, ['context' => \context_system::instance()])
            : format_string(get_site()->fullname);
        $entity = $config->a11yentity ?: $name;
        $status = self::status($companyid);
        $texts = [];
        foreach (self::TEXTS as $name) {
            $value = trim((string) ($config->{'a11y' . $name} ?? ''));
            if ($name === 'contact') {
                // Email addresses written as such become links (Markdown automatic links).
                $value = preg_replace('/(?<![<\w.@:\/])([\w.+-]+@[\w-]+(?:\.[\w-]+)+)(?![\w>])/u', '<$1>', $value);
            }
            $texts[$name] = $value === '' ? '' : format_text($value, FORMAT_MARKDOWN, ['context' => \context_system::instance()]);
        }
        $rate = trim((string) ($config->a11yrate ?? ''));
        return [
            'published' => $status !== null,
            'entity' => $entity,
            'site' => format_string(get_site()->fullname),
            // The own statement of an IOMAD company: for its users, and on its pages.
            'company' => $company ? $name : '',
            'siteurl' => (new \moodle_url('/'))->out(false),
            'status' => $status ? get_string('a11ystatus_' . $status, 'theme_epure') : '',
            'statusphrase' => $status ? self::string('a11ystatement_status_' . $status) : '',
            'intro' => self::string('a11ystatement_intro', $entity),
            'hasaudit' => $rate !== '',
            'rate' => s($rate),
            'ratetext' => self::string('a11ystatement_rate', s($rate)),
            // The remedies: the Défenseur des droits in France, the body of the country in Europe, none set elsewhere.
            'france' => self::model() === 'fr',
            'remedies' => self::model() === 'world' ? '' : self::string('a11ystatement_remedies_text'),
            'auditor' => s(trim((string) ($config->a11yauditor ?? ''))),
            'auditdate' => s(trim((string) ($config->a11yauditdate ?? ''))),
            'statementdate' => s(trim((string) ($config->a11ystatementdate ?? ''))),
            'noncompliant' => $texts['noncompliant'],
            'derogations' => $texts['derogations'],
            'disproportionate' => $texts['disproportionate'],
            'contact' => $texts['contact'] ?: self::default_contact(),
            'measures' => array_map(
                fn($i) => get_string(sprintf('a11ystatement_measure_%02d', $i), 'theme_epure'),
                range(1, self::MEASURES)
            ),
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
