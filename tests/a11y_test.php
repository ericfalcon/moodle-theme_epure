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

#[\PHPUnit\Framework\Attributes\CoversClass(a11y::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(accessibility_statement::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(privacy\provider::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(form\a11y_statement_form::class)]
/**
 * Tests for the display preferences and the accessibility statement.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\a11y
 * @covers     \theme_epure\accessibility_statement
 * @covers     \theme_epure\privacy\provider
 * @covers     \theme_epure\form\a11y_statement_form
 */
final class a11y_test extends \advanced_testcase {
    /**
     * Without preferences, or without a user, the display is the default one.
     */
    public function test_default_display(): void {
        $this->resetAfterTest();
        $this->assertSame('', a11y::html_classes());
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame('', a11y::html_classes());
        $this->assertSame(['text' => 100, 'font' => '', 'scheme' => '',
            'spacing' => false, 'contrast' => false, 'underline' => false,
            'motion' => false], a11y::preferences());
    }

    /**
     * The preferences of the user become classes of the html element; unknown values are ignored.
     */
    public function test_html_classes(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('theme_epure_a11y_text', 130);
        set_user_preference('theme_epure_a11y_font', 'opendyslexic');
        set_user_preference('theme_epure_a11y_contrast', 1);
        set_user_preference('theme_epure_a11y_motion', 1);
        $this->assertSame(
            'epure-a11y-text-130 epure-a11y-font-opendyslexic epure-a11y-contrast epure-a11y-motion',
            a11y::html_classes()
        );

        set_user_preference('theme_epure_a11y_text', 400);
        set_user_preference('theme_epure_a11y_font', 'comicsans');
        $this->assertSame('epure-a11y-contrast epure-a11y-motion', a11y::html_classes());

        // Dark mode: the choice of the user, else the setting of the site.
        set_config('darkmode', 'auto', 'theme_epure');
        $this->assertStringContainsString('epure-dark-auto', a11y::html_classes());
        set_user_preference('theme_epure_a11y_scheme', 'dark');
        $this->assertStringEndsWith(' epure-dark', a11y::html_classes());
        set_user_preference('theme_epure_a11y_scheme', 'light');
        $this->assertStringNotContainsString('epure-dark', a11y::html_classes());
        set_config('darkmode', 'light', 'theme_epure');
        unset_user_preference('theme_epure_a11y_scheme');

        // Guests get the default display, whatever the guest account holds.
        $this->setGuestUser();
        $this->assertSame('', a11y::html_classes());
    }

    /**
     * The display of the site by default applies to the visitors and to the users who did not choose theirs.
     */
    public function test_site_defaults(): void {
        $this->resetAfterTest();
        set_config('preftext', 115, 'theme_epure');
        set_config('preffont', 'atkinson', 'theme_epure');
        set_config('prefmotion', 1, 'theme_epure');
        $expected = 'epure-a11y-text-115 epure-a11y-font-atkinson epure-a11y-motion';
        $this->assertSame($expected, a11y::html_classes());
        $this->setGuestUser();
        $this->assertSame($expected, a11y::html_classes());
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame($expected, a11y::html_classes());

        // The choices of the user come first, the switches turned off too.
        set_user_preference('theme_epure_a11y_text', 100);
        set_user_preference('theme_epure_a11y_motion', 0);
        $this->assertSame('epure-a11y-font-atkinson', a11y::html_classes());

        $this->assertTrue(a11y::panel_enabled());
        set_config('prefpanel', 0, 'theme_epure');
        $this->assertFalse(a11y::panel_enabled());
    }

    /**
     * The preferences can be changed by the user, and only by them; unknown values are refused.
     */
    public function test_definitions(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        foreach (array_keys(a11y::definitions()) as $name) {
            $this->assertTrue(\core_user::can_edit_preference($name, $user), $name);
            $this->assertFalse(\core_user::can_edit_preference($name, $other), $name);
        }
        $this->assertSame(115, \core_user::clean_preference('115', 'theme_epure_a11y_text'));
        $this->assertSame(100, \core_user::clean_preference('400', 'theme_epure_a11y_text'));
        $this->assertSame('atkinson', \core_user::clean_preference('atkinson', 'theme_epure_a11y_font'));
        $this->assertSame('', \core_user::clean_preference('comicsans', 'theme_epure_a11y_font'));
        $this->assertSame(1, \core_user::clean_preference('1', 'theme_epure_a11y_spacing'));
    }

    /**
     * The panel shows the current choices of the user.
     */
    public function test_panel_context(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('theme_epure_a11y_text', 115);
        set_user_preference('theme_epure_a11y_underline', 1);

        $context = a11y::panel_context();
        $this->assertSame([115], array_column(array_filter($context['sizes'], fn($size) => $size['checked']), 'value'));
        $this->assertSame([''], array_column(array_filter($context['fonts'], fn($font) => $font['checked']), 'value'));
        $this->assertSame(['underline'], array_column(
            array_filter($context['switches'], fn($switch) => $switch['checked']),
            'name'
        ));
        $this->assertSame('Atkinson Hyperlegible', $context['fonts'][1]['label']);
    }

