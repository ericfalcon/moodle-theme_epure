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
        $files = get_file_storage()->get_area_files(
            \context_system::instance()->id,
            'local_iomad',
            certificate_frame::FILEAREA,
            7,
            'id',
            false
        );
        $this->assertCount(1, $files);
        $this->assertSame(certificate_frame::png('#9B2335'), reset($files)->get_content());
        if ($iomad) {
            $this->assertEquals(1, $DB->get_field('companycertificate', 'useborder', ['companyid' => 7]));
        }
    }
    /**
     * The frame the company had, and IOMAD's « Use border », come back when the frame of Épure is removed, as when
     * the company leaves Épure; a deleted company or another theme gives the frame back on the clean up.
     */
    public function test_remove(): void {
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
        $fs = get_file_storage();
        $context = \context_system::instance();
        $area = ['contextid' => $context->id, 'component' => 'local_iomad', 'filearea' => certificate_frame::FILEAREA,
            'itemid' => 7, 'filepath' => '/'];
        $fs->create_file_from_string($area + ['filename' => 'mine.png'], 'my frame');
        $names = fn() => array_map(
            fn($file) => $file->get_filename(),
            array_values($fs->get_area_files($context->id, 'local_iomad', certificate_frame::FILEAREA, 7, 'id', false))
        );

        certificate_frame::install(7, '#1C6E73');
        certificate_frame::install(7, '#9B2335');
        $this->assertSame([certificate_frame::FILENAME], $names());
        $this->assertTrue(certificate_frame::in_use());

        certificate_frame::remove(7);
        $this->assertSame(['mine.png'], $names());
        $this->assertFalse(certificate_frame::in_use());
        $this->assertEmpty($fs->get_area_files($context->id, 'theme_epure', certificate_frame::BACKUPAREA, 7, 'id', false));
        if ($iomad) {
            $this->assertEquals(0, $DB->get_field('companycertificate', 'useborder', ['companyid' => 7]));
        }

        // Without IOMAD, or for a company that does not exist, the clean up gives the frame back.
        certificate_frame::install(7, '#1C6E73');
        certificate_frame::clean_up();
        $this->assertSame(['mine.png'], $names());
        $this->assertFalse(certificate_frame::in_use());
    }
}
