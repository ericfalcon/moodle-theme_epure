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
 * Frame of the IOMAD certificates in the colour of the brand of a company.
 *
 * The certificates of IOMAD (mod_iomadcertificate, type « company ») are PDF documents drawn over four images of
 * the company: its logo, a signature, a frame and a watermark (Edit company › Certificate). On request, in the
 * Épure part of the company form, the theme draws the frame in the brand colour of the company and saves it as
 * the frame of the company: a band of the colour, a fine line inside and diamonds in the corners, on a
 * transparent background, at the size of a landscape A4 page.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_frame {
    /** @var int Width of the image: landscape A4 at 150 dpi. */
    public const WIDTH = 1754;

    /** @var int Height of the image. */
    public const HEIGHT = 1240;

    /** @var string File area of the frames of the companies in IOMAD. */
    public const FILEAREA = 'companycertificateborder';

    /**
     * Whether the frame can be made: IOMAD certificates installed, and GD to draw.
     *
     * @return bool
     */
    public static function available(): bool {
        return company_style::iomad_installed()
            && \core_component::get_component_directory('mod_iomadcertificate') !== null
            && function_exists('imagecreatetruecolor');
    }

    /**
     * The frame, as a PNG image.
     *
     * @param string|null $brand Brand colour.
     * @param float $scale Size of the image, 1 for the certificate.
     * @return string PNG data.
     */
    public static function png(?string $brand, float $scale = 1): string {
        $p = palette::derive($brand);
        $w = (int) round(self::WIDTH * $scale);
        $h = (int) round(self::HEIGHT * $scale);
        $px = fn(float $value) => (int) round($value * $scale);
        $image = imagecreatetruecolor($w, $h);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
        imagealphablending($image, true);
        $colour = function (string $hex) use ($image) {
            [$r, $g, $b] = palette::to_rgb($hex);
            return imagecolorallocate($image, $r, $g, $b);
        };
        // The band of the colour of the brand, then the fine line inside, in a lighter tint.
        $band = $colour($p['fill']);
        $line = $colour(palette::mix($p['fill'], '#FFFFFF', 0.45));
        $frame = function (int $margin, int $thickness, $fill) use ($image, $w, $h) {
            imagefilledrectangle($image, $margin, $margin, $w - $margin - 1, $margin + $thickness - 1, $fill);
            imagefilledrectangle($image, $margin, $h - $margin - $thickness, $w - $margin - 1, $h - $margin - 1, $fill);
            imagefilledrectangle($image, $margin, $margin, $margin + $thickness - 1, $h - $margin - 1, $fill);
            imagefilledrectangle($image, $w - $margin - $thickness, $margin, $w - $margin - 1, $h - $margin - 1, $fill);
        };
        $frame($px(36), max(2, $px(22)), $band);
        $frame($px(80), max(1, $px(4)), $line);
        // Diamonds of the brand colour on the corners of the line.
        $centre = $px(82);
        $size = $px(18);
        foreach ([[$centre, $centre], [$w - $centre, $centre], [$centre, $h - $centre], [$w - $centre, $h - $centre]] as [$x, $y]) {
            imagefilledpolygon($image, [$x, $y - $size, $x + $size, $y, $x, $y + $size, $x - $size, $y], $band);
        }
        ob_start();
        imagepng($image);
        imagedestroy($image);
        return (string) ob_get_clean();
    }

    /**
     * Saves the frame of a company in IOMAD, in place of its current frame, and turns the frame of its certificates
     * on (IOMAD's « Use border »).
     *
     * @param int $companyid Company.
     * @param string|null $brand Brand colour of the company.
     */
    public static function install(int $companyid, ?string $brand): void {
        global $DB;
        $context = \context_system::instance();
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_iomad', self::FILEAREA, $companyid);
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_iomad',
            'filearea' => self::FILEAREA,
            'itemid' => $companyid,
            'filepath' => '/',
            'filename' => 'epure-frame.png',
        ], self::png($brand));
        // Without a record, IOMAD prints the frame; with one, only if it is turned on.
        if (company_style::iomad_installed()) {
            $DB->set_field('companycertificate', 'useborder', 1, ['companyid' => $companyid]);
        }
    }

    /**
     * Saves the frame of a company at the end of the request: IOMAD's company form saves its own field « Frame »
     * after the event that saves the Épure fields, and would replace it.
     *
     * @param int $companyid Company.
     * @param string|null $brand Brand colour of the company.
     */
    public static function install_after_request(int $companyid, ?string $brand): void {
        \core_shutdown_manager::register_function([self::class, 'install'], [$companyid, $brand]);
    }

    /**
     * A small preview of the frame, for the form.
     *
     * @param string|null $brand Brand colour.
     * @return string Data URL.
     */
    public static function preview(?string $brand): string {
        return 'data:image/png;base64,' . base64_encode(self::png($brand, .2));
    }
}
