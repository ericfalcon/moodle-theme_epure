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

namespace theme_epure\output;

/**
 * Renderer of the H5P contents: adds the style sheet that puts them in the colour of the brand.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_h5p_renderer extends \core_h5p\output\renderer {
    /**
     * Stylesheets loaded for H5P: those of Moodle (the custom H5P CSS of the site), then the colours of the brand.
     *
     * @param \stdClass[] $styles List of stylesheets that will be loaded
     * @param array $libraries Array of libraries indexed by the library's machineName
     * @param string $embedtype Possible values: div, iframe, external, editor
     */
    public function h5p_alter_styles(&$styles, array $libraries, string $embedtype) {
        parent::h5p_alter_styles($styles, $libraries, $embedtype);
        $url = \theme_epure\h5p::stylesheet_url();
        $styles[] = (object) ['path' => $url->out_omit_querystring(), 'version' => '?' . $url->get_query_string(false)];
    }
}
