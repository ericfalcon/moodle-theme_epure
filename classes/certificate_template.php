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
 * A template of certificate in the colours of the brand for the plugin Certificate (tool_certificate), made on the
 * request of an administrator: a landscape A4 page with the frame of the brand, the logo of the site, the title, the
 * name of the learner, the name of the course, the date of issue and the QR code to check it.
 *
 * The template is then the administrator's, as any template of the plugin: they edit it, duplicate it, use it in
 * their courses (Course certificate activity), and it stays as it is if the site leaves the theme. Making it again
 * creates another one; the ones made before are not changed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_template {
    /** @var float Middle of the landscape A4 page, in millimetres. */
    protected const MIDDLE = 148.5;

    /** @var string Colour of the texts that are not in the colour of the brand. */
    protected const INK = '#1f2937';

    /** @var string[] Types of images the PDF of the certificates can show. */
    protected const IMAGES = ['image/png', 'image/jpeg', 'image/gif'];

    /**
     * Whether the template can be made: the plugin Certificate installed, and GD to draw the frame.
     *
     * @return bool
     */
    public static function available(): bool {
        return \core_component::get_component_directory('tool_certificate') !== null
            && class_exists(\tool_certificate\template::class)
            && function_exists('imagecreatetruecolor');
    }

    /**
     * Address of the page that makes the template.
     *
     * @return \moodle_url
     */
    public static function url(): \moodle_url {
        return new \moodle_url('/theme/epure/certificate.php');
    }

    /**
     * Makes the template, in the system context and shared with the categories, in the colours of the brand of the
     * site and in the language of the administrator.
     *
     * @return \tool_certificate\template The template made.
     */
    public static function create(): \tool_certificate\template {
        $brand = palette::normalise(get_config('theme_epure', 'brandcolor'));
        $colours = palette::derive($brand);
        $site = format_string(get_site()->fullname, true, ['context' => \context_system::instance()]);
        $template = \tool_certificate\template::create((object) [
            'name' => get_string('certtemplate_name', 'theme_epure', $site),
            'contextid' => \context_system::instance()->id,
            'shared' => 1,
        ]);
        $page = $template->new_page();
        $page->save((object) []);
        $pageid = $page->get_id();
        $sequence = 0;

        // An element of the page, with an image of its own when given.
        $add = function (array $record, ?array $image = null) use ($pageid, &$sequence): void {
            $record += ['pageid' => $pageid, 'sequence' => ++$sequence];
            $element = new \tool_certificate\persistent\element(0, (object) $record);
            $element->save();
            if ($image) {
                get_file_storage()->create_file_from_string([
                    'contextid' => \context_system::instance()->id,
                    'component' => 'tool_certificate',
                    'filearea' => 'element',
                    'itemid' => $element->get('id'),
                    'filepath' => '/',
                    'filename' => $image['filename'],
                ], $image['content']);
            }
        };
        // A line of text centred on the page.
        $text = fn(string $name, string $element, string $data, string $font, int $size, string $colour, float $y) => [
            'name' => $name, 'element' => $element, 'data' => $data, 'font' => $font, 'fontsize' => $size,
            'colour' => $colour, 'posx' => self::MIDDLE, 'posy' => $y, 'width' => 0, 'refpoint' => 1,
        ];

        // The frame of the brand, drawn for the whole page.
        $add([
            'name' => get_string('certtemplate_frame', 'theme_epure'), 'element' => 'image',
            'data' => json_encode(['width' => 0, 'height' => 0, 'isbackground' => true]),
        ], ['filename' => 'epure-frame.png', 'content' => certificate_frame::png($brand)]);

        // The logo of the site, centred at the top.
        if ($logo = self::logo()) {
            $add([
                'name' => get_string('logo', 'admin'), 'element' => 'image', 'posx' => self::MIDDLE - 25, 'posy' => 24,
                'data' => json_encode(['width' => 50, 'height' => 0, 'isbackground' => false]),
            ], ['filename' => $logo->get_filename(), 'content' => $logo->get_content()]);
        }

        $title = get_string('certtemplate_title', 'theme_epure');
        $add($text($title, 'text', $title, 'freesansb', 30, $colours['text'], 58));
        $awarded = get_string('certtemplate_awardedto', 'theme_epure');
        $add($text($awarded, 'text', $awarded, 'freesans', 13, self::INK, 80));
        $add($text(get_string('fullnameuser'), 'userfield', 'fullname', 'freesansb', 26, self::INK, 89));
        $completing = get_string('certtemplate_completing', 'theme_epure');
        $add($text($completing, 'text', $completing, 'freesans', 13, self::INK, 110));
        $course = json_encode(['display' => 'coursefullname']);
        $add($text(get_string('fullnamecourse'), 'program', $course, 'freesansb', 20, $colours['text'], 119));
        $issued = get_string('certtemplate_issued', 'theme_epure');
        $add($text($issued, 'text', $issued, 'freesans', 11, self::INK, 140));
        $date = json_encode(['dateitem' => -1, 'dateformat' => 'strftimedate']);
        $add($text(get_string('date'), 'date', $date, 'freesansb', 12, self::INK, 146));

        // The QR code to check the certificate, at the bottom on the left; the name of the site on the right.
        $add([
            'name' => get_string('certtemplate_code', 'theme_epure'), 'element' => 'code', 'font' => 'freesans',
            'fontsize' => 10, 'data' => json_encode(['display' => 4]), 'colour' => '#000000',
            'posx' => 30, 'posy' => 152, 'width' => 28, 'refpoint' => 0,
        ]);
        $add([
            'name' => $site, 'element' => 'text', 'data' => $site, 'font' => 'freesansb', 'fontsize' => 12,
            'colour' => self::INK, 'posx' => 267, 'posy' => 172, 'width' => 0, 'refpoint' => 2,
        ]);
        return $template;
    }

    /**
     * The logo of the site the PDF can show: the logo of the theme, else the one of Appearance › Logos.
     *
     * @return \stored_file|null
     */
    public static function logo(): ?\stored_file {
        $fs = get_file_storage();
        $context = \context_system::instance();
        foreach ([['theme_epure', 'logo'], ['core_admin', 'logo'], ['core_admin', 'logocompact']] as [$component, $area]) {
            foreach ($fs->get_area_files($context->id, $component, $area, false, 'itemid, filepath, filename', false) as $file) {
                if (in_array($file->get_mimetype(), self::IMAGES, true)) {
                    return $file;
                }
            }
        }
        return null;
    }
}
