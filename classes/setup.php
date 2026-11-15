<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * Getting started with Épure, for the administrator: the tabs of the settings of the theme that matter most on a new
 * site, one after the other, as they are in the settings (Identity: the name of the site, the logos, the colours and
 * their palette; the login page, with a model by sector; the footer), then the accessibility statement. The same
 * settings and the same fields as the tabs, saved as the page of the settings saves them: the getting started adds
 * only the steps, the name of the site and the models. Offered on the home of the administration until it is
 * finished or dismissed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /** @var array<string, string|null> The steps, in their order: the tab of the settings of the theme each shows. */
    public const STEPS = [
        'identity' => 'theme_epure_brand',
        'login' => 'theme_epure_login',
        'footer' => 'theme_epure_footer',
        'accessibility' => null,
    ];

    /** @var string[] The settings of the site at the top of step 1: its full and short names. */
    public const SITE_SETTINGS = ['fullname', 'shortname'];

    /** @var string[] The models of the login page, by sector: its tagline and its text, and whether the catalogue shows. */
    public const MODELS = ['university', 'company', 'school', 'training', 'administration'];

    /** @var bool[] Whether the catalogue of the courses shows, by model: open offers yes, internal training no. */
    public const CATALOGUE = ['university' => true, 'company' => false, 'school' => false, 'training' => true,
        'administration' => false];

    /** @var string Configuration of the theme: the getting started is finished or dismissed (not a setting). */
    public const DONE = 'setupdone';

    /**
     * Address of the page of the getting started, at a step.
     *
     * @param int $step Step, from 1.
     * @return \moodle_url
     */
    public static function url(int $step = 1): \moodle_url {
        return new \moodle_url('/theme/epure/setup.php', ['step' => $step]);
    }

    /**
     * Whether the getting started is offered: to an administrator of the site, until it is finished or dismissed.
     *
     * @return bool
     */
    public static function offered(): bool {
        return !get_config('theme_epure', self::DONE) && has_capability('moodle/site:config', \context_system::instance());
    }

    /**
     * Marks the getting started as finished or dismissed: it is not offered any more (the page stays open).
     */
    public static function finish(): void {
        set_config(self::DONE, 1, 'theme_epure');
    }

    /**
     * The tab of the settings of the theme that a step shows, as in the settings: null for the accessibility statement.
     *
     * @param string $step Step.
     * @return \admin_settingpage|null
     */
    public static function page(string $step): ?\admin_settingpage {
        $name = self::STEPS[$step] ?? null;
        $settings = $name ? self::root()->locate('themesettingepure') : null;
        if (!$settings instanceof \theme_boost_admin_settingspage_tabs) {
            return null;
        }
        foreach ($settings->get_tabs() as $tab) {
            if ($tab->name === $name) {
                return $tab;
            }
        }
        return null;
    }

    /**
     * The settings of the full and the short names of the site, as on the page of the front page.
     *
     * @return \admin_setting[]
     */
    public static function site_settings(): array {
        $page = self::root()->locate('frontpagesettings');
        $settings = [];
        foreach (self::SITE_SETTINGS as $name) {
            if ($page instanceof \admin_settingpage && isset($page->settings->$name)) {
                $settings[] = $page->settings->$name;
            }
        }
        return $settings;
    }

    /**
     * The tagline and the text of the login page of a model, in the language of the site.
     *
     * @param string $model Model.
     * @return array{logintagline: string, logintext: string}
     */
    public static function model_texts(string $model): array {
        return [
            'logintagline' => get_string('setup_model_' . $model . '_tagline', 'theme_epure'),
            'logintext' => get_string('setup_model_' . $model . '_text', 'theme_epure'),
        ];
    }

    /**
     * The data of the login page with a model: it fills the tagline and the text left empty (what the administrator
     * wrote stays), and decides whether the catalogue shows.
     *
     * @param array $data Data submitted, as the page of the settings receives it (s_theme_epure_…).
     * @return array
     */
    public static function apply_model(array $data): array {
        $model = (string) ($data['model'] ?? '');
        if (!in_array($model, self::MODELS, true)) {
            return $data;
        }
        foreach (self::model_texts($model) as $name => $text) {
            $key = 's_theme_epure_' . $name;
            if (trim((string) ($data[$key] ?? '')) === '') {
                $data[$key] = $text;
            }
        }
        set_config('catalogue', (int) self::CATALOGUE[$model], 'theme_epure');
        return $data;
    }

    /**
     * Saves a step as the page of the settings saves its settings (validation, callbacks, log of the changes).
     *
     * @param string $step Step.
     * @param array $data Data submitted.
     * @return bool Whether all was saved; the errors are kept in the tree of the administration, shown with the fields.
     */
    public static function save(string $step, array $data): bool {
        if ($step === 'login') {
            $data = self::apply_model($data);
        }
        self::root()->errors = [];
        admin_write_settings($data);
        return empty(self::root()->errors);
    }

    /**
     * The HTML of the settings of a step, as the page of the settings shows them: a setting not saved keeps what was
     * submitted, with its error.
     *
     * @param \admin_setting[] $settings Settings.
     * @return string
     */
    public static function settings_html(array $settings): string {
        $errors = self::root()->errors;
        $html = '';
        foreach ($settings as $setting) {
            $fullname = $setting->get_full_name();
            $data = array_key_exists($fullname, $errors) ? $errors[$fullname]->data : $setting->get_setting();
            $html .= $setting->output_html($data);
        }
        return \html_writer::tag('fieldset', $html);
    }

    /**
     * The full tree of the administration, with its settings.
     *
     * @return \admin_root
     */
    protected static function root(): \admin_root {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        return admin_get_root(false, true);
    }
}
