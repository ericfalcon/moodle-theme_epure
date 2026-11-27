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

namespace theme_epure\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use theme_epure\activity_icons;
use theme_epure\company_style;
use theme_epure\entity;
use theme_epure\fonts;
use theme_epure\palette;
use theme_epure\vocabulary\manager;
use theme_epure\vocabulary\terms;

/**
 * Appearance of an entity: a course category with its own colours, logos, header, pages and footer, and the cohorts that
 * lead to it out of its courses. Each field is « As the site » until it is set.
 *
 * Custom data: category (id), cohorts (options, by id).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_form extends \moodleform {
    /** @var array Options of the file managers of the logos. */
    public const LOGO_OPTIONS = ['maxfiles' => 1, 'subdirs' => 0, 'accepted_types' => ['.svg', '.png', '.webp', '.jpg', '.jpeg']];

    /** @var string[] Settings on or off of the pages that an entity can set instead of those of the site. */
    public const SWITCHES = ['coursebanner', 'learnerdashboard', 'mobilenav', 'catalogue', 'quicksearch', 'activitynav',
        'sectionprogress', 'mycoursesbyrole', 'courseaccessibility'];

    /** @var string[] Settings with several choices that an entity can set instead of those of the site. */
    public const CHOICES = ['radius', 'breadcrumb', 'mobileblocks', 'mobiledashboard', 'simpleview'];

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $site = fn(string $label) => ['' => get_string('companysite', 'theme_epure', $label)];
        $mform->addElement('hidden', 'category', (int) $this->_customdata['category']);
        $mform->setType('category', PARAM_INT);

        // Colours, logos, header and font.
        $mform->addElement('header', 'looks', get_string('entitylooks', 'theme_epure'));
        $mform->setExpanded('looks');
        $sitebrand = palette::normalise(get_config('theme_epure', 'brandcolor')) ?? palette::DEFAULT_BRAND;
        foreach (
            ['brandcolor' => $sitebrand, 'accentcolor' => palette::normalise(get_config('theme_epure', 'accentcolor'))
                ?? $sitebrand] as $name => $default
        ) {
            $mform->addElement('text', $name, get_string($name, 'theme_epure'), ['size' => 9, 'maxlength' => 7,
                'placeholder' => $default, 'pattern' => '#?[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?', 'autocomplete' => 'off']);
            $mform->setType($name, PARAM_RAW_TRIMMED);
            $mform->addHelpButton($name, 'entitycolour', 'theme_epure');
        }
        // The palette of these colours, in a live preview that follows them before they are saved.
        $current = $this->_customdata['settings'] ?? [];
        $mform->addElement('static', 'palettelive', get_string('palette', 'theme_epure'), \theme_epure\admin\palette_preview::live(
            'epure-palette-live',
            ($current['brandcolor'] ?? '') ?: null,
            ($current['accentcolor'] ?? '') ?: null,
            ($current['headerstyle'] ?? '') ?: ((string) get_config('theme_epure', 'headerstyle') ?: 'light'),
            ['brand' => '#id_brandcolor', 'accent' => '#id_accentcolor', 'header' => '#id_headerstyle']
        ));
        $mform->addElement('filemanager', 'entitylogo', get_string('logo', 'theme_epure'), null, self::LOGO_OPTIONS);
        $mform->addElement(
            'filemanager',
            'entitylogoonbrand',
            get_string('logoonbrand', 'theme_epure'),
            null,
            self::LOGO_OPTIONS
        );
        $mform->addHelpButton('entitylogoonbrand', 'entitylogoonbrand', 'theme_epure');

        $siteheader = (string) get_config('theme_epure', 'headerstyle');
        $siteheader = in_array($siteheader, company_style::HEADER_STYLES, true) ? $siteheader : 'light';
        $headers = [];
        foreach (company_style::HEADER_STYLES as $style) {
            $headers[$style] = get_string('headerstyle' . $style, 'theme_epure');
        }
        $mform->addElement(
            'select',
            'headerstyle',
            get_string('headerstyle', 'theme_epure'),
            $site($headers[$siteheader]) + $headers
        );

        $sitefont = get_config('theme_epure', 'font') === fonts::CUSTOM
            ? (trim((string) get_config('theme_epure', 'customfontname')) ?: get_string('fontcustom', 'theme_epure'))
            : fonts::get(get_config('theme_epure', 'font') ?: null)['family'];
        $fontoptions = array_map(fn($font) => $font['family'], fonts::all());
        if (get_config('theme_epure', 'customfontregular')) {
            $fontoptions[fonts::CUSTOM] = get_string(
                'companyfontsite',
                'theme_epure',
                trim((string) get_config('theme_epure', 'customfontname')) ?: get_string('fontcustom', 'theme_epure')
            );
        }
        $mform->addElement('select', 'font', get_string('font', 'theme_epure'), $site($sitefont) + $fontoptions);

        $sitedark = (string) get_config('theme_epure', 'darkmode');
        $sitedark = in_array($sitedark, company_style::DARK_MODES, true) ? $sitedark : 'light';
        $darkmodes = [];
        foreach (company_style::DARK_MODES as $mode) {
            $darkmodes[$mode] = get_string('darkmode' . $mode, 'theme_epure');
        }
        $mform->addElement('select', 'darkmode', get_string('darkmode', 'theme_epure'), $site($darkmodes[$sitedark]) + $darkmodes);

        $icons = [];
        foreach (activity_icons::CHOICES as $choice) {
            $icons[$choice] = get_string('activityicons' . $choice, 'theme_epure');
        }
        $mform->addElement(
            'select',
            'activityicons',
            get_string('activityicons', 'theme_epure'),
            $site($icons[activity_icons::site()] ?? activity_icons::site()) + $icons
        );

        // Who sees it out of its courses: the cohorts.
        $mform->addElement('header', 'who', get_string('entitywho', 'theme_epure'));
        $mform->setExpanded('who');
        $mform->addElement('static', 'whointro', '', get_string('entitywho_desc', 'theme_epure'));
        $mform->addElement(
            'autocomplete',
            'cohorts',
            get_string('entitycohorts', 'theme_epure'),
            $this->_customdata['cohorts'] ?? [],
            ['multiple' => true, 'noselectionstring' => get_string(
                'entitynocohort',
                'theme_epure'
            )]
        );
        $mform->addHelpButton('cohorts', 'entitycohorts', 'theme_epure');
        $mform->addElement(
            'select',
            'priority',
            get_string('entitypriority', 'theme_epure'),
            array_combine(range(0, entity::MAX_PRIORITY), range(0, entity::MAX_PRIORITY))
        );
        $mform->addHelpButton('priority', 'entitypriority', 'theme_epure');

        // Pages and navigation.
        $mform->addElement('header', 'pages', get_string('entitypages', 'theme_epure'));
        $onoff = ['show' => get_string('companyswitchshow', 'theme_epure'),
            'hide' => get_string('companyswitchhide', 'theme_epure')];
        foreach (self::CHOICES as $name) {
            $choices = [];
            foreach (company_style::CHOICES[$name] as $value) {
                $choices[$value] = get_string($name . $value, 'theme_epure');
            }
            $current = (string) get_config('theme_epure', $name) ?: company_style::CHOICE_DEFAULTS[$name];
            $mform->addElement('select', $name, get_string($name, 'theme_epure'), $site($choices[$current] ?? $current) + $choices);
        }
        foreach (self::SWITCHES as $name) {
            $current = get_config('theme_epure', $name) !== '0' ? 'show' : 'hide';
            $mform->addElement('select', $name, get_string($name, 'theme_epure'), $site($onoff[$current]) + $onoff);
        }

        // Footer.
        $mform->addElement('header', 'footer', get_string('entityfooter', 'theme_epure'));
        $mform->addElement('static', 'footerintro', '', get_string('entityfooter_desc', 'theme_epure'));
        $mform->addElement('textarea', 'footertext', get_string('footertext', 'theme_epure'), ['rows' => 4, 'cols' => 60]);
        $mform->setType('footertext', PARAM_RAW);
        // In the editor of Moodle, as the text of the footer of the site and of an IOMAD company (HTML).
        global $CFG;
        require_once($CFG->libdir . '/editorlib.php');
        editors_get_preferred_editor(FORMAT_HTML)->use_editor('id_footertext', [
            'context' => \context_coursecat::instance($this->_customdata['category']),
            'autosave' => false,
            'maxfiles' => 0,
        ]);
        foreach (['footerlegalurl', 'footerprivacyurl', 'footercontacturl'] as $name) {
            $mform->addElement('text', $name, get_string($name, 'theme_epure'), ['size' => 60]);
            $mform->setType($name, PARAM_URL);
        }
        $mform->addElement('textarea', 'footerlinks', get_string('footerlinks', 'theme_epure'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('footerlinks', PARAM_TEXT);
        $mform->addHelpButton('footerlinks', 'entityfooterlinks', 'theme_epure');

        // The login page of the entity: its own address, its layout and its texts.
        $mform->addElement('header', 'login', get_string('entitylogin', 'theme_epure'));
        $mform->addElement('static', 'loginurl', get_string('entityloginurl', 'theme_epure'), \html_writer::tag(
            'code',
            s(entity::login_url((int) $this->_customdata['category'])->out(false))
        ));
        $mform->addHelpButton('loginurl', 'entityloginurl', 'theme_epure');
        $layouts = [];
        foreach (company_style::CHOICES['loginlayout'] as $value) {
            $layouts[$value] = get_string('loginlayout' . $value, 'theme_epure');
        }
        $sitelayout = (string) get_config('theme_epure', 'loginlayout') ?: company_style::CHOICE_DEFAULTS['loginlayout'];
        $mform->addElement(
            'select',
            'loginlayout',
            get_string('loginlayout', 'theme_epure'),
            $site($layouts[$sitelayout] ?? $sitelayout) + $layouts
        );
        $mform->addElement('text', 'logintagline', get_string('logintagline', 'theme_epure'), ['size' => 60]);
        $mform->setType('logintagline', PARAM_TEXT);
        $mform->addElement('textarea', 'logintext', get_string('logintext', 'theme_epure'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('logintext', PARAM_TEXT);

        // CSS of the entity: for the administrators of the site only, as a CSS can hide or change anything in the page.
        if (!empty($this->_customdata['admin'])) {
            $mform->addElement('header', 'css', get_string('entitycss', 'theme_epure'));
            $mform->addElement(
                'textarea',
                'customcss',
                get_string('entitycss', 'theme_epure'),
                ['rows' => 6, 'cols' => 60, 'spellcheck' => 'false', 'class' => 'text-monospace font-monospace']
            );
            $mform->setType('customcss', PARAM_RAW);
            $mform->addHelpButton('customcss', 'entitycss', 'theme_epure');
        }

        // Words: course, student, teacher, for the members of the cohorts of the entity.
        $mform->addElement('header', 'vocabulary', get_string('entityvocabulary', 'theme_epure'));
        $mform->addElement('static', 'vocabularyintro', '', get_string('entityvocabulary_desc', 'theme_epure'));
        $translations = get_string_manager()->get_list_of_translations(true);
        $presets = terms::presets();
        foreach (manager::languages() as $lang) {
            foreach (terms::CONCEPTS as $concept) {
                $name = terms::setting($lang, $concept);
                $site = terms::chosen($lang, $concept) ?? $presets[$lang][$concept][terms::DEFAULTS[$lang][$concept]];
                $options = ['' => get_string('companysite', 'theme_epure', $site['plural'])];
                foreach ($presets[$lang][$concept] as $key => $term) {
                    $options[$key] = $term['singular'] === $term['plural'] ? $term['singular']
                        : $term['singular'] . ' / ' . $term['plural'];
                }
                $options[terms::CUSTOM] = get_string('vocabcustom', 'theme_epure');
                $label = get_string('vocab_' . $concept, 'theme_epure') . ' (' . ($translations[$lang] ?? $lang) . ')';
                $mform->addElement('select', $name, $label, $options);
                foreach (['singular', 'plural'] as $form) {
                    $field = terms::setting($lang, $concept, $form);
                    $mform->addElement('text', $field, get_string('vocab' . $form, 'theme_epure'), ['size' => 24]);
                    $mform->setType($field, PARAM_TEXT);
                    $mform->hideIf($field, $name, 'neq', terms::CUSTOM);
                }
                if ($lang === 'fr') {
                    $field = terms::setting($lang, $concept, 'gender');
                    $mform->addElement('select', $field, get_string('vocabgender', 'theme_epure'), [
                        'm' => get_string('vocabmasculine', 'theme_epure'),
                        'f' => get_string('vocabfeminine', 'theme_epure'),
                    ]);
                    $mform->hideIf($field, $name, 'neq', terms::CUSTOM);
                }
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Colours as hex codes, or empty for the site.
     *
     * @param array $data Data.
     * @param array $files Files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        foreach (['brandcolor', 'accentcolor'] as $name) {
            if (trim((string) ($data[$name] ?? '')) !== '' && palette::normalise($data[$name]) === null) {
                $errors[$name] = get_string('entitybadcolour', 'theme_epure');
            }
        }
        // A word typed needs at least its singular.
        foreach (manager::languages() as $lang) {
            foreach (terms::CONCEPTS as $concept) {
                $singular = terms::setting($lang, $concept, 'singular');
                if (($data[terms::setting($lang, $concept)] ?? '') === terms::CUSTOM && trim($data[$singular] ?? '') === '') {
                    $errors[$singular] = get_string('required');
                }
            }
        }
        return $errors;
    }

    /**
     * The values of the form for the appearance of the entity ({@see entity::save()}).
     *
     * @param \stdClass $data Data of the form.
     * @param array $before Words saved before: those of the languages not installed, not in the form, are kept.
     * @return array
     */
    public static function values(\stdClass $data, array $before = []): array {
        $values = [];
        foreach (
            array_merge(
                ['brandcolor', 'accentcolor', 'headerstyle', 'font', 'darkmode', 'activityicons', 'cohorts', 'priority',
                    'loginlayout', 'logintagline', 'logintext', 'customcss'],
                self::CHOICES,
                self::SWITCHES,
                company_style::FOOTER_FIELDS
            ) as $name
        ) {
            $values[$name] = $data->$name ?? '';
        }
        $offered = manager::languages();
        $values['vocabulary'] = array_filter(
            $before,
            fn($name) => !in_array(explode('_', $name)[1] ?? '', $offered, true),
            ARRAY_FILTER_USE_KEY
        );
        foreach ($offered as $lang) {
            foreach (terms::CONCEPTS as $concept) {
                foreach (['', 'singular', 'plural', 'gender'] as $suffix) {
                    $field = $suffix === '' ? terms::setting($lang, $concept) : terms::setting($lang, $concept, $suffix);
                    if (isset($data->$field)) {
                        $values['vocabulary'][$field] = trim((string) $data->$field);
                    }
                }
            }
        }
        return $values;
    }

    /**
     * The data of the form from the appearance of the entity, with its logos in draft areas.
     *
     * @param int $categoryid Category.
     * @param array $settings Settings ({@see entity::settings()}).
     * @return array
     */
    public static function data(int $categoryid, array $settings): array {
        $data = $settings + $settings['vocabulary'] + ['category' => $categoryid];
        unset($data['vocabulary']);
        foreach (entity::FILE_AREAS as $area) {
            $draft = file_get_submitted_draft_itemid($area);
            file_prepare_draft_area(
                $draft,
                \context_system::instance()->id,
                'theme_epure',
                $area,
                $categoryid,
                self::LOGO_OPTIONS
            );
            $data[$area] = $draft;
        }
        return $data;
    }
}
