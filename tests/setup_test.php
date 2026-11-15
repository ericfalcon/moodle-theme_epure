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
     * Each step writes the settings of Moodle and of the theme; a model fills only what is left empty.
     */
    public function test_save(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        setup::save('identity', (object) ['fullname' => 'Clinique des Tilleuls', 'shortname' => 'Tilleuls']);
        $this->assertSame('Clinique des Tilleuls', get_site()->fullname);
        $this->assertSame('Tilleuls', get_site()->shortname);

        setup::save('colours', (object) ['brandcolor' => '1e6b52', 'accentcolor' => '', 'headerstyle' => 'brand']);
        $this->assertSame('#1E6B52', get_config('theme_epure', 'brandcolor'));
        $this->assertSame('', get_config('theme_epure', 'accentcolor'));
        $this->assertSame('brand', get_config('theme_epure', 'headerstyle'));

        // The model of a company: its texts where nothing was written, no catalogue.
        setup::save('login', (object) ['model' => 'company', 'logintagline' => 'Notre campus', 'logintext' => '',
            'catalogue' => 1]);
        $this->assertSame('Notre campus', get_config('theme_epure', 'logintagline'));
        $this->assertSame(get_string('setup_model_company_text', 'theme_epure'), get_config('theme_epure', 'logintext'));
        $this->assertSame('0', get_config('theme_epure', 'catalogue'));
        // Without a model, the choice of the catalogue.
        setup::save('login', (object) ['model' => '', 'logintagline' => '', 'logintext' => 'Bienvenue', 'catalogue' => 1]);
        $this->assertSame('', get_config('theme_epure', 'logintagline'));
        $this->assertSame('1', get_config('theme_epure', 'catalogue'));

        set_config('footer', 0, 'theme_epure');
        setup::save('footer', (object) ['footertext' => ' Formation continue ', 'footerlegalurl' => 'https://example.com/legal',
            'footerprivacyurl' => '', 'footercontacturl' => '']);
        $this->assertSame('Formation continue', get_config('theme_epure', 'footertext'));
        $this->assertSame('https://example.com/legal', get_config('theme_epure', 'footerlegalurl'));
        $this->assertSame('1', get_config('theme_epure', 'footer'));
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

    /**
     * Step 1 changes the logo that Épure shows: the logo of the site, or the logo of the theme when it has one, which
     * comes before it.
     */
    public function test_logo(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $draft = function (string $filename): int {
            $itemid = file_get_unused_draft_itemid();
            get_file_storage()->create_file_from_string(['contextid' => \context_user::instance(get_admin()->id)->id,
                'component' => 'user', 'filearea' => 'draft', 'itemid' => $itemid, 'filepath' => '/',
                'filename' => $filename], 'logo');
            return $itemid;
        };
        $identity = fn(int $logo) => (object) ['fullname' => 'Clinique', 'shortname' => 'Clinique', 'logo' => $logo];

        $this->assertFalse(setup::logo_target()['theme']);
        setup::save('identity', $identity($draft('site.png')));
        $this->assertSame('/site.png', get_config('core_admin', 'logo'));

        // A logo of the theme is shown instead: it is the one changed, the logo of the site stays.
        set_config('logo', '/theme.png', 'theme_epure');
        $this->assertSame(['component' => 'theme_epure', 'setting' => 'logo', 'theme' => true], setup::logo_target());
        setup::save('identity', $identity($draft('nouveau.png')));
        $this->assertSame('/nouveau.png', get_config('theme_epure', 'logo'));
        $this->assertSame('/site.png', get_config('core_admin', 'logo'));
        $this->assertStringEndsWith('/nouveau.png', logos::main_url()->out(false));

        // The other logos of the tab Identity, at the same step.
        $data = $identity(0);
        $data->logoonbrand = $draft('blanc.svg');
        $data->favicon = $draft('icone.png');
        setup::save('identity', $data);
        $this->assertSame('/blanc.svg', get_config('theme_epure', 'logoonbrand'));
        $this->assertSame('/icone.png', get_config('theme_epure', 'favicon'));
        $this->assertSame('', (string) get_config('theme_epure', 'logomobile'));
        $this->assertSame('/nouveau.png', get_config('theme_epure', 'logo'));
    }
}
