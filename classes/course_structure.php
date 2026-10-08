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
 * The activities of a course in the order the learner sees them, subsections included.
 *
 * Moodle keeps the sections of the subsections (mod_subsection, sections « delegated » to an activity) after all the
 * sections of the course, and course_modinfo::get_cms() lists their activities there, at the end of the course. On
 * the course page, they are shown at the place of the subsection, inside its section: the theme follows that order
 * for the next activity, the position in the course and the previous and next activities.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_structure {
    /**
     * The activities of a course in the order of the course page: those of a subsection at the place of the
     * subsection. The subsections themselves are left out: they are parts of a section, not activities.
     *
     * @param \course_modinfo $modinfo Course.
     * @return \cm_info[] By id, in order.
     */
    public static function cms(\course_modinfo $modinfo): array {
        $cms = [];
        $seen = [];
        foreach ($modinfo->get_listed_section_info_all() as $section) {
            self::add_section($modinfo, $section, $cms, $seen);
        }
        return $cms;
    }

    /**
     * The activities of a section, with those of its subsections at their place.
     *
     * @param \course_modinfo $modinfo Course.
     * @param \section_info $section Section.
     * @param \cm_info[] $cms Activities, added to.
     * @param bool[] $seen Sections already walked, against a loop.
     */
    protected static function add_section(\course_modinfo $modinfo, \section_info $section, array &$cms, array &$seen): void {
        if (isset($seen[$section->id])) {
            return;
        }
        $seen[$section->id] = true;
        foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if ($delegated = $cm->get_delegated_section_info()) {
                // The activities of a hidden subsection are hidden too: uservisible tells for each one.
                self::add_section($modinfo, $delegated, $cms, $seen);
                continue;
            }
            $cms[$cm->id] = $cm;
        }
    }

    /**
     * The section an activity is shown in on the course page, and the subsection it is in, if any.
     *
     * @param \cm_info $cm Activity.
     * @return array{0: \section_info|null, 1: \section_info|null} The section of the course, and the subsection.
     */
    public static function sections_of(\cm_info $cm): array {
        $section = $cm->get_section_info();
        if ($section && $section->is_delegated() && ($delegate = $section->get_component_instance())) {
            $parent = method_exists($delegate, 'get_parent_section') ? $delegate->get_parent_section() : null;
            return [$parent, $section];
        }
        return [$section, null];
    }

    /**
     * Name of the place of an activity in the course: its section, and its subsection after it.
     *
     * @param \cm_info $cm Activity.
     * @return string For example « Week 2 › Part A ».
     */
    public static function place_name(\cm_info $cm): string {
        [$section, $subsection] = self::sections_of($cm);
        $names = [];
        foreach ([$section, $subsection] as $part) {
            if ($part) {
                $names[] = get_section_name($cm->course, $part);
            }
        }
        return implode(' › ', $names);
    }
}
