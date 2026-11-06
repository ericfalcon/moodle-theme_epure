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
        return get_string('a11ymodel_' . self::model(), 'theme_epure', (object) [
            'country' => $country !== '' && isset($countries[$country]) ? $countries[$country]
                : get_string('a11ymodel_nocountry', 'theme_epure'),
            'url' => (new \moodle_url('/admin/settings.php', ['section' => 'locationsettings']))->out(false),
        ]);
    }

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
     * Address of the guided form, at a step.
     *
     * @param int $step Step, from 1.
     * @return \moodle_url
     */
    public static function edit_url(int $step = 1): \moodle_url {
        return new \moodle_url('/theme/epure/a11ystatement.php', ['step' => $step]);
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
