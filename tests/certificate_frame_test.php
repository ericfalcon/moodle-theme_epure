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

#[\PHPUnit\Framework\Attributes\CoversClass(certificate_frame::class)]
/**
 * Tests for the frame of the IOMAD certificates.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\certificate_frame
 */
final class certificate_frame_test extends \advanced_testcase {
    /**
     * The frame is a landscape A4 image, transparent inside, with the band in the brand colour.
     */
    public function test_png(): void {
        if (!function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('GD is not installed.');
        }
        $image = imagecreatefromstring(certificate_frame::png('#1C6E73'));
        $this->assertSame(certificate_frame::WIDTH, imagesx($image));
        $this->assertSame(certificate_frame::HEIGHT, imagesy($image));
        // The middle of the page is transparent; the band is in the fill of the palette.
        $this->assertSame(127, imagecolorsforindex($image, imagecolorat($image, 877, 620))['alpha']);
        $band = imagecolorsforindex($image, imagecolorat($image, 877, 45));
        $this->assertSame(palette::derive('#1C6E73')['fill'], palette::to_hex([$band['red'], $band['green'], $band['blue']]));

        $this->assertStringStartsWith('data:image/png;base64,', certificate_frame::preview('#1C6E73'));
    }

    /**
     * The frame is saved as the frame of the company in IOMAD, in place of the current one.
     */
    public function test_install(): void {
        global $DB;
        $this->resetAfterTest();
        if (!function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('GD is not installed.');
        }
        $iomad = $DB->get_manager()->table_exists('companycertificate');
        if ($iomad) {
            $DB->insert_record('companycertificate', ['companyid' => 7, 'uselogo' => 1, 'usewatermark' => 0,
                'usesignature' => 0, 'useborder' => 0, 'showgrade' => 0]);
        }
        certificate_frame::install(7, '#1C6E73');
        certificate_frame::install(7, '#9B2335');
        $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'local_iomad',
            certificate_frame::FILEAREA, 7, 'id', false);
        $this->assertCount(1, $files);
        $this->assertSame(certificate_frame::png('#9B2335'), reset($files)->get_content());
        if ($iomad) {
            $this->assertEquals(1, $DB->get_field('companycertificate', 'useborder', ['companyid' => 7]));
        }
    }
}
