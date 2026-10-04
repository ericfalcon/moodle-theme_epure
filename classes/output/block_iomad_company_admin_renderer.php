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

use theme_epure\company_style;

/**
 * Renderer of the IOMAD dashboard (block_iomad_company_admin), restyled by Épure.
 *
 * The data of the dashboard (actions, capabilities, tabs, company selector) stay those of IOMAD;
 * only their presentation changes: the selected company in a header, and in each tab the actions
 * as cards grouped by intention (create, manage, configure, import and export, follow).
 *
 * This class is only loaded on IOMAD sites, when the dashboard is displayed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_company_admin_renderer extends \block_iomad_company_admin\output\renderer {
    /** @var string[] Intentions, in their display order. */
    public const INTENTS = ['create', 'configure', 'manage', 'transfer', 'follow'];

    /** @var array<string, string> Intention of an action, from the small icon IOMAD gives it. */
    protected const ICON_INTENTS = [
        'fa-plus-square' => 'create',
        'fa-square-plus' => 'create',
        'fa-plus' => 'create',
        'fa-upload' => 'transfer',
        'fa-download' => 'transfer',
        'fa-arrow-up-from-bracket' => 'transfer',
        'fa-bar-chart-o' => 'follow',
        'fa-bar-chart' => 'follow',
        'fa-chart-bar' => 'follow',
        'fa-edit' => 'configure',
        'fa-pen-to-square' => 'configure',
        'fa-wrench' => 'configure',
        'fa-info-circle' => 'configure',
        'fa-circle-info' => 'configure',
        'fa-microchip' => 'configure',
    ];

    /**
     * Renders the IOMAD dashboard.
     *
     * @param \block_iomad_company_admin\output\adminblock $adminblock The dashboard.
     * @return string HTML.
     */
    public function render_adminblock(\block_iomad_company_admin\output\adminblock $adminblock) {
        $data = $adminblock->export_for_template($this);
        $company = company_style::current_company();
        $category = $company ? company_style::company_category($company) : null;
        $data['panes'] = array_map(function ($pane) use ($category) {
            $pane = (array) $pane;
            // The course category of the company leads the actions on courses.
            if ($category && ($pane['category'] ?? '') === 'CourseAdmin') {
                array_unshift($pane['items'], ['url' => $category['url'], 'action' => $category['label'],
                    'icon' => 'fa-folder-open', 'iconsmall' => '']);
            }
            return $this->group_pane($pane);
        }, $data['panes']);
        $data['company'] = $this->company_header($data['companyselect'] ?? null);
        $data['company']['category'] = $category;
        return $this->render_from_template('theme_epure/iomad_dashboard', $data);
    }

    /**
     * The intention of an action.
     *
     * @param array $item Action of the dashboard.
     * @return string One of {@see self::INTENTS}.
     */
    public static function intent(array $item): string {
        $small = trim((string) ($item['iconsmall'] ?? ''));
        foreach (preg_split('/\s+/', $small) as $class) {
            if (isset(self::ICON_INTENTS[$class])) {
                return self::ICON_INTENTS[$class];
            }
        }
        // Without small icons (setting iomad_useicons), the address tells the intention.
        $url = (string) ($item['url'] ?? '');
        if (preg_match('/createnew|_create|create_|add_|_add\b/i', $url)) {
            return 'create';
        }
        if (preg_match('/upload|import|download|export/i', $url)) {
            return 'transfer';
        }
        if (preg_match('/report/i', $url)) {
            return 'follow';
        }
        return 'manage';
    }

    /**
     * Groups the actions of a tab by intention.
     *
     * @param array $pane Tab of the dashboard.
     * @return array The tab, with groups of actions.
     */
    protected function group_pane(array $pane): array {
        $groups = [];
        foreach ($pane['items'] ?? [] as $item) {
            $item = (array) $item;
            $icon = (string) ($item['icon'] ?? '');
            $groups[self::intent($item)][] = [
                'url' => (string) $item['url'],
                'label' => $item['action'] ?? '',
                // With iomad_useicons, IOMAD gives an image tag instead of an icon class: a neutral icon replaces it.
                'icon' => preg_match('/^[\w\s-]+$/', $icon) ? $icon : 'fa-circle-dot',
            ];
        }
        $pane['groups'] = [];
        foreach (self::INTENTS as $intent) {
            if (!empty($groups[$intent])) {
                $pane['groups'][] = [
                    'id' => 'epure-iomad-' . $pane['category'] . '-' . $intent,
                    'label' => get_string('iomadintent_' . $intent, 'theme_epure'),
                    'items' => $groups[$intent],
                ];
            }
        }
        // A heading is only useful when the tab has several groups.
        $pane['showheadings'] = count($pane['groups']) > 1;
        return $pane;
    }

    /**
     * The header of the dashboard: the selected company, and the company selector.
     *
     * @param object|null $companyselect Company selector of IOMAD.
     * @return array
     */
    protected function company_header(?object $companyselect): array {
        $company = company_style::current_company();
        // Only the logo of the company itself: the logo of the site would suggest another company.
        $logo = null;
        if ($company && $this->output instanceof core_renderer) {
            $logo = $this->output->company_logo_url(['logo', 'logocompact']);
        }
        return [
            'name' => $company ? format_string($company->name) : null,
            // IOMAD's own word for a company, so that the vocabulary of the platform and of the company applies.
            'label' => get_string('company', 'block_iomad_company_admin'),
            'logourl' => $logo ? $logo->out(false) : null,
            'onecompany' => !empty($companyselect->onecompany),
            'selectform' => $companyselect->selectform ?? '',
            'suspended' => $companyselect->suspended ?? '',
        ];
    }
}
