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
 * Achievements of a learner: the badges they earned, as thumbnails, and their certificates (Certificate, Custom
 * certificate, IOMAD), for the dashboard and their own profile.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class achievements {
    /** @var int Badges shown. */
    public const BADGES = 6;

    /** @var int Certificates shown. */
    public const CERTIFICATES = 3;

    /**
     * Context of the template theme_epure/achievements.
     *
     * @param int $userid Learner, the current user: their certificates are theirs only.
     * @param string|null $certificatesurl Address of all their certificates, when the platform issues some.
     * @return array|null Null when they have nothing to show.
     */
    public static function export(int $userid, ?string $certificatesurl = null): ?array {
        $badges = self::badges($userid);
        $certificates = self::certificates($userid);
        if (!$badges['items'] && !$certificates && !$certificatesurl) {
            return null;
        }
        return [
            'badges' => $badges['items'],
            'hasbadges' => !empty($badges['items']),
            'morebadges' => $badges['total'] > count($badges['items']) ? $badges['total'] - count($badges['items']) : 0,
            'badgesurl' => $badges['items'] ? (new \moodle_url('/badges/mybadges.php'))->out(false) : null,
            'certificates' => $certificates,
            'hascertificates' => !empty($certificates),
            'certificatesurl' => $certificatesurl,
        ];
    }

    /**
     * On their own profile, with Épure: the gallery of their achievements, all their badges (Moodle shows there their
     * public badges only) and their certificates, in place of the list of Moodle. The profile of the others keeps
     * their public badges.
     *
     * @param \core_user\output\myprofile\tree $tree Profile.
     * @param \stdClass $user Owner of the profile.
     * @param bool $iscurrentuser Whether it is the profile of the current user.
     * @param \stdClass|null $course Course of the profile, none for the profile of the site.
     */
    public static function myprofile(
        \core_user\output\myprofile\tree $tree,
        \stdClass $user,
        bool $iscurrentuser,
        ?\stdClass $course
    ): void {
        global $OUTPUT;
        if (!$iscurrentuser || $course || isguestuser($user) || !hook_callbacks::epure_page()) {
            return;
        }
        $context = self::export((int) $user->id, learner_dashboard::certificates_url((int) $user->id));
        if (!$context) {
            return;
        }
        // The list of Moodle repeats the public badges of the gallery.
        $nodes = new \ReflectionProperty($tree, 'nodes');
        $list = $nodes->getValue($tree);
        unset($list['localbadges']);
        $nodes->setValue($tree, $list);

        $tree->add_category(new \core_user\output\myprofile\category(
            'epureachievements',
            get_string('achievements', 'theme_epure'),
            'contact'
        ));
        $tree->add_node(new \core_user\output\myprofile\node(
            'epureachievements',
            'epureachievements',
            '',
            null,
            null,
            $OUTPUT->render_from_template('theme_epure/achievements', $context)
        ));
    }

    /**
     * The latest badges of a user, all of them, public or not: they are seen by the user only.
     *
     * @param int $userid User.
     * @return array With items (name, image, date, url) and total.
     */
    public static function badges(int $userid): array {
        global $CFG;
        if (empty($CFG->enablebadges)) {
            return ['items' => [], 'total' => 0];
        }
        require_once($CFG->libdir . '/badgeslib.php');
        $records = badges_get_user_badges($userid);
        usort($records, fn($a, $b) => $b->dateissued <=> $a->dateissued);
        $items = [];
        foreach (array_slice($records, 0, self::BADGES) as $record) {
            $context = $record->type == BADGE_TYPE_COURSE && $record->courseid
                ? \context_course::instance($record->courseid, IGNORE_MISSING) : \context_system::instance();
            if (!$context) {
                continue;
            }
            $items[] = [
                'name' => format_string($record->name, true, ['context' => $context]),
                'image' => \moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $record->id, '/', 'f3')
                    ->out(false),
                'date' => userdate($record->dateissued, get_string('strftimedatefullshort', 'langconfig')),
                'url' => (new \moodle_url('/badges/badge.php', ['hash' => $record->uniquehash]))->out(false),
            ];
        }
        return ['items' => $items, 'total' => count($records)];
    }

    /**
     * The latest certificates of a user: Certificate (tool_certificate), Custom certificate, IOMAD certificates of
     * the completions tracked.
     *
     * @param int $userid User.
     * @param int $limit Number of certificates, 0 for all of them.
     * @return array[] Each with name, date and url, the latest first.
     */
    public static function certificates(int $userid, int $limit = self::CERTIFICATES): array {
        global $DB;
        $certificates = [];
        try {
            if (\core_component::get_component_directory('tool_certificate')) {
                $records = $DB->get_records_sql(
                    'SELECT i.id, i.code, i.timecreated, t.name, t.contextid
                       FROM {tool_certificate_issues} i
                       JOIN {tool_certificate_templates} t ON t.id = i.templateid
                      WHERE i.userid = :userid
                   ORDER BY i.timecreated DESC',
                    ['userid' => $userid],
                    0,
                    $limit
                );
                foreach ($records as $record) {
                    $certificates[] = ['name' => format_string($record->name), 'time' => (int) $record->timecreated,
                        'url' => (new \moodle_url('/admin/tool/certificate/view.php', ['code' => $record->code]))->out(false)];
                }
            }
            if (\core_component::get_component_directory('mod_customcert')) {
                $records = $DB->get_records_sql(
                    'SELECT i.id, i.timecreated, c.name, c.id AS customcertid, c.course
                       FROM {customcert_issues} i
                       JOIN {customcert} c ON c.id = i.customcertid
                      WHERE i.userid = :userid
                   ORDER BY i.timecreated DESC',
                    ['userid' => $userid],
                    0,
                    $limit
                );
                foreach ($records as $record) {
                    $cm = get_coursemodule_from_instance('customcert', $record->customcertid, $record->course);
                    if ($cm) {
                        $certificates[] = ['name' => format_string($record->name), 'time' => (int) $record->timecreated,
                            'url' => (new \moodle_url('/mod/customcert/view.php', ['id' => $cm->id, 'downloadown' => 1]))
                                ->out(false)];
                    }
                }
            }
            if (iomad::installed() && $DB->get_manager()->table_exists(iomad::table('local_iomad_track_certs'))) {
                $records = $DB->get_records_sql(
                    'SELECT c.id, c.trackid, c.filename, t.coursename, t.timecompleted
                       FROM {' . iomad::table('local_iomad_track_certs') . '} c
                       JOIN {' . iomad::table('local_iomad_track') . '} t ON t.id = c.trackid
                      WHERE t.userid = :userid
                   ORDER BY t.timecompleted DESC',
                    ['userid' => $userid],
                    0,
                    $limit
                );
                $area = iomad::renamed() ? '/local_iomad/certificate_issue/' : '/local_iomad_track/issue/';
                foreach ($records as $record) {
                    $certificates[] = ['name' => format_string($record->coursename), 'time' => (int) $record->timecompleted,
                        'url' => \moodle_url::make_file_url('/pluginfile.php', '/' . \context_user::instance($userid)->id
                            . $area . $record->trackid . '/' . $record->filename)->out(false)];
                }
            }
        } catch (\Throwable $e) {
            debugging('Épure could not read the certificates: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        usort($certificates, fn($a, $b) => $b['time'] <=> $a['time']);
        $certificates = $limit ? array_slice($certificates, 0, $limit) : $certificates;
        foreach ($certificates as &$certificate) {
            $certificate['date'] = $certificate['time']
                ? userdate($certificate['time'], get_string('strftimedatefullshort', 'langconfig')) : '';
        }
        return $certificates;
    }
}
