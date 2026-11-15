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
 * Tests for the getting started with Épure.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\setup
 */
final class setup_test extends \advanced_testcase {
    /**
     * Each step shows a tab of the settings of the theme, as in the settings; step 1 the names of the site too.
     */
    public function test_pages(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->assertSame('theme_epure_brand', setup::page('identity')->name);
        $this->assertSame('theme_epure_login', setup::page('login')->name);
        $this->assertSame('theme_epure_footer', setup::page('footer')->name);
        $this->assertNull(setup::page('accessibility'));
        $this->assertSame(['fullname', 'shortname'], array_map(fn($setting) => $setting->name, setup::site_settings()));
        // The tab as in the settings: its logos, its colours and its palette.
        $names = array_map(fn($setting) => $setting->name, (array) setup::page('identity')->settings);
        $expected = ['logo', 'logoonbrand', 'logomobile', 'favicon', 'brandcolor', 'accentcolor', 'headerstyle', 'palettepreview'];
        foreach ($expected as $name) {
            $this->assertContains($name, $names);
        }
    }

    /**
     * Each step saves as the page of the settings saves: the names of the site, the settings of the theme, validated; a
     * model fills only what is left empty.
     */
    public function test_save(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->assertTrue(setup::save('identity', ['s__fullname' => 'Clinique des Tilleuls', 's__shortname' => 'Tilleuls',
            's_theme_epure_brandcolor' => '#1E6B52', 's_theme_epure_headerstyle' => 'brand']));
        $this->assertSame('Clinique des Tilleuls', get_site()->fullname);
        $this->assertSame('Tilleuls', get_site()->shortname);
        $this->assertSame('#1E6B52', get_config('theme_epure', 'brandcolor'));
        $this->assertSame('brand', get_config('theme_epure', 'headerstyle'));

        // A colour that is not one is not saved, and the error is kept for the field.
        $this->assertFalse(setup::save('identity', ['s_theme_epure_brandcolor' => 'nonsense']));
        $this->assertSame('#1E6B52', get_config('theme_epure', 'brandcolor'));
        $this->assertArrayHasKey('s_theme_epure_brandcolor', admin_get_root()->errors);

        $this->assertTrue(setup::save('login', ['model' => 'company', 's_theme_epure_logintagline' => 'Notre campus',
            's_theme_epure_logintext' => '']));
        $this->assertSame('Notre campus', get_config('theme_epure', 'logintagline'));
        $this->assertSame(setup::model_texts('company')['logintext'], get_config('theme_epure', 'logintext'));
        $this->assertSame('0', (string) get_config('theme_epure', 'catalogue'));

        $this->assertTrue(setup::save('footer', ['s_theme_epure_footertext' => 'Formation continue',
            's_theme_epure_footerlegalurl' => 'https://example.com/legal']));
        $this->assertSame('Formation continue', get_config('theme_epure', 'footertext'));
        $this->assertSame('https://example.com/legal', get_config('theme_epure', 'footerlegalurl'));
    }

    /**
     * Offered on the home of the administration to an administrator, until it is finished or dismissed.
     */
    public function test_offered(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(setup::offered());
        $this->setAdminUser();
        $this->assertTrue(setup::offered());
        $this->assertNotNull(admin_home::export()['setup']);
        setup::finish();
        $this->assertFalse(setup::offered());
        $this->assertNull(admin_home::export()['setup']);
    }
}
