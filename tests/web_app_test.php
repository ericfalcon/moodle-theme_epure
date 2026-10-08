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

#[\PHPUnit\Framework\Attributes\CoversClass(web_app::class)]
/**
 * Tests for the installable web app.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\web_app
 */
final class web_app_test extends \advanced_testcase {
    /**
     * The web app is offered when it is turned on and the pages are shown with Épure.
     */
    public function test_enabled(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->theme = 'epure';
        $this->assertFalse(web_app::enabled());
        set_config('webapp', 1, 'theme_epure');
        $this->assertTrue(web_app::enabled());
        $CFG->theme = 'boost';
        $this->assertFalse(web_app::enabled());
    }

    /**
     * The manifest has the name, the colour and the icons of the platform, and covers the site.
     */
    public function test_manifest(): void {
        global $CFG;
        $this->resetAfterTest();
        set_config('brandcolor', '#1C6E73', 'theme_epure');
        set_config('webappname', 'Campus', 'theme_epure');
        $manifest = web_app::manifest();
        $this->assertSame('Campus', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame(palette::derive('#1C6E73')['fill'], $manifest['theme_color']);
        $this->assertSame(web_app::scope(), $manifest['scope']);
        $this->assertStringStartsWith($CFG->wwwroot, $manifest['start_url']);
        $this->assertCount(4, $manifest['icons']);
        $this->assertSame(['any', 'maskable', 'any', 'maskable'], array_column($manifest['icons'], 'purpose'));
    }

    /**
     * Without an icon in the settings, the icon is drawn in the brand colour, at the size asked.
     */
    public function test_icon(): void {
        $this->resetAfterTest();
        $png = web_app::icon(192, '#1C6E73');
        $size = getimagesizefromstring($png);
        $this->assertSame([192, 192, 'image/png'], [$size[0], $size[1], $size['mime']]);
        // The corner is the brand colour.
        $image = imagecreatefromstring($png);
        $this->assertSame(
            palette::to_rgb(palette::derive('#1C6E73')['fill']),
            array_values(array_slice(imagecolorsforindex($image, imagecolorat($image, 2, 2)), 0, 3))
        );
        // Unknown sizes give the largest one.
        $this->assertSame(512, getimagesizefromstring(web_app::icon(1000))[0]);
    }

    /**
     * The service worker keeps the offline page while the web app is on; turned off, or with another theme, it removes
     * itself.
     */
    public function test_service_worker(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->theme = 'epure';
        set_config('webapp', 1, 'theme_epure');
        $this->assertStringContainsString('caches.match(OFFLINE)', web_app::service_worker());
        $this->assertStringContainsString('navigator.serviceWorker.register', web_app::register_js());

        $CFG->theme = 'boost';
        $this->assertStringContainsString('self.registration.unregister()', web_app::service_worker());

        $CFG->theme = 'epure';
        set_config('webapp', 0, 'theme_epure');
        $this->assertStringContainsString('self.registration.unregister()', web_app::service_worker());
        // Never turned on, the pages do not look for a service worker; once used, they remove it.
        $this->assertSame('', web_app::register_js());
        set_config('webappused', 1, 'theme_epure');
        $this->assertStringContainsString('registration.unregister()', web_app::register_js());
    }
}
