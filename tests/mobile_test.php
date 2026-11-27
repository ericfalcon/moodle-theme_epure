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
 * Tests of the display preferences of « Aa » in the Moodle app.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\output\mobile
 * @covers     \theme_epure\external\display_classes
 * @covers     \theme_epure\mobile_app::preferences_css
 */
final class mobile_test extends \advanced_testcase {
    /**
     * The app gets the classes of the preferences of the user, when it opens the site and from its web service.
     */
    public function test_classes(): void {
        $this->resetAfterTest();
        set_config('theme', 'epure');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('theme_epure_a11y_scheme', 'yellow');
        set_user_preference('theme_epure_a11y_text', 150);
        $init = output\mobile::mobile_init([]);
        $this->assertStringContainsString('"', $init['javascript']);
        $this->assertStringNotContainsString('INITIAL_CLASSES', $init['javascript']);
        $this->assertStringContainsString('epure-a11y-scheme-yellow', $init['javascript']);
        $this->assertStringContainsString('epure-a11y-text-150', $init['javascript']);

        $result = external\display_classes::execute();
        $result = \core_external\external_api::clean_returnvalue(external\display_classes::execute_returns(), $result);
        $this->assertStringContainsString('epure-a11y-scheme-yellow', $result['classes']);
        $this->assertStringContainsString('epure-dark', $result['classes']);
    }

    /**
     * The page of the app: the choices of the panel, those of the user selected, each saved when it changes.
     */
    public function test_page(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('theme_epure_a11y_font', 'lexend');
        set_user_preference('theme_epure_a11y_spacing', 1);
        $page = output\mobile::mobile_preferences([]);
        $this->assertSame('lexend', $page['otherdata']['font']);
        $this->assertSame('true', $page['otherdata']['spacing']);
        $this->assertSame('', $page['otherdata']['motion']);
        $html = $page['templates'][0]['html'];
        foreach (['text', 'font', 'scheme', 'spacing', 'underline'] as $name) {
            $this->assertStringContainsString("epureSave('{$name}', CONTENT_OTHERDATA.{$name})", $html);
        }
        $this->assertStringContainsString('value="yellow"', $html);
        $this->assertStringContainsString('theme_epure_a11y_', $page['javascript']);
    }

    /**
     * The style sheet of the app applies every class of the preferences.
     */
    public function test_css(): void {
        $css = mobile_app::css('#2559A8');
        foreach (['epure-a11y-text-200', 'epure-a11y-font-opendyslexic', 'epure-a11y-spacing', 'epure-a11y-underline',
                'epure-a11y-motion', 'epure-a11y-focusring', 'epure-a11y-scheme-yellow', 'epure-a11y-scheme-cream'] as $class) {
            $this->assertStringContainsString('html.' . $class, $css);
        }
        $this->assertStringContainsString('--ion-background-color:#000 !important', $css);
    }
}