    /**
     * The statement is only published once a compliance status is chosen.
     */
    public function test_statement(): void {
        $this->resetAfterTest();
        $this->assertFalse(accessibility_statement::enabled());
        $this->assertFalse(accessibility_statement::export()['published']);

        set_config('a11ystatus', 'partial', 'theme_epure');
        set_config('a11yentity', 'Clinique des Tilleuls', 'theme_epure');
        set_config('a11yrate', '75 %', 'theme_epure');
        set_config('a11ynoncompliant', "- Some PDF documents", 'theme_epure');
        $this->assertTrue(accessibility_statement::enabled());

        $statement = accessibility_statement::export();
        $this->assertTrue($statement['published']);
        $this->assertSame('Clinique des Tilleuls', $statement['entity']);
        $this->assertTrue($statement['hasaudit']);
        $this->assertSame(get_string('a11ystatus_partial', 'theme_epure'), $statement['status']);
        $this->assertStringContainsString('<li>Some PDF documents</li>', $statement['noncompliant']);
        $this->assertSame('', $statement['derogations']);
        $this->assertCount(accessibility_statement::MEASURES, $statement['measures']);
        // Without a contact, the support email is given, else the support form.
        set_config('supportemail', 'support@example.com');
        $this->assertStringContainsString('mailto:support@example.com', accessibility_statement::export()['contact']);
        set_config('supportemail', '');
        $this->assertStringContainsString('contactsitesupport.php', accessibility_statement::export()['contact']);

        // An email address entered as contact becomes a link.
        set_config('a11ycontact', 'Écrire à eric@example.fr', 'theme_epure');
        $this->assertStringContainsString('href="mailto:eric@example.fr"', accessibility_statement::export()['contact']);

        set_config('a11ystatus', 'other', 'theme_epure');
        $this->assertFalse(accessibility_statement::enabled());
    }

    /**
     * The statement filled step by step: its steps hold all its settings, and each step saves its own only.
     */
    public function test_statement_steps(): void {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        // The steps hold every setting of the statement, each once.
        $names = array_merge(...array_values(accessibility_statement::STEPS));
        $texts = array_map(fn($name) => 'a11y' . $name, accessibility_statement::TEXTS);
        $fields = ['a11ystatus', 'a11yentity', 'a11yrate', 'a11yauditor', 'a11yauditdate', 'a11ystatementdate'];
        $expected = array_merge($fields, $texts);
        sort($names);
        sort($expected);
        $this->assertSame($expected, $names);

        // The values of a step only, trimmed; an unknown status leaves the statement unpublished.
        $data = (object) ['step' => 3, 'a11ystatus' => 'perfect', 'a11yentity' => 'Other'];
        $this->assertSame(['a11ystatus' => ''], form\a11y_statement_form::values_from($data));
        $data = (object) ['step' => 1, 'a11yentity' => '  Clinique  ', 'a11ystatementdate' => '30 October 2026'];
        $values = form\a11y_statement_form::values_from($data);
        $this->assertSame(['a11yentity' => 'Clinique', 'a11ystatementdate' => '30 October 2026'], $values);
        $this->assertSame('2', accessibility_statement::edit_url(2)->get_param('step'));
    }

    /**
     * Each step of the guided form shows its settings, with their current values.
     */
    public function test_statement_form(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('a11yrate', '78 %', 'theme_epure');
        foreach (array_keys(accessibility_statement::STEPS) as $i => $key) {
            $form = new form\a11y_statement_form(null, ['step' => $i + 1]);
            $html = $form->render();
            foreach (accessibility_statement::STEPS[$key] as $name) {
                $this->assertStringContainsString('name="' . $name . '"', $html, $name);
            }
        }
        $html = (new form\a11y_statement_form(null, ['step' => 3]))->render();
        $this->assertStringContainsString(get_string('a11ywizard_rate', 'theme_epure', '78 %'), $html);
    }

    /**
     * The display preferences are declared and exported for the privacy API.
     */
    public function test_privacy(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        set_user_preference('theme_epure_a11y_text', 130, $user);

        $collection = privacy\provider::get_metadata(new \core_privacy\local\metadata\collection('theme_epure'));
        $this->assertCount(count(a11y::definitions()), $collection->get_collection());

        privacy\provider::export_user_preferences($user->id);
        $writer = \core_privacy\local\request\writer::with_context(\context_system::instance());
        $preferences = $writer->get_user_preferences('theme_epure');
        $this->assertSame('130', $preferences->theme_epure_a11y_text->value);
        $this->assertFalse(isset($preferences->theme_epure_a11y_font));
    }
}
