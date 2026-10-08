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
 * Nothing of Épure stays when the company leaves the theme: the frame of the company and IOMAD's « Use border »
 * are kept aside when the frame is made, and put back as soon as the company (or the site, for a company without
 * a theme of its own) no longer uses Épure. That is checked before IOMAD makes certificates: on its certificate
 * pages, on the completion of a course (which issues them) and after the company form is saved.
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

    /** @var string File area of Épure keeping the frame the company had before. */
    public const BACKUPAREA = 'certificateframebackup';

    /** @var string File name of the frame made by Épure. */
    public const FILENAME = 'epure-frame.png';

    /** @var string Prefix of the settings recording the companies with the frame, and what they had before. */
    public const SETTING = 'certificateframe_';

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
     * on (IOMAD's « Use border »). The first time, the frame and the setting the company had are kept aside.
     *
     * @param int $companyid Company.
     * @param string|null $brand Brand colour of the company.
     */
    public static function install(int $companyid, ?string $brand): void {
        global $DB;
        $context = \context_system::instance();
        $fs = get_file_storage();
        if (get_config('theme_epure', self::SETTING . $companyid) === false) {
            foreach ($fs->get_area_files($context->id, 'local_iomad', self::FILEAREA, $companyid, 'id', false) as $file) {
                $fs->create_file_from_storedfile(['component' => 'theme_epure', 'filearea' => self::BACKUPAREA], $file);
            }
            $useborder = company_style::iomad_installed()
                ? $DB->get_field('companycertificate', 'useborder', ['companyid' => $companyid]) : false;
            set_config(self::SETTING . $companyid, json_encode(['useborder' => $useborder]), 'theme_epure');
        }
        $fs->delete_area_files($context->id, 'local_iomad', self::FILEAREA, $companyid);
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_iomad',
            'filearea' => self::FILEAREA,
            'itemid' => $companyid,
            'filepath' => '/',
            'filename' => self::FILENAME,
        ], self::png($brand));
        // Without a record, IOMAD prints the frame; with one, only if it is turned on.
        if (company_style::iomad_installed()) {
            $DB->set_field('companycertificate', 'useborder', 1, ['companyid' => $companyid]);
        }
    }

    /**
     * Gives a company back the frame and the setting it had before the frame of Épure.
     *
     * @param int $companyid Company.
     */
    public static function remove(int $companyid): void {
        global $DB;
        $state = get_config('theme_epure', self::SETTING . $companyid);
        if ($state === false) {
            return;
        }
        $context = \context_system::instance();
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_iomad', self::FILEAREA, $companyid);
        foreach ($fs->get_area_files($context->id, 'theme_epure', self::BACKUPAREA, $companyid, 'id', false) as $file) {
            $fs->create_file_from_storedfile(['component' => 'local_iomad', 'filearea' => self::FILEAREA], $file);
        }
        $fs->delete_area_files($context->id, 'theme_epure', self::BACKUPAREA, $companyid);
        $useborder = json_decode($state, true)['useborder'] ?? false;
        if ($useborder !== false && $useborder !== null && company_style::iomad_installed()) {
            $DB->set_field('companycertificate', 'useborder', (int) $useborder, ['companyid' => $companyid]);
        }
        unset_config(self::SETTING . $companyid, 'theme_epure');
    }

    /**
     * Gives back their frame to the companies that no longer use Épure (another theme for the company, or for the
     * site when the company has none), or that were deleted.
     *
     * @param bool $all Whether to give back all the frames (uninstallation of the theme).
     */
    public static function clean_up(bool $all = false): void {
        global $DB;
        $config = get_config('theme_epure');
        foreach ((array) $config as $name => $value) {
            if (!str_starts_with($name, self::SETTING)) {
                continue;
            }
            $companyid = (int) substr($name, strlen(self::SETTING));
            $company = company_style::iomad_installed() ? $DB->get_record('company', ['id' => $companyid]) : false;
            if ($all || !$company || !theme_use::company($company)) {
                self::remove($companyid);
            }
        }
    }

    /**
     * Whether some companies have the frame of Épure.
     *
     * @return bool
     */
    public static function in_use(): bool {
        foreach (array_keys((array) get_config('theme_epure')) as $name) {
            if (str_starts_with($name, self::SETTING)) {
                return true;
            }
        }
        return false;
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
     * Gives back their frame to the companies that left Épure at the end of the request, after IOMAD's company
     * form saved its own fields (the theme of the company, and its field « Frame »).
     */
    public static function clean_up_after_request(): void {
        if (self::in_use()) {
            \core_shutdown_manager::register_function([self::class, 'clean_up']);
        }
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
