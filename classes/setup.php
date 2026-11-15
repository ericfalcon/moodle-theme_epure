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
 * Getting started with Épure, for the administrator: a few steps that fill the settings that matter most on a new
 * site (the name and the logo, the colours, the login page from a model by sector, the footer), then lead to the
 * accessibility statement. It adds no setting: each step writes the settings of Moodle and of the theme, which
 * stay in their tabs. Offered on the home of the administration until it is finished or dismissed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /** @var string[] The steps, in their order. */
    public const STEPS = ['identity', 'colours', 'login', 'footer', 'accessibility'];

    /** @var string[] The models of the login page, by sector: its tagline and its text, and whether the catalogue shows. */
    public const MODELS = ['university', 'company', 'school', 'training', 'administration'];

    /** @var bool[] Whether the catalogue of the courses shows, by model: open offers yes, internal training no. */
    public const CATALOGUE = ['university' => true, 'company' => false, 'school' => false, 'training' => true,
        'administration' => false];

    /** @var string[] Styles of the header, as in the Appearance tab. */
    public const HEADER_STYLES = ['light', 'brand', 'gradient'];

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
     * The current values of a step, for its form.
     *
     * @param string $step Step.
     * @return array
     */
    public static function values(string $step): array {
        $config = get_config('theme_epure');
        switch ($step) {
            case 'identity':
                $site = get_site();
                return ['fullname' => $site->fullname, 'shortname' => $site->shortname];
            case 'colours':
                return [
                    'brandcolor' => (string) ($config->brandcolor ?? palette::DEFAULT_BRAND),
                    'accentcolor' => (string) ($config->accentcolor ?? ''),
                    'headerstyle' => (string) ($config->headerstyle ?? 'light'),
                ];
            case 'login':
                return [
                    'model' => '',
                    'logintagline' => (string) ($config->logintagline ?? ''),
                    'logintext' => (string) ($config->logintext ?? ''),
                    'catalogue' => (int) ($config->catalogue ?? 1),
                ];
            case 'footer':
                return [
                    'footertext' => (string) ($config->footertext ?? ''),
                    'footerlegalurl' => (string) ($config->footerlegalurl ?? ''),
                    'footerprivacyurl' => (string) ($config->footerprivacyurl ?? ''),
                    'footercontacturl' => (string) ($config->footercontacturl ?? ''),
                ];
        }
        return [];
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
     * Writes the values of a step into the settings of Moodle and of the theme.
     *
     * @param string $step Step.
     * @param \stdClass $data Submitted data, cleaned by the form.
     */
    public static function save(string $step, \stdClass $data): void {
        global $DB;
        switch ($step) {
            case 'identity':
                $site = get_site();
                $site->fullname = trim($data->fullname);
                $site->shortname = trim($data->shortname);
                $DB->update_record('course', (object) ['id' => $site->id, 'fullname' => $site->fullname,
                    'shortname' => $site->shortname]);
                rebuild_course_cache($site->id, true);
                if (!empty($data->logo)) {
                    self::save_logo((int) $data->logo);
                }
                break;
            case 'colours':
                set_config('brandcolor', palette::normalise($data->brandcolor) ?? palette::DEFAULT_BRAND, 'theme_epure');
                set_config(
                    'accentcolor',
                    trim($data->accentcolor) === '' ? '' : (string) palette::normalise($data->accentcolor),
                    'theme_epure'
                );
                set_config('headerstyle', $data->headerstyle, 'theme_epure');
                mobile_app::appearance_updated();
                break;
            case 'login':
                // A model fills what is left empty; what the administrator wrote stays.
                $texts = in_array($data->model, self::MODELS, true) ? self::model_texts($data->model) : [];
                foreach (['logintagline', 'logintext'] as $name) {
                    $value = trim((string) $data->$name);
                    set_config($name, $value === '' ? ($texts[$name] ?? '') : $value, 'theme_epure');
                }
                $catalogue = in_array($data->model, self::MODELS, true) ? self::CATALOGUE[$data->model]
                    : !empty($data->catalogue);
                set_config('catalogue', (int) $catalogue, 'theme_epure');
                break;
            case 'footer':
                foreach (['footertext', 'footerlegalurl', 'footerprivacyurl', 'footercontacturl'] as $name) {
                    set_config($name, trim((string) $data->$name), 'theme_epure');
                }
                set_config('footer', 1, 'theme_epure');
                break;
        }
        theme_reset_all_caches();
    }

    /**
     * The logo that Épure shows, which step 1 changes: the logo of the theme (its tab Appearance › Logos) when it has
     * one, as it comes before the logo of the site; else the logo of the site (Appearance › Logos of Moodle).
     *
     * @return array{component: string, setting: string, theme: bool}
     */
    public static function logo_target(): array {
        if ((string) get_config('theme_epure', 'logo') !== '') {
            return ['component' => 'theme_epure', 'setting' => 'logo', 'theme' => true];
        }
        return ['component' => 'core_admin', 'setting' => 'logo', 'theme' => false];
    }

    /**
     * The logo that Épure shows ({@see logo_target()}), from the draft area of the form.
     *
     * @param int $draftitemid Draft area.
     */
    protected static function save_logo(int $draftitemid): void {
        ['component' => $component, 'setting' => $setting] = self::logo_target();
        $context = \context_system::instance();
        file_save_draft_area_files(
            $draftitemid,
            $context->id,
            $component,
            $setting,
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $files = get_file_storage()->get_area_files($context->id, $component, $setting, 0, 'sortorder, filepath, filename', false);
        $file = reset($files);
        set_config($setting, $file ? $file->get_filepath() . $file->get_filename() : '', $component);
    }

    /**
     * A draft area with the logo that Épure shows, for the form.
     *
     * @return int Draft item id.
     */
    public static function logo_draft(): int {
        ['component' => $component, 'setting' => $setting] = self::logo_target();
        $draftitemid = file_get_submitted_draft_itemid('logo');
        file_prepare_draft_area(
            $draftitemid,
            \context_system::instance()->id,
            $component,
            $setting,
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        return $draftitemid;
    }
}
