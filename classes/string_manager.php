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

use theme_epure\vocabulary\company;

/**
 * String manager that applies the IOMAD vocabulary: the words of the company of the user, else those of all companies.
 *
 * It is the standard string manager of Moodle, with one more step: once the strings of a
 * component are loaded (language pack and local customisations), the words chosen for
 * « company » and « department » replace those of the language pack. The rewritten strings are cached by company,
 * language and component.
 *
 * It is enabled by {@see hook_callbacks::after_config()} when words were chosen, unless
 * config.php already sets another custom string manager.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class string_manager extends \core_string_manager_standard {
    /** @var array<string, array> Rewritten strings of the request, by company, language and component. */
    protected array $companystrings = [];

    /** @var int|null Company of the current user, once known. */
    protected ?int $companyid = null;

    /** @var int|null User the company was found for. */
    protected ?int $companyuserid = null;

    /** @var bool Whether the company is being looked for (IOMAD may load strings meanwhile). */
    protected bool $resolving = false;

    /**
     * Loads the strings of a component, with the words of the company of the user.
     *
     * @param string $component Component.
     * @param string $lang Language.
     * @param bool $disablecache Do not use caches.
     * @param bool $disablelocal Ignore local customisations (the language customisation tool uses it): no company words either.
     * @return array String identifier => string.
     */
    public function load_component_strings($component, $lang, $disablecache = false, $disablelocal = false) {
        $strings = parent::load_component_strings($component, $lang, $disablecache, $disablelocal);
        if ($disablelocal || !$strings) {
            return $strings;
        }
        $companyid = $this->company_id();
        if (!($rewriter = company::rewriter($companyid, $lang))) {
            return $strings;
        }

        $key = $companyid . '_' . $lang . '_' . str_replace('-', '_', $component);
        if (!$disablecache && isset($this->companystrings[$key])) {
            return $this->companystrings[$key];
        }
        $cache = \cache::make('theme_epure', 'companystrings');
        $cachekey = $key . '_' . company::revision() . '_' . $this->get_key_suffix();
        if (!$disablecache && ($cached = $cache->get($cachekey)) !== false) {
            return $this->companystrings[$key] = $cached;
        }

        $english = $lang === 'en' ? $strings : parent::load_component_strings($component, 'en', $disablecache);
        foreach ($strings as $id => $string) {
            if (is_string($string) && ($new = $rewriter->rewrite($string, (string) ($english[$id] ?? ''))) !== null) {
                $strings[$id] = $new;
            }
        }
        if (!$disablecache) {
            $cache->set($cachekey, $strings);
        }
        return $this->companystrings[$key] = $strings;
    }

    /**
     * Clears the caches, including the strings of the companies.
     *
     * @param bool $phpunitreset Called by the PHPUnit reset.
     */
    public function reset_caches($phpunitreset = false) {
        parent::reset_caches($phpunitreset);
        $this->companystrings = [];
        \cache::make('theme_epure', 'companystrings')->purge();
    }

    /**
     * The IOMAD company of the current user, as IOMAD finds it.
     *
     * @return int Company identifier, 0 when there is none.
     */
    protected static function iomad_company_id(): int {
        global $CFG;
        if (!company_style::iomad_installed() || during_initial_install()) {
            return 0;
        }
        require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
        return max(0, (int) \iomad::get_my_companyid(\context_system::instance(), false));
    }

    /**
     * The company of the current user.
     *
     * @return int Company identifier, 0 when there is none.
     */
    protected function company_id(): int {
        global $USER;
        $userid = (int) ($USER->id ?? 0);
        if ($this->resolving || !$userid) {
            return 0;
        }
        if ($this->companyid === null || $this->companyuserid !== $userid) {
            $this->resolving = true;
            try {
                $this->companyid = self::iomad_company_id();
            } finally {
                $this->resolving = false;
            }
            $this->companyuserid = $userid;
        }
        return $this->companyid;
    }
}
