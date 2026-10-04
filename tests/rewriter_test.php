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

use theme_epure\vocabulary\rewriter;
use theme_epure\vocabulary\terms;

#[\PHPUnit\Framework\Attributes\CoversClass(vocabulary\rewriter::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vocabulary\rewriter_fr::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vocabulary\rewriter_en::class)]
/**
 * Tests for the rewriting of language strings with the chosen vocabulary.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\vocabulary\rewriter
 * @covers     \theme_epure\vocabulary\rewriter_fr
 * @covers     \theme_epure\vocabulary\rewriter_en
 */
final class rewriter_test extends \basic_testcase {
    /**
     * French strings: formation (feminine), stagiaire (starts with a consonant), formateur.
     *
     * @return array<string, array{0: string, 1: string, 2: string|null}> String, English string, expected result.
     */
    public static function french_provider(): array {
        return [
            'possessive plural' => ['Mes cours', 'My courses', 'Mes formations'],
            'plural from English' => ['Cours', 'Courses', 'Formations'],
            'singular from English' => ['Cours', 'Course', 'Formation'],
            'tous les' => ['Tous les cours', 'All courses', 'Toutes les formations'],
            'du' => ['Nom complet du cours', 'Course full name', 'Nom complet de la formation'],
            'au' => ['Bienvenue au cours {$a}', 'Welcome to {$a}', 'Bienvenue à la formation {$a}'],
            'ce and participle' => ['Ce cours est masqué', '', 'Cette formation est masquée'],
            'participle after a été' => ['Le cours a été créé', '', 'La formation a été créée'],
            'plural from the participle' => ['Cours non commencés', '', 'Formations non commencées'],
            'un nouveau' => ['Un nouveau cours', 'A new course', 'Une nouvelle formation'],
            'aucun' => ['Aucun cours', 'No courses', 'Aucune formation'],
            'adjective after' => ['Cours suivant', 'Next course', 'Formation suivante'],
            'en cours is kept' => ['Cours en cours', 'Courses in progress', 'Formations en cours'],
            'au cours de is kept' => ['Au cours de la semaine', 'During the week', null],
            'elision removed' => ["Retour à l'étudiant", '', 'Retour au stagiaire'],
            'd apostrophe removed' => ["Liste d'étudiants", '', 'Liste de stagiaires'],
            'capital accent' => ['Étudiant', 'Student', 'Stagiaire'],
            'role name' => ['Enseignant non éditeur', 'Non-editing teacher', 'Formateur non éditeur'],
            'cet' => ['Cet enseignant', '', 'Ce formateur'],
            'nouvel' => ['Nouvel étudiant', '', 'Nouveau stagiaire'],
            'upper case' => ['COURS', 'COURSES', 'FORMATIONS'],
            'placeholders kept' => ['{$a->course} : cours', 'course', '{$a->course} : formation'],
            'html kept' => ['<a href="course/view.php">cours</a>', 'course', '<a href="course/view.php">formation</a>'],
            'nothing to change' => ['Participants', 'Participants', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('french_provider')]
    /**
     * French rewriting: determiners, elision and agreement follow the new word.
     *
     * @dataProvider french_provider
     * @param string $text French string.
     * @param string $english English string.
     * @param string|null $expected Expected result, null when nothing changes.
     */
    public function test_french(string $text, string $english, ?string $expected): void {
        $presets = terms::presets()['fr'];
        $rewriter = rewriter::for_language('fr', [
            'course' => $presets['course']['formation'],
            'student' => $presets['student']['stagiaire'],
            'teacher' => $presets['teacher']['formateur'],
        ]);
        $this->assertSame($expected, $rewriter->rewrite($text, $english));
    }

    /**
     * A word starting with a vowel is elided after le, de and ce.
     */
    public function test_french_elision(): void {
        $rewriter = rewriter::for_language('fr', [
            'course' => ['singular' => 'unité', 'plural' => 'unités', 'gender' => 'f'],
            'student' => terms::presets()['fr']['student']['apprenant'],
        ]);
        $this->assertSame("Nom complet de l'unité", $rewriter->rewrite('Nom complet du cours', 'Course full name'));
        $this->assertSame('Cette unité', $rewriter->rewrite('Ce cours', 'This course'));
        $this->assertSame("Le profil de l'apprenant", $rewriter->rewrite("Le profil de l'étudiant", ''));
    }

    /**
     * IOMAD strings: feminine words of the language pack (entreprise, société) becoming masculine, and the reverse.
     */
    public function test_french_iomad(): void {
        $presets = terms::presets()['fr'];
        $rewriter = rewriter::for_language('fr', [
            'company' => $presets['company']['client'],
            'department' => $presets['department']['equipe'],
        ]);
        $this->assertSame('Modifier le client', $rewriter->rewrite("Modifier l'entreprise", 'Edit company'));
        $this->assertSame('Gérer les clients', $rewriter->rewrite('Gérer les sociétés', 'Manage companies'));
        $this->assertSame('Ce client', $rewriter->rewrite('Cette société', ''));
        $this->assertSame('Le client sélectionné est suspendu', $rewriter->rewrite("L'entreprise sélectionnée est suspendue", ''));
        $this->assertSame('Afficher les clients suspendus ?', $rewriter->rewrite('Afficher les entreprises suspendues ?', ''));
        $this->assertSame('Nouvelle équipe', $rewriter->rewrite('Nouveau département', ''));
        $this->assertSame("L'équipe parente", $rewriter->rewrite('Le département parent', ''));
        // Identifiers are not words.
        $this->assertNull($rewriter->rewrite('company_users entreprise_id', ''));
    }

    /**
     * The words to replace can be given: those of the platform vocabulary, for the vocabulary of a company.
     */
    public function test_sources(): void {
        $presets = terms::presets()['fr'];
        $rewriter = rewriter::for_language(
            'fr',
            ['company' => $presets['company']['agence']],
            ['company' => [$presets['company']['client']]]
        );
        $this->assertSame("Modifier l'agence", $rewriter->rewrite('Modifier le client', 'Edit company'));
        $this->assertNull($rewriter->rewrite("Modifier l'entreprise", 'Edit company'));
    }

    /**
     * English strings: plural, capitals, and the article a or an.
     */
    public function test_english(): void {
        $presets = terms::presets()['en'];
        $rewriter = rewriter::for_language('en', [
            'course' => $presets['course']['program'],
            'student' => $presets['student']['learner'],
            'teacher' => $presets['teacher']['instructor'],
        ]);
        $this->assertSame('My programs', $rewriter->rewrite('My courses'));
        $this->assertSame('Add a program', $rewriter->rewrite('Add a course'));
        $this->assertSame('An instructor', $rewriter->rewrite('A teacher'));
        $this->assertSame("Learner's grade", $rewriter->rewrite("Student's grade"));
        $this->assertSame('Program {$a->course}', $rewriter->rewrite('Course {$a->course}'));
        $this->assertNull($rewriter->rewrite('Of course'));
        $this->assertNull($rewriter->rewrite('Participants'));
    }

    /**
     * Languages other than French and English are not rewritten.
     */
    public function test_unsupported_language(): void {
        $this->assertNull(rewriter::for_language('de', []));
    }
}
