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
 * HTML e-mails in the colours of the brand, for the renderers of the pages and of the command line
 * (the scheduled tasks send most e-mails).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait email_renderer {
    /**
     * Renders a template; the HTML e-mails get the logo, the colour and the footer of the brand.
     *
     * @param string $templatename Template.
     * @param array|\stdClass $context Context.
     * @return string HTML.
     */
    public function render_from_template($templatename, $context) {
        if ($templatename === 'core/email_html' && \theme_epure\email::enabled()) {
            $context = (array) $context;
            $context['epure'] = \theme_epure\email::export((int) ($context['touserid'] ?? 0));
        }
        return parent::render_from_template($templatename, $context);
    }
}
