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

namespace theme_epure\output\core_user;

use core_user\output\myprofile\tree;
use theme_epure\profile;

/**
 * Renderer of the profile with Épure: the summary of the own profile at the top, the cards that matter first and
 * the others grouped under « More information » ({@see profile}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class myprofile_renderer extends \core_user\output\myprofile\renderer {
    /**
     * Renders the whole tree.
     *
     * @param tree $tree Profile.
     * @return string HTML.
     */
    public function render_tree(tree $tree) {
        $primary = '';
        $more = '';
        $editurl = null;
        foreach ($tree->categories as $name => $category) {
            $html = $this->render($category);
            if (in_array($name, profile::PRIMARY, true)) {
                $primary .= $html;
            } else {
                $more .= $html;
            }
            if ($name === 'contact' && isset($category->nodes['editprofile'])) {
                $editurl = $category->nodes['editprofile']->url;
            }
        }
        $summary = profile::summary($this->output, $editurl instanceof \moodle_url ? $editurl : null);
        $html = $summary ? $this->output->render_from_template('theme_epure/profile_summary', $summary) : '';
        $html .= \html_writer::div($primary, 'profile_tree');
        if (trim($more) !== '') {
            $html .= \html_writer::tag(
                'details',
                \html_writer::tag('summary', get_string('profile_more', 'theme_epure'))
                    . \html_writer::div($more, 'profile_tree'),
                ['class' => 'epure-profile-more']
            );
        }
        return $html;
    }
}
