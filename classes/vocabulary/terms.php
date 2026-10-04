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

namespace theme_epure\vocabulary;

/**
 * The words the administrator can choose for the objects of the platform.
 *
 * A term is an array with the keys singular, plural and gender ('m' or 'f', used in French).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class terms {
    /** @var string[] Objects whose name can be chosen for the whole platform. */
    public const CONCEPTS = ['course', 'student', 'teacher'];

    /** @var string[] IOMAD objects, whose name is chosen for all companies and for each one, see {@see company}. */
    public const IOMAD_CONCEPTS = ['company', 'department'];

    /** @var string[] Languages the vocabulary can be rewritten in. */
    public const LANGUAGES = ['fr', 'en'];

    /** @var string Choice of a word typed by the administrator. */
    public const CUSTOM = 'custom';

    /** @var array<string, array<string, string>> Key of the word used by the language pack, by language and object. */
    public const DEFAULTS = [
        'fr' => [
            'course' => 'cours', 'student' => 'etudiant', 'teacher' => 'enseignant',
            'company' => 'entreprise', 'department' => 'departement',
        ],
        'en' => [
            'course' => 'course', 'student' => 'student', 'teacher' => 'teacher',
            'company' => 'company', 'department' => 'department',
        ],
    ];

    /**
     * Objects whose name can be chosen for the whole platform with the theme.
     *
     * The IOMAD objects are chosen apart, for all companies and for each one, see {@see company}.
     *
     * @return string[]
     */
    public static function concepts(): array {
        return self::CONCEPTS;
    }

    /**
     * Words the language pack uses for an object: the default word, and its synonyms.
     *
     * The French translation of IOMAD calls a company « entreprise » or « société ».
     *
     * @param string $lang Language.
     * @param string $concept Object.
     * @return array[] Terms.
     */
    public static function pack_words(string $lang, string $concept): array {
        $presets = self::presets()[$lang][$concept];
        $words = [$presets[self::DEFAULTS[$lang][$concept]]];
        if ($lang === 'fr' && $concept === 'company') {
            $words[] = $presets['societe'];
        }
        return $words;
    }

    /**
     * Words proposed for each object, by language.
     *
     * @return array<string, array<string, array<string, array<string, string>>>> language => object => key => term.
     */
    public static function presets(): array {
        $t = fn(string $singular, string $plural, string $gender = 'm') => compact('singular', 'plural', 'gender');
        return [
            'fr' => [
                'course' => [
                    'cours' => $t('cours', 'cours'),
                    'formation' => $t('formation', 'formations', 'f'),
                    'parcours' => $t('parcours', 'parcours'),
                    'module' => $t('module', 'modules'),
                ],
                'student' => [
                    'etudiant' => $t('étudiant', 'étudiants'),
                    'apprenant' => $t('apprenant', 'apprenants'),
                    'stagiaire' => $t('stagiaire', 'stagiaires'),
                    'participant' => $t('participant', 'participants'),
                    'collaborateur' => $t('collaborateur', 'collaborateurs'),
                ],
                'teacher' => [
                    'enseignant' => $t('enseignant', 'enseignants'),
                    'formateur' => $t('formateur', 'formateurs'),
                    'tuteur' => $t('tuteur', 'tuteurs'),
                    'intervenant' => $t('intervenant', 'intervenants'),
                    'professeur' => $t('professeur', 'professeurs'),
                    'coach' => $t('coach', 'coachs'),
                ],
                'company' => [
                    'entreprise' => $t('entreprise', 'entreprises', 'f'),
                    'societe' => $t('société', 'sociétés', 'f'),
                    'client' => $t('client', 'clients'),
                    'etablissement' => $t('établissement', 'établissements'),
                    'organisation' => $t('organisation', 'organisations', 'f'),
                    'filiale' => $t('filiale', 'filiales', 'f'),
                    'agence' => $t('agence', 'agences', 'f'),
                ],
                'department' => [
                    'departement' => $t('département', 'départements'),
                    'service' => $t('service', 'services'),
                    'equipe' => $t('équipe', 'équipes', 'f'),
                    'site' => $t('site', 'sites'),
                    'pole' => $t('pôle', 'pôles'),
                    'agence' => $t('agence', 'agences', 'f'),
                    'unite' => $t('unité', 'unités', 'f'),
                ],
            ],
            'en' => [
                'course' => [
                    'course' => $t('course', 'courses'),
                    'program' => $t('program', 'programs'),
                    'programme' => $t('programme', 'programmes'),
                    'module' => $t('module', 'modules'),
                    'class' => $t('class', 'classes'),
                    'learningpath' => $t('learning path', 'learning paths'),
                ],
                'student' => [
                    'student' => $t('student', 'students'),
                    'learner' => $t('learner', 'learners'),
                    'trainee' => $t('trainee', 'trainees'),
                    'participant' => $t('participant', 'participants'),
                    'employee' => $t('employee', 'employees'),
                ],
                'teacher' => [
                    'teacher' => $t('teacher', 'teachers'),
                    'trainer' => $t('trainer', 'trainers'),
                    'tutor' => $t('tutor', 'tutors'),
                    'instructor' => $t('instructor', 'instructors'),
                    'facilitator' => $t('facilitator', 'facilitators'),
                    'coach' => $t('coach', 'coaches'),
                ],
                'company' => [
                    'company' => $t('company', 'companies'),
                    'client' => $t('client', 'clients'),
                    'organisation' => $t('organisation', 'organisations'),
                    'organization' => $t('organization', 'organizations'),
                    'institution' => $t('institution', 'institutions'),
                    'subsidiary' => $t('subsidiary', 'subsidiaries'),
                    'agency' => $t('agency', 'agencies'),
                ],
                'department' => [
                    'department' => $t('department', 'departments'),
                    'team' => $t('team', 'teams'),
                    'site' => $t('site', 'sites'),
                    'unit' => $t('unit', 'units'),
                    'division' => $t('division', 'divisions'),
                    'branch' => $t('branch', 'branches'),
                ],
            ],
        ];
    }

    /**
     * Name of the setting that stores a choice.
     *
     * @param string $lang Language.
     * @param string $concept Object.
     * @param string $suffix Empty for the choice, or singular, plural, gender for a custom word.
     * @return string
     */
    public static function setting(string $lang, string $concept, string $suffix = ''): string {
        return "vocab_{$lang}_{$concept}" . ($suffix === '' ? '' : "_{$suffix}");
    }

    /**
     * The word chosen for an object, when it differs from the one of the language pack.
     *
     * @param string $lang Language.
     * @param string $concept Object.
     * @param array|null $values Settings (name => value), or null for the settings of the theme.
     * @param bool $packisnone Whether choosing the word of the language pack means no change.
     * @return array<string, string>|null The term, or null to keep the language pack wording.
     */
    public static function chosen(string $lang, string $concept, ?array $values = null, bool $packisnone = true): ?array {
        $get = fn(string $name) => (string) ($values === null ? get_config('theme_epure', $name) : ($values[$name] ?? ''));
        $choice = $get(self::setting($lang, $concept));
        // For the platform, the word of the language pack means no change. For a company, it can be a choice
        // like any other (it can differ from the word of the platform), and no choice means the platform word.
        if ($choice === '' || ($packisnone && $choice === self::DEFAULTS[$lang][$concept])) {
            return null;
        }
        if ($choice === self::CUSTOM) {
            $singular = trim($get(self::setting($lang, $concept, 'singular')));
            $plural = trim($get(self::setting($lang, $concept, 'plural')));
            $gender = $get(self::setting($lang, $concept, 'gender')) === 'f' ? 'f' : 'm';
            if ($singular === '') {
                return null;
            }
            return ['singular' => $singular, 'plural' => $plural === '' ? $singular : $plural, 'gender' => $gender];
        }
        return self::presets()[$lang][$concept][$choice] ?? null;
    }

    /**
     * The words chosen in a language, for the objects whose wording changes.
     *
     * @param string $lang Language.
     * @return array<string, array<string, string>> object => term.
     */
    public static function chosen_all(string $lang): array {
        $targets = [];
        foreach (self::concepts() as $concept) {
            if ($term = self::chosen($lang, $concept)) {
                $targets[$concept] = $term;
            }
        }
        return $targets;
    }
}
