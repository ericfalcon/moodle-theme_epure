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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Moodle\BehatExtension\Exception\SkippedException;
use theme_epure\iomad;

/**
 * Steps of the Behat tests of theme_epure: IOMAD companies (IOMAD 4.5 has no data generator), and the colours of the
 * page.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_epure extends behat_base {
    /**
     * What the visual regression hides, as content that changes from a run to another: the dates (elements time, the
     * « Last modified » of the resources, the date fields of the forms), the year of the footer, and the image of the
     * banner of a course, which Moodle generates from the id of the course when it has none.
     */
    protected const VISUAL_MASK = ['time', '.modified', '.fdate_time_selector', '.fdate_selector',
        '.epure-footer-copyright', '.epure-course-banner-image'];

    /**
     * Skips the scenario without IOMAD.
     *
     * @Given /^IOMAD is installed$/
     */
    public function iomad_is_installed(): void {
        if (!iomad::installed()) {
            throw new SkippedException('IOMAD is not installed.');
        }
    }

    /**
     * Skips the scenario with IOMAD.
     *
     * @Given /^IOMAD is not installed$/
     */
    public function iomad_is_not_installed(): void {
        if (iomad::installed()) {
            throw new SkippedException('IOMAD is installed.');
        }
    }

    /**
     * Creates IOMAD companies, each with its top department.
     *
     * The columns are those of IOMAD's company table: name and shortname, and for example theme and headingcolor.
     *
     * @Given /^the following IOMAD companies exist:$/
     * @param TableNode $data Companies.
     */
    public function the_following_iomad_companies_exist(TableNode $data): void {
        global $DB;
        foreach ($data->getHash() as $row) {
            $company = (object) ($row + ['city' => 'Lyon', 'country' => 'FR', 'theme' => 'epure', 'headingcolor' => '',
                'linkcolor' => '', 'maincolor' => '', 'customcss' => '']);
            // Its category of courses, as IOMAD makes one for each company: with several companies, IOMAD lists them
            // by their category and warns of two companies in the same one.
            $category = \core_course_category::create(['name' => $company->name]);
            $columns = $DB->get_columns(iomad::table('company'));
            $company->{isset($columns['coursecategoryid']) ? 'coursecategoryid' : 'category'} = $category->id;
            $company->id = $DB->insert_record(iomad::table('company'), $company);
            $department = ['name' => $company->name, 'shortname' => $company->shortname];
            $department += iomad::renamed() ? ['companyid' => $company->id, 'parentid' => 0]
                : ['company' => $company->id, 'parent' => 0];
            $DB->insert_record(iomad::table('department'), (object) $department);
        }
        $this->rebuild_theme();
    }

    /**
     * Empties the caches of the theme and builds its stylesheet at once, in this process. Built by the first page, the
     * stylesheet held the web server of the tests for so long that the JavaScript of that page timed out (« not ready
     * after 10 seconds »), then the server stopped answering at all: the first brand colour of the matrix of
     * non-regression failed so, now and then, on every version of Moodle.
     */
    protected function rebuild_theme(): void {
        global $CFG;
        require_once($CFG->libdir . '/outputlib.php');
        theme_reset_all_caches();
        theme_build_css_for_themes([\theme_config::load('epure')], ['ltr']);
    }

    /**
     * Makes users members of IOMAD companies, in their top department.
     *
     * @Given /^the following IOMAD company users exist:$/
     * @param TableNode $data Columns user (username) and company (short name).
     */
    public function the_following_iomad_company_users_exist(TableNode $data): void {
        global $DB;
        foreach ($data->getHash() as $row) {
            $userid = $DB->get_field('user', 'id', ['username' => $row['user']], MUST_EXIST);
            $companyid = $this->get_companyid($row['company']);
            $field = iomad::renamed() ? 'companyid' : 'company';
            $parent = iomad::renamed() ? 'parentid' : 'parent';
            $departmentid = $DB->get_field(iomad::table('department'), 'id', [$field => $companyid, $parent => 0], MUST_EXIST);
            if (!empty($row['manager'])) {
                // A company manager, through IOMAD itself, which also gives the role of the company.
                $this->iomad_company_class()::upsert_company_user($userid, $companyid, $departmentid, 1);
                continue;
            }
            $DB->insert_record(iomad::table('company_users'), (object) ['companyid' => $companyid, 'userid' => $userid,
                'managertype' => 0, 'departmentid' => $departmentid]);
        }
    }

    /**
     * On IOMAD, puts the users who have no company in a company with the Épure theme: IOMAD sends a user without a
     * company back to the home page of the site. Nothing without IOMAD, so that the same scenarios run on both.
     *
     * @Given /^the users belong to an IOMAD company when IOMAD is installed$/
     */
    public function the_users_belong_to_an_iomad_company_when_iomad_is_installed(): void {
        global $DB;
        if (!iomad::installed()) {
            return;
        }
        if (!$DB->record_exists(iomad::table('company'), ['shortname' => 'epuretests'])) {
            $this->the_following_iomad_companies_exist(new TableNode([
                ['name', 'shortname', 'theme'],
                ['Épure tests', 'epuretests', 'epure'],
            ]));
        }
        $rows = [['user', 'company']];
        $users = $DB->get_records_select('user', "deleted = 0 AND username NOT IN ('guest', 'admin')", null, 'id', 'id, username');
        foreach ($users as $user) {
            if (!$DB->record_exists(iomad::table('company_users'), ['userid' => $user->id])) {
                $rows[] = [$user->username, 'epuretests'];
            }
        }
        if (count($rows) > 1) {
            $this->the_following_iomad_company_users_exist(new TableNode($rows));
        }
    }

    /**
     * The class of the IOMAD companies: local_iomad\company from IOMAD 5.1, company before.
     *
     * @return string
     */
    protected function iomad_company_class(): string {
        global $CFG;
        if (iomad::renamed()) {
            return '\local_iomad\company';
        }
        require_once($CFG->dirroot . '/local/iomad/lib/company.php');
        return '\company';
    }

    /**
     * The pre-audit: each page in every display (light, dark, yellow on black and cream on a computer, light on phones
     * of 390 and 320 pixels, and with the text at 200 % on 320 pixels), with the axe audit of Moodle (WCAG 2.2 A and
     * AA), the contrast under the cream overlay and, on phones, no scroll across the page (WCAG 1.4.10). All the pages
     * are checked before the step fails, with the list of what failed.
     *
     * A path can name a course by its short name, {course SAFE}, an activity by its ID number, {cm page1} (or its
     * instance, {instance forum1}), a user by their username, {user learner1}, a discussion by its name and the last
     * attempt at a quiz by the ID number of the quiz, {attempt quiz1}. The column « except » takes « wide table »:
     * a table in two dimensions, which may scroll across (WCAG 1.4.10).
     *
     * @Then /^the following pages should meet accessibility standards in every display:$/
     * @param TableNode $data Columns path and, optionally, except.
     */
    public function the_following_pages_should_meet_accessibility_standards_in_the_four_displays(TableNode $data): void {
        // Light, dark, the colours of « Aa » yellow on black and cream, on a computer; light on phones, and with the text
        // at 200 % of « Aa » on the smallest one.
        $displays = ['light 1300' => [1300, ''], 'dark 1300' => [1300, 'dark'], 'yellow 1300' => [1300, 'yellow'],
            'cream 1300' => [1300, 'cream'], 'light 390' => [390, ''], 'light 320' => [320, ''],
            'text 200 % 320' => [320, 'large']];
        $failures = [];
        foreach ($data->getHash() as $row) {
            $path = $this->epure_path($row['path']);
            foreach ($displays as $label => [$width, $scheme]) {
                $this->getSession()->resizeWindow($width, 900);
                $this->getSession()->visit($this->locate_path($path));
                $this->wait_for_pending_js();
                // The editors (TinyMCE) ready: their status bar gets its roles once the editor is drawn.
                $this->getSession()->wait(10000, "!document.querySelector('.tox-statusbar__resize-handle:not([role])')");
                if ($scheme !== '') {
                    // Without the transitions of the colours, axe reads the colours of the scheme, not those on the way
                    // to it. Yellow on black is a dark scheme.
                    $classes = ['dark' => ['epure-dark'], 'yellow' => ['epure-dark', 'epure-a11y-scheme-yellow'],
                        'cream' => ['epure-a11y-scheme-cream'], 'large' => ['epure-a11y-text-200']][$scheme];
                    $this->getSession()->executeScript(
                        "const style = document.createElement('style');" .
                        "style.textContent = '*, *::before, *::after { transition: none !important; }';" .
                        "document.head.appendChild(style);" .
                        "document.documentElement.classList.add(" . implode(', ', array_map('json_encode', $classes)) . ");"
                    );
                    $this->getSession()->wait(500);
                }
                $this->wait_for_animations();
                $this->without_deprecation_marks();
                // The cream colours are an overlay, which axe does not see: their contrast is measured under it.
                $axe = $scheme === 'cream' ? $this->cream_failure() : $this->axe_failure();
                if ($axe !== null) {
                    $failures[] = "{$row['path']} ({$label}): " . $axe . $this->pink_painter($axe);
                }
                // Yellow on black comes first, over the colours of the brand, of a course or of a plugin: every text at
                // 7:1 at least (WCAG 1.4.6), which only its own colours reach.
                // The samples of other displays are left out: the live preview of the colours and the swatches of the
                // palette show the colours of the brand in light and dark, as an image of them (at 4.5:1 above).
                $samples = ['.epure-pl-modes', '.epure-palette-swatch', '.epure-logocolours-swatch', '.epure-management-swatch'];
                if ($scheme === 'yellow' && ($axe = $this->axe_violations(false, ['color-contrast-enhanced'], $samples)) !== null) {
                    $failures[] = "{$row['path']} ({$label}, 7:1): " . $axe;
                }
                if ($width < 1000 && ($row['except'] ?? '') !== 'wide table') {
                    $script = 'return document.scrollingElement.scrollWidth - window.innerWidth;';
                    $across = $this->getSession()->evaluateScript($script);
                    if ($across > 1) {
                        // The elements past the edge, not clipped by a frame: the deepest ones, to find the cause.
                        $wide = $this->getSession()->evaluateScript(<<<'JS'
                            return (() => {
                                const edge = document.documentElement.clientWidth + 1;
                                // Inside a frame that clips it, or inside a fixed element (a drawer, a bar), an element
                                // does not make the page scroll.
                                const clipped = (element) => {
                                    for (let node = element; node; node = node.parentElement) {
                                        const style = getComputedStyle(node);
                                        if (style.position === 'fixed' || style.visibility === 'hidden') {
                                            return true;
                                        }
                                        if (node !== element && style.overflowX !== 'visible') {
                                            return node !== document.body && node !== document.documentElement;
                                        }
                                    }
                                    return false;
                                };
                                return Array.from(document.querySelectorAll('body *')).filter((element) =>
                                    element.getBoundingClientRect().right > edge && !clipped(element)
                                    && !Array.from(element.children).some((child) =>
                                        child.getBoundingClientRect().right > edge)
                                ).slice(0, 3).map((element) => {
                                    // The element and the containers around it, to find where it is.
                                    const names = [];
                                    for (let node = element; node && node !== document.body && names.length < 5;
                                            node = node.parentElement) {
                                        names.push(node.tagName.toLowerCase() + (node.id ? '#' + node.id : '')
                                            + (node.className ? '.' + String(node.className).trim().split(/\s+/)
                                                .slice(0, 2).join('.') : ''));
                                    }
                                    return names.join(' < ');
                                }).join(', ');
                            })();
                        JS);
                        $failures[] = "{$row['path']} ({$label}): the page scrolls across, by {$across} px ({$wide})";
                    }
                }
            }
        }
        $this->getSession()->resizeWindow(1366, 900);
        if ($failures) {
            throw new ExpectationException(count($failures) . " failures:\n" . implode("\n", $failures), $this->getSession());
        }
    }

    /**
     * The frames of the site in the page (the editor, H5P, the files shown) have the classes of the display preferences
     * of the page, and the style sheet that applies them (amd/src/frame_preferences.js).
     *
     * @Then /^the frames of the page should have the display preferences$/
     */
    public function the_frames_of_the_page_should_have_the_display_preferences(): void {
        $script = <<<'JS'
            return (() => {
                const wanted = Array.from(document.documentElement.classList)
                    .filter((name) => /^epure-(a11y-|dark$)/.test(name));
                const frames = Array.from(document.querySelectorAll('iframe')).filter((frame) => {
                    try {
                        return frame.contentDocument && frame.contentDocument.head && !frame.contentWindow.M;
                    } catch (e) {
                        return false;
                    }
                });
                if (!wanted.length || !frames.length) {
                    return 'no preference or no frame: ' + wanted.length + ' ' + frames.length;
                }
                return frames.filter((frame) => {
                    const doc = frame.contentDocument;
                    return !doc.getElementById('epure-frame-preferences')
                        || wanted.some((name) => !doc.documentElement.classList.contains(name));
                }).map((frame) => frame.id || frame.src || 'frame').join(', ');
            })();
        JS;
        // The editor draws its frame after the page: tried for ten seconds.
        for ($try = 0; $try < 10; $try++) {
            $missing = $this->getSession()->evaluateScript($script);
            if ($missing === '') {
                return;
            }
            $this->getSession()->wait(1000);
        }
        throw new ExpectationException('Frames without the display preferences: ' . $missing, $this->getSession());
    }

    /**
     * Waits for the animations of the page to end: axe reads the colours of a text that fades in on the way (the tiles of
     * the format Tiles appear with an animation of jQuery). The endless ones (the loading icons) are not waited for.
     */
    protected function wait_for_animations(): void {
        $this->getSession()->wait(5000, <<<'JS'
            !(window.jQuery && window.jQuery(':animated').length)
            && !document.getAnimations().some((animation) => animation.playState === 'running'
                && animation.effect && Number.isFinite(animation.effect.getComputedTiming().endTime))
        JS);
    }

    /**
     * The audit of axe (WCAG 2.2 A and AA) of the page as it is, waited for: the step of Moodle reads its results for a
     * few seconds only, and finds « No data » on the pages that take longer (the charts of the management dashboard).
     *
     * @Then /^the page should meet accessibility standards, the audit waited for$/
     */
    public function the_page_should_meet_accessibility_standards_waited_for(): void {
        $this->wait_for_pending_js();
        $this->without_deprecation_marks();
        $axe = $this->axe_failure();
        if ($axe !== null) {
            throw new ExpectationException($axe . $this->pink_painter($axe), $this->getSession());
        }
    }

    /**
     * The contrast of the texts of the page under the cream overlay of « Aa », which axe does not see: each text and its
     * background multiplied by the colour of the overlay, as the browser draws them. What already fails without it is
     * left to axe; a text over an image, or hidden for the eyes (read by screen readers only), is not measured.
     *
     * @return string|null The texts that no longer reach 4.5:1 (3:1 for a large text), or null.
     */
    protected function cream_failure(): ?string {
        $failures = $this->getSession()->evaluateScript(<<<'JS'
            return (() => {
                // The colour of the overlay (_preferences.scss), multiplied with what is under it.
                const cream = [246, 233, 201];
                const parse = (value) => {
                    const match = /rgba?\(([^)]+)\)/.exec(value || '');
                    if (!match) {
                        return null;
                    }
                    const parts = match[1].split(/[\s,/]+/).filter(Boolean).map(Number);
                    return {rgb: parts.slice(0, 3), alpha: parts.length > 3 ? parts[3] : 1};
                };
                const channel = (value) => {
                    const c = value / 255;
                    return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
                };
                const luminance = (rgb) => 0.2126 * channel(rgb[0]) + 0.7152 * channel(rgb[1]) + 0.0722 * channel(rgb[2]);
                const ratio = (a, b) => {
                    const [x, y] = [luminance(a), luminance(b)].sort((p, q) => q - p);
                    return (x + 0.05) / (y + 0.05);
                };
                const blend = (top, under) => top.rgb.map((v, i) => v * top.alpha + under[i] * (1 - top.alpha));
                const multiplied = (rgb) => rgb.map((v, i) => v * cream[i] / 255);
                // The colour behind an element: its backgrounds, from the page up to it; none known over an image.
                const background = (element) => {
                    const layers = [];
                    for (let node = element; node; node = node.parentElement) {
                        const style = getComputedStyle(node);
                        if (style.backgroundImage && style.backgroundImage !== 'none') {
                            return null;
                        }
                        const colour = parse(style.backgroundColor);
                        if (colour && colour.alpha > 0) {
                            layers.push(colour);
                            if (colour.alpha >= 1) {
                                break;
                            }
                        }
                    }
                    return layers.reverse().reduce((under, layer) => blend(layer, under), [255, 255, 255]);
                };
                const failures = [];
                const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
                const seen = new Set();
                for (let node = walker.nextNode(); node; node = walker.nextNode()) {
                    const element = node.parentElement;
                    if (!node.textContent.trim() || !element || seen.has(element)) {
                        continue;
                    }
                    seen.add(element);
                    const rect = element.getBoundingClientRect();
                    const style = getComputedStyle(element);
                    if (rect.width <= 1 || rect.height <= 1 || style.visibility !== 'visible' || Number(style.opacity) === 0
                            || element.closest('[aria-hidden="true"], script, style, noscript, .sr-only, .visually-hidden')) {
                        continue;
                    }
                    const under = background(element);
                    const colour = parse(style.color);
                    if (!under || !colour) {
                        continue;
                    }
                    const text = blend(colour, under);
                    const size = parseFloat(style.fontSize);
                    const large = size >= 24 || (size >= 18.66 && Number(style.fontWeight) >= 700);
                    const minimum = large ? 3 : 4.5;
                    // What already fails in light is axe's; here, what the overlay makes fail.
                    const after = ratio(multiplied(text), multiplied(under));
                    if (ratio(text, under) >= minimum && after < minimum - 0.005) {
                        const name = element.tagName.toLowerCase() + (element.className && typeof element.className === 'string'
                            ? '.' + element.className.trim().split(/\s+/).slice(0, 2).join('.') : '');
                        failures.push(name + ' « ' + node.textContent.trim().slice(0, 30) + ' » ' + after.toFixed(2) + ':1');
                    }
                }
                return failures.slice(0, 5).join(', ');
            })();
        JS);
        return $failures ? 'contrast under the cream overlay: ' . $failures : null;
    }

    /**
     * The audit of axe-core (WCAG 2.2 A and AA) of the page, run by the step of Moodle, with its rules.
     *
     * @return string|null What fails, null when the page meets the standards.
     */
    protected function axe_failure(): ?string {
        // The script of axe is loaded here, and waited for: on the first page after the caches are purged, the one web
        // server of the tests may answer it only once the styles are built.
        $this->getSession()->executeScript(
            "if (!document.querySelector('script[data-purpose=\"axe\"]')) {" .
            "const tag = document.createElement('script');" .
            "tag.src = M.cfg.wwwroot + '/lib/behat/axe/axe.min.js';" .
            "tag.dataset.purpose = 'axe';" .
            "document.head.append(tag);" .
            "}"
        );
        $this->getSession()->wait(30000, "typeof window.axe !== 'undefined'");
        // While the page is still settling, axe may read no results: it runs once more. Moodle's step reads the results
        // for a few seconds only: on the pages with editors (TinyMCE and its frame), axe may take longer, and the step
        // finds « No data ». The audit then runs with the same rules, in the page, waited for a minute.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $this->execute('behat_accessibility::run_axe_validation_for_tags', ['', 'wcag22aa']);
                return null;
            } catch (\Exception $e) {
                if (!preg_match('/^No data$|axe is not defined/', trim($e->getMessage()))) {
                    return trim($e->getMessage()) . $this->page_error();
                }
                $this->getSession()->wait(1000);
            }
        }
        return $this->axe_violations(true);
    }

    /**
     * The error that Moodle shows in the page, when its own step cannot read it (fewer boxes than it expects, with the
     * error of a plugin): its text, to find the cause.
     *
     * @return string The text of the error, or nothing.
     */
    protected function page_error(): string {
        $text = $this->getSession()->evaluateScript(<<<'JS'
            return (() => {
                const error = document.querySelector('[data-rel="fatalerror"]');
                return error ? [error, ...document.querySelectorAll('.alert-danger, .alert-error, .notifytiny')]
                    .map((e) => e.textContent.replace(/\s+/g, ' ').trim().slice(0, 400)).join(' | ') : '';
            })();
        JS);
        return $text ? "\n  The error in the page: " . $text : '';
    }

    /**
     * The page as on a site: on the sites of Behat, Moodle 5.x paints the classes of Bootstrap 4 it deprecates (.sr-only,
     * .ml-1, .font-italic, used by plugins) in pink (deprecated.scss), which axe reads as their background. The mark is
     * a help for developers, never shown on a site. The classes of the page are kept (Behat needs them).
     */
    protected function without_deprecation_marks(): void {
        // The marked elements only (the dotted outline and the pink of deprecated.scss): their background as on a site.
        $this->getSession()->executeScript(<<<'JS'
            document.querySelectorAll('body *').forEach((element) => {
                const style = getComputedStyle(element);
                if (style.outlineStyle === 'dotted' && style.outlineWidth === '3px'
                        && style.backgroundColor === 'rgb(249, 213, 210)') {
                    element.style.setProperty('background-color', 'transparent', 'important');
                }
            });
        JS);
    }

    /**
     * The elements that paint the pink background (#f9d5d2) that axe reads on some pages of the continuous integration
     * only, never seen on a site: their tag, classes and position, to find what draws it.
     *
     * @param string $message The message of axe.
     * @return string The elements, or nothing when the message does not name that colour.
     */
    protected function pink_painter(string $message): string {
        if (stripos($message, '#f9d5d2') === false) {
            return '';
        }
        $found = $this->getSession()->evaluateScript(<<<'JS'
            return [...document.querySelectorAll('*')].filter((e) => {
                const style = getComputedStyle(e);
                const colour = style.backgroundColor;
                const translucent = /^rgba\(\d+, \d+, \d+, 0?\.\d+\)$/.test(colour) && !colour.startsWith('rgba(0, 0, 0');
                return colour.includes('249, 213, 210') || translucent;
            }).slice(0, 8).map((e) => {
                const r = e.getBoundingClientRect();
                const classes = String(e.className).trim().split(/\s+/).slice(0, 4).join('.');
                return e.tagName.toLowerCase() + (e.id ? '#' + e.id : '') + '.' + classes
                    + ' ' + getComputedStyle(e).backgroundColor + ' ' + getComputedStyle(e).position
                    + ' [' + [r.x, r.y, r.width, r.height].map(Math.round).join(',') + ']';
            }).join('; ');
        JS);
        return "\n  The pink is painted by: " . ($found ?: 'no element (an overlay of the browser?)');
    }

    /**
     * The audit of axe-core (WCAG 2.2 A and AA) of the page, run in the page itself: the step of Moodle that runs it
     * looks for exceptions afterwards, and fails on the error pages that Moodle marks as fatal errors.
     *
     * With the rules of Moodle's step, the frames of the page (the editors) are left out: their content is the text being
     * written, and axe waits for a minute for a frame without axe.
     *
     * @param bool $moodle With the rules of Moodle's step (Section 508, ARIA, visual cues, orientation too).
     * @param string[]|null $rules These rules of axe only, instead of the tags.
     * @param string[] $exclude Selectors of the parts of the page left out.
     * @return string|null The violations, or null when there are none.
     */
    protected function axe_violations(bool $moodle = false, ?array $rules = null, array $exclude = []): ?string {
        $tags = $moodle ? ['wcag2a', 'wcag21a', 'wcag22a', 'wcag2aa', 'wcag21aa', 'wcag22aa', 'section508', 'cat.aria',
            'cat.sensory-and-visual-cues', 'wcag134'] : ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];
        $only = $rules !== null ? ['type' => 'rule', 'values' => $rules] : ['type' => 'tag', 'values' => $tags];
        $options = json_encode(['runOnly' => $only, 'iframes' => !$moodle]);
        $context = $exclude ? json_encode(['exclude' => array_map(fn($selector) => [$selector], $exclude)]) : 'document';
        $this->getSession()->executeScript(
            "window.epureAxe = null;" .
            "const run = () => axe.run(" . $context . ", " . $options . ")" .
            ".then((r) => { window.epureAxe = r.violations.map((v) => v.id + ' (' + v.nodes.length + ': '" .
            "+ v.nodes.slice(0, 5).map((n) => n.target.join(' ') + (n.any[0] && n.any[0].data && n.any[0].data.fgColor" .
            " ? ' ' + n.any[0].data.fgColor + ' on ' + n.any[0].data.bgColor : '')).join('; ') + ')').join(', '); })" .
            // Moodle's step may still be running its audit, which axe does one at a time.
            ".catch((e) => { if (/already running/i.test(e.message)) { setTimeout(run, 500); } " .
            "else { window.epureAxe = 'axe: ' + e.message; } });" .
            "if (typeof axe !== 'undefined') { run(); } else {" .
            "const tag = document.createElement('script');" .
            "tag.src = M.cfg.wwwroot + '/lib/behat/axe/axe.min.js';" .
            "tag.onload = run;" .
            "document.head.append(tag);" .
            "}"
        );
        $this->getSession()->wait(60000, 'window.epureAxe !== null');
        $violations = $this->getSession()->evaluateScript('return window.epureAxe;');
        if ($violations === null) {
            return 'axe-core: no result within a minute';
        }
        return $violations === '' ? null : 'axe-core: ' . $violations;
    }

    /**
     * The error page of a path: the card of the kind of error in the colours of the theme, with its title, its lead and
     * the message of Moodle, accessible (axe-core, WCAG 2.2 A and AA), and its link, which leads where it says.
     *
     * Moodle marks its error pages as fatal errors, which fail the step after which they are shown: the step leaves
     * them for the page of the link (or the home page), once checked.
     *
     * @Then /^the error page of "([^"]*)" should be a card "([^"]*)" with the link "([^"]*)" to "([^"]*)"$/
     * @param string $path Path, with {course SHORTNAME} and {cm IDNUMBER}.
     * @param string $kind Kind of the error: notfound, denied, unavailable, session or general.
     * @param string $link Text of the link of the card.
     * @param string $target Path the link leads to.
     */
    public function the_error_page_should_be_a_card(string $path, string $kind, string $link, string $target): void {
        $this->getSession()->visit($this->locate_path($this->epure_path($path)));
        $this->wait_for_pending_js();
        $failures = [];
        $card = $this->getSession()->getPage()->find('css', '.epure-error-card[data-kind="' . $kind . '"]');
        if (!$card) {
            $found = $this->getSession()->getPage()->find('css', '.epure-error-card');
            // Without a card, what the page shows instead, to find why.
            $shown = $this->getSession()->evaluateScript(
                "return location.pathname + location.search + ' « ' + document.title + ' » '"
                . " + (document.querySelector('#region-main, #page-content, body') || document.body).innerText"
                . ".replace(/\\s+/g, ' ').slice(0, 300);"
            );
            $failures[] = $found ? 'the card is of the kind ' . $found->getAttribute('data-kind') : 'no error card: ' . $shown;
        } else {
            foreach (['.epure-error-title' => 'error_' . $kind, '.epure-error-lead' => 'error_' . $kind . '_lead'] as $css => $id) {
                $element = $card->find('css', $css);
                if (!$element || trim($element->getText()) !== get_string($id, 'theme_epure')) {
                    $failures[] = "{$css} is not « " . get_string($id, 'theme_epure') . ' »';
                }
            }
            $details = $card->find('css', '.epure-error-details');
            if (!$details || trim($details->getText()) === '') {
                $failures[] = 'the message of Moodle is missing';
            }
            if (($axe = $this->axe_violations()) !== null) {
                $failures[] = $axe;
            }
        }
        $anchor = $card ? $card->find('named_exact', ['link', $link]) : null;
        if (!$anchor) {
            $failures[] = "no link « {$link} » in the card";
            $this->getSession()->visit($this->locate_path('/'));
        } else {
            $anchor->click();
            $this->wait_for_pending_js();
            $reached = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
            $expected = parse_url($this->locate_path($target), PHP_URL_PATH);
            if (rtrim((string) $reached, '/') !== rtrim((string) $expected, '/')) {
                $failures[] = "the link « {$link} » leads to {$reached}, not {$expected}";
                $this->getSession()->visit($this->locate_path('/'));
            }
        }
        if ($failures) {
            throw new ExpectationException("Error page of {$path}: " . implode('; ', $failures), $this->getSession());
        }
    }

    /**
     * A « My summary » document of a user, issued under a known code.
     *
     * @Given /^the user "([^"]*)" has a summary document with the code "([^"]*)"$/
     * @param string $username User.
     * @param string $code Code.
     */
    public function the_user_has_a_summary_document(string $username, string $code): void {
        global $DB, $PAGE;
        $userid = (int) $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
        \theme_epure\learner_summary_document::issue(
            $PAGE->get_renderer('core'),
            $userid,
            \theme_epure\learner_summary_document::normalise($code)
        );
    }

    /**
     * Brings an element to the middle of the screen, out from under the bars that stick to the bottom of a form (the
     * button to save of the guide « Accessibility of the course »), so that a click reaches it as a person scrolling
     * to it would.
     *
     * @When /^I bring "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" to the middle of the screen$/
     * @param string $element Element.
     * @param string $selector Type of selector.
     */
    public function i_bring_to_the_middle_of_the_screen(string $element, string $selector): void {
        $node = $this->get_selected_node($selector, $element);
        $this->execute_js_on_node($node, '{{ELEMENT}}.scrollIntoView({block: "center"});');
    }

    /**
     * The page is not in the dark colour mode of Boost (Moodle 5.3, theme_boost/enablecolourmodes): Bootstrap stays
     * light on a page of Épure, whose own scheme decides.
     *
     * @Then /^the page should not be in the dark colour mode of Boost$/
     */
    public function the_page_should_not_be_in_the_dark_colour_mode_of_boost(): void {
        $mode = $this->getSession()->evaluateScript("return document.documentElement.getAttribute('data-bs-theme');");
        if ($mode === 'dark') {
            throw new ExpectationException(
                'The page is in the dark colour mode of Boost (data-bs-theme="dark").',
                $this->getSession()
            );
        }
    }

    /**
     * Visual regression: each page, in its display, compared with its reference image (tests/behat/visual/NAME.png).
     * Only where the environment variable EPURE_VISUAL names a folder (one job of the continuous integration): the
     * fonts and the browser draw the pixels, which differ from a machine to another. Without its reference, the image
     * of a page is saved in EPURE_VISUAL/new, to be added to the references; when it differs, the image and its
     * differences (in red) are saved in EPURE_VISUAL/diff, and the step fails.
     *
     * The animations, the transitions and the caret are stopped; what changes from a run to another is hidden
     * (VISUAL_MASK: dates and times, the year of the footer, the images generated by Moodle for the courses, whose
     * pattern follows the id of the course), with the elements of the column « mask » (CSS selectors).
     *
     * @Then /^the following pages should look like their references:$/
     * @param TableNode $data Columns name, path, display (light 1300, dark 1300, light 390) and, optionally, mask.
     */
    public function the_following_pages_should_look_like_their_references(TableNode $data): void {
        $folder = getenv('EPURE_VISUAL');
        if (!$folder) {
            throw new SkippedException('The visual regression runs where EPURE_VISUAL names a folder.');
        }
        $failures = [];
        foreach ($data->getHash() as $row) {
            [$scheme, $width] = explode(' ', $row['display']);
            $this->getSession()->resizeWindow((int) $width, 900);
            $this->getSession()->visit($this->locate_path($this->epure_path($row['path'])));
            $this->wait_for_pending_js();
            $mask = implode(', ', array_filter(array_merge(self::VISUAL_MASK, [trim($row['mask'] ?? '')])));
            $this->getSession()->executeScript(
                "const style = document.createElement('style');" .
                "style.textContent = '*, *::before, *::after { transition: none !important; animation: none !important;" .
                " caret-color: transparent !important; scroll-behavior: auto !important; }" .
                " " . addslashes($mask) . " { visibility: hidden !important; }';" .
                "document.head.appendChild(style);" .
                ($scheme === 'dark' ? "document.documentElement.classList.add('epure-dark');" : '') .
                "document.activeElement && document.activeElement.blur && document.activeElement.blur();" .
                "window.scrollTo(0, 0);"
            );
            $this->getSession()->wait(500);
            $image = $this->getSession()->getScreenshot();
            $name = clean_param($row['name'], PARAM_ALPHANUMEXT);
            $reference = __DIR__ . '/visual/' . $name . '.png';
            if (!is_readable($reference)) {
                $this->epure_save_image($folder . '/new', $name . '.png', $image);
                continue;
            }
            $difference = $this->epure_compare_images(file_get_contents($reference), $image);
            if ($difference !== null) {
                $this->epure_save_image($folder . '/diff', $name . '.png', $image);
                if ($difference['image'] !== null) {
                    $this->epure_save_image($folder . '/diff', $name . '-diff.png', $difference['image']);
                }
                $failures[] = "{$row['name']} ({$row['path']}, {$row['display']}): {$difference['message']}";
            }
        }
        if ($failures) {
            throw new ExpectationException(
                "The pages differ from their references:\n" . implode("\n", $failures),
                $this->getSession()
            );
        }
    }

    /**
     * Saves an image of the visual regression.
     *
     * @param string $folder Folder.
     * @param string $file Name of the file.
     * @param string $image Image (PNG).
     */
    protected function epure_save_image(string $folder, string $file, string $image): void {
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        file_put_contents($folder . '/' . $file, $image);
    }

    /**
     * Compares an image with its reference: the pixels whose colour differs by more than 48 (out of 255) on one channel
     * at least, beyond 0.02 % of the pixels (the edges of the letters, drawn a little differently from a run to another).
     *
     * @param string $reference Reference image (PNG).
     * @param string $image Image of the page (PNG).
     * @return array|null Null when they are alike; else the message and an image of the differences in red.
     */
    protected function epure_compare_images(string $reference, string $image): ?array {
        $expected = imagecreatefromstring($reference);
        $actual = imagecreatefromstring($image);
        $width = imagesx($actual);
        $height = imagesy($actual);
        if (imagesx($expected) !== $width || imagesy($expected) !== $height) {
            return ['message' => 'the size is ' . $width . '×' . $height . ', not ' . imagesx($expected) . '×'
                . imagesy($expected), 'image' => null];
        }
        $diff = imagecreatetruecolor($width, $height);
        imagecopy($diff, $actual, 0, 0, 0, 0, $width, $height);
        imagefilter($diff, IMG_FILTER_GRAYSCALE);
        imagefilter($diff, IMG_FILTER_BRIGHTNESS, 80);
        $red = imagecolorallocate($diff, 230, 0, 0);
        $count = 0;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $a = imagecolorat($expected, $x, $y);
                $b = imagecolorat($actual, $x, $y);
                if ($a === $b) {
                    continue;
                }
                if (
                    abs((($a >> 16) & 255) - (($b >> 16) & 255)) > 48 || abs((($a >> 8) & 255) - (($b >> 8) & 255)) > 48
                        || abs(($a & 255) - ($b & 255)) > 48
                ) {
                    $count++;
                    imagesetpixel($diff, $x, $y, $red);
                }
            }
        }
        if ($count <= $width * $height * 0.0002) {
            return null;
        }
        ob_start();
        imagepng($diff);
        return ['message' => $count . ' pixels differ', 'image' => ob_get_clean()];
    }

    /**
     * A path of the pre-audit, its courses, activities, users, discussions and attempts replaced by their ids.
     *
     * @param string $path Path with {course SHORTNAME}, {cm IDNUMBER}, {instance IDNUMBER} (the instance of the
     *     activity), {user USERNAME}, {discussion NAME} and {attempt IDNUMBER} (the last attempt at the quiz).
     * @return string
     */
    protected function epure_path(string $path): string {
        global $DB;
        $tokens = '/\{(course|cm|instance|user|discussion|attempt) ([^}]+)\}/';
        return preg_replace_callback($tokens, function (array $m) use ($DB): string {
            switch ($m[1]) {
                case 'course':
                    return (string) $DB->get_field('course', 'id', ['shortname' => $m[2]], MUST_EXIST);
                case 'cm':
                    return (string) $DB->get_field('course_modules', 'id', ['idnumber' => $m[2]], MUST_EXIST);
                case 'instance':
                    return (string) $DB->get_field('course_modules', 'instance', ['idnumber' => $m[2]], MUST_EXIST);
                case 'user':
                    return (string) $DB->get_field('user', 'id', ['username' => $m[2]], MUST_EXIST);
                case 'discussion':
                    return (string) $DB->get_field('forum_discussions', 'id', ['name' => $m[2]], MUST_EXIST);
                default:
                    // The last attempt at the quiz of this ID number.
                    $quiz = $DB->get_field('course_modules', 'instance', ['idnumber' => $m[2]], MUST_EXIST);
                    return (string) $DB->get_field_sql('SELECT MAX(id) FROM {quiz_attempts} WHERE quiz = ?', [$quiz]);
            }
        }, $path);
    }

    /**
     * The template of certificate of Épure, as an administrator makes it (« Certificate — Acceptance test site »).
     * Skips the scenario without the activity Course certificate (mod_coursecertificate) and its plugin Certificate.
     *
     * @Given /^the certificate template of Épure exists$/
     */
    public function the_certificate_template_of_epure_exists(): void {
        if (
            !\theme_epure\certificate_template::available()
                || \core_component::get_component_directory('mod_coursecertificate') === null
        ) {
            throw new SkippedException('The activity Course certificate (mod_coursecertificate) is not installed.');
        }
        \core\session\manager::set_user(get_admin());
        \theme_epure\certificate_template::create();
    }

    /**
     * The brand colour of the site, its styles built again.
     *
     * @Given /^the brand colour of the site is "(?P<colour>#[0-9A-Fa-f]{6})"$/
     * @param string $colour Colour.
     */
    public function the_brand_colour_of_the_site_is(string $colour): void {
        set_config('brandcolor', $colour, 'theme_epure');
        $this->rebuild_theme();
    }

    /**
     * Each element reached with the keyboard (the first 60 of the page), once focused, is not hidden under a bar fixed at
     * the top or the bottom of the screen (WCAG 2.4.11), on a computer (1300 pixels) and on a phone (390 pixels). The
     * element is focused as with the key Tab, then given the time of the theme to move it clear of the bars.
     *
     * @Then /^the elements focused with the keyboard should not be hidden under a fixed bar on the pages:$/
     * @param TableNode $data Column path.
     */
    public function the_elements_focused_should_not_be_hidden_under_a_fixed_bar(TableNode $data): void {
        $list = <<<'JS'
            window.epureFocusables = Array.from(document.querySelectorAll(
                'a[href], button, input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])'
            )).filter((element) => !element.disabled && element.getClientRects().length > 0
                && getComputedStyle(element).visibility !== 'hidden').slice(0, 60);
            return window.epureFocusables.length;
        JS;
        $failures = [];
        foreach ($data->getHash() as $row) {
            $path = $this->epure_path($row['path']);
            foreach ([1300, 390] as $width) {
                $this->getSession()->resizeWindow($width, 900);
                $this->getSession()->visit($this->locate_path($path));
                $this->wait_for_pending_js();
                $count = (int) $this->getSession()->evaluateScript($list);
                for ($i = 0; $i < $count; $i++) {
                    $this->getSession()->executeScript("window.epureFocusables[{$i}].focus();");
                    $this->getSession()->wait(150);
                    $hidden = $this->getSession()->evaluateScript(<<<JS
                        return (() => {
                            const element = window.epureFocusables[{$i}];
                            if (document.activeElement !== element) {
                                return '';
                            }
                            const rect = element.getBoundingClientRect();
                            const x = Math.min(Math.max(rect.left + rect.width / 2, 0), window.innerWidth - 1);
                            const y = rect.top + rect.height / 2;
                            if (!rect.width || !rect.height || y < 0 || y >= window.innerHeight) {
                                return '';
                            }
                            const top = document.elementFromPoint(x, y);
                            if (!top || element.contains(top) || top.contains(element)) {
                                return '';
                            }
                            for (let node = top; node && node !== document.body; node = node.parentElement) {
                                const position = getComputedStyle(node).position;
                                if ((position === 'fixed' || position === 'sticky') && !node.contains(element)) {
                                    const name = (element.textContent || element.getAttribute('aria-label')
                                        || element.name || element.id || element.tagName).trim().slice(0, 40);
                                    return name + ' under ' + node.tagName.toLowerCase()
                                        + (node.className ? '.' + String(node.className).trim().split(/\s+/)[0] : '');
                                }
                            }
                            return '';
                        })();
                    JS);
                    if ($hidden) {
                        $failures[] = "{$row['path']} ({$width} px): {$hidden}";
                    }
                }
            }
        }
        $this->getSession()->resizeWindow(1366, 900);
        if ($failures) {
            throw new ExpectationException(count($failures) . " failures:\n" . implode("\n", $failures), $this->getSession());
        }
    }

    /**
     * The last access of a user to a course, some days ago, and the activities of the course added since: as read by
     * Moodle at the next login of the user (theme_epure\new_activities).
     *
     * @Given /^"(?P<username>[^"]*)" last visited the course "(?P<shortname>[^"]*)" (?P<days>\d+) days ago$/
     * @param string $username User.
     * @param string $shortname Course.
     * @param int $days Days.
     */
    public function last_visited_the_course_days_ago(string $username, string $shortname, int $days): void {
        global $DB;
        $userid = (int) $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
        $courseid = (int) $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
        $time = time() - $days * DAYSECS;
        $record = $DB->get_record('user_lastaccess', ['userid' => $userid, 'courseid' => $courseid]);
        if ($record) {
            $DB->set_field('user_lastaccess', 'timeaccess', $time, ['id' => $record->id]);
        } else {
            $DB->insert_record('user_lastaccess', (object) ['userid' => $userid, 'courseid' => $courseid, 'timeaccess' => $time]);
        }
    }

    /**
     * Sets when an activity was added to its course, some days ago (theme_epure\new_activities).
     *
     * @Given /^the activity "(?P<idnumber>[^"]*)" was added (?P<days>\d+) days ago$/
     * @param string $idnumber Id number of the activity.
     * @param int $days Days.
     */
    public function the_activity_was_added_days_ago(string $idnumber, int $days): void {
        global $DB;
        $cm = $DB->get_record('course_modules', ['idnumber' => $idnumber], 'id, course', MUST_EXIST);
        $DB->set_field('course_modules', 'added', time() - $days * DAYSECS, ['id' => $cm->id]);
        rebuild_course_cache($cm->course, true);
    }

    /**
     * Gives an IOMAD company a logo, as IOMAD stores it (core_admin, logo<id>).
     *
     * @Given /^the IOMAD company "(?P<shortname>[^"]*)" has the logo "(?P<path>[^"]*)"$/
     * @param string $shortname Short name of the company.
     * @param string $path File, from the root of Moodle.
     */
    public function the_iomad_company_has_the_logo(string $shortname, string $path): void {
        global $CFG;
        $companyid = $this->get_companyid($shortname);
        $filename = basename($path);
        get_file_storage()->create_file_from_pathname([
            'contextid' => context_system::instance()->id,
            'component' => 'core_admin',
            'filearea' => 'logo' . $companyid,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], $CFG->dirroot . '/' . $path);
        set_config('logo' . $companyid, '/' . $filename, 'core_admin');
        $this->rebuild_theme();
    }

    /**
     * Checks the brand colour of the page: the colour the theme derives from a colour, for its contrast.
     *
     * @Then /^the brand colour of the page should come from "(?P<colour>#[0-9A-Fa-f]{6})"$/
     * @param string $colour Brand colour set.
     */
    public function the_brand_colour_of_the_page_should_come_from(string $colour): void {
        $expected = \theme_epure\palette::derive($colour)['fill'];
        $actual = $this->brand_colour();
        if ($actual !== strtoupper($expected)) {
            throw new ExpectationException("The brand colour of the page is $actual, not $expected.", $this->getSession());
        }
    }

    /**
     * Checks that the brand colour of the page is not the one the theme derives from a colour.
     *
     * @Then /^the brand colour of the page should not come from "(?P<colour>#[0-9A-Fa-f]{6})"$/
     * @param string $colour Brand colour.
     */
    public function the_brand_colour_of_the_page_should_not_come_from(string $colour): void {
        $colour = strtoupper(\theme_epure\palette::derive($colour)['fill']);
        if ($this->brand_colour() === $colour) {
            throw new ExpectationException("The brand colour of the page is $colour.", $this->getSession());
        }
    }

    /**
     * Brand colour of the page, as the custom property --epure-brand.
     *
     * @return string
     */
    protected function brand_colour(): string {
        return (string) $this->evaluate_script(
            'return getComputedStyle(document.documentElement).getPropertyValue("--epure-brand").trim().toUpperCase();'
        );
    }

    /**
     * Checks that the page shows the logo of an IOMAD company.
     *
     * @Then /^the logo of the IOMAD company "(?P<shortname>[^"]*)" should be shown$/
     * @param string $shortname Short name of the company.
     */
    public function the_logo_of_the_iomad_company_should_be_shown(string $shortname): void {
        $area = '/core_admin/logo' . $this->get_companyid($shortname) . '/';
        $this->execute('behat_general::should_exist', ["img[src*='$area']", 'css_element']);
    }

    /**
     * Identifier of an IOMAD company.
     *
     * @param string $shortname Short name.
     * @return int
     */
    protected function get_companyid(string $shortname): int {
        global $DB;
        return (int) $DB->get_field(iomad::table('company'), 'id', ['shortname' => $shortname], MUST_EXIST);
    }
    /**
     * The state told by the bar of the buttons to save of a long form, with what it shows and the fields changed since
     * the page was loaded when it is not the one expected.
     *
     * @Then /^the long form should tell "(?P<state>none|unsaved|errors)"$/
     * @param string $state Expected state.
     */
    public function the_long_form_should_tell(string $state): void {
        $script = <<<'JS'
            return (function() {
                const status = document.querySelector('.epure-longform-status');
                const form = status && status.closest('form');
                const now = form ? [...new FormData(form).entries()].filter(([, value]) => typeof value === 'string')
                    .map(([name, value]) => name + '=' + value) : [];
                const before = window.epureLongFormLoaded || [];
                return JSON.stringify({
                    state: status ? status.dataset.state : null,
                    text: status ? status.textContent : null,
                    visible: status ? Boolean(status.offsetParent) : false,
                    added: now.filter((item) => !before.includes(item)),
                    removed: before.filter((item) => !now.includes(item)),
                });
            })();
            JS;
        // The state follows the form within two seconds.
        for ($try = 0; $try < 20; $try++) {
            $result = json_decode($this->evaluate_script($script), true);
            if (($result['state'] ?? null) === $state && !empty($result['visible'])) {
                return;
            }
            usleep(250000);
        }
        throw new ExpectationException('The long form tells: ' . json_encode($result), $this->getSession());
    }

    /**
     * Keeps the values of the long form of the page, to tell later which fields changed.
     *
     * @Given /^I keep the values of the long form$/
     */
    public function i_keep_the_values_of_the_long_form(): void {
        $this->execute_script('window.epureLongFormLoaded = [...new FormData(document.querySelector(' .
            '".epure-longform-actions").closest("form")).entries()].filter(([, value]) => typeof value === "string")' .
            '.map(([name, value]) => name + "=" + value);');
    }

    /**
     * Skips the scenario when a course format is not installed: the course formats of other plugins are added to some
     * jobs of the continuous integration only, those of the versions of Moodle they support.
     *
     * @Given /^the course format "(?P<format_string>(?:[^"]|\\")*)" is installed$/
     * @param string $format Name of the format, without format_.
     */
    public function the_course_format_is_installed(string $format): void {
        if (!\core_component::get_component_directory('format_' . $format)) {
            throw new \Moodle\BehatExtension\Exception\SkippedException('The course format ' . $format . ' is not installed.');
        }
    }

    /**
     * Skips the scenario when an activity module is not installed: the activities of other plugins are added to some jobs
     * of the continuous integration only.
     *
     * @Given /^the activity module "(?P<modname_string>(?:[^"]|\\")*)" is installed$/
     * @param string $modname Name of the module, without mod_.
     */
    public function the_activity_module_is_installed(string $modname): void {
        if (!\core_component::get_component_directory('mod_' . $modname)) {
            throw new \Moodle\BehatExtension\Exception\SkippedException('The activity module ' . $modname . ' is not installed.');
        }
    }

    /**
     * Opens a page by its path, which can name a course, an activity or a user ({@see self::epure_path()}).
     *
     * @When /^I visit the Épure page "(?P<path_string>(?:[^"]|\\")*)"$/
     * @param string $path Path, with {course SHORTNAME} and {cm IDNUMBER}.
     */
    public function i_visit_the_epure_page(string $path): void {
        $this->getSession()->visit($this->locate_path($this->epure_path($path)));
        $this->wait_for_pending_js();
    }

    /**
     * The description of an activity gets an image without a text alternative, for the guide « Accessibility of the
     * course » to find (after the page of the activity is checked: the defect is the teacher's, not the theme's).
     *
     * @Given /^the description of the activity "(?P<idnumber>[^"]*)" has an image without a text alternative$/
     * @param string $idnumber ID number of the activity.
     */
    public function the_description_has_an_image_without_a_text_alternative(string $idnumber): void {
        global $DB;
        $cm = $DB->get_record('course_modules', ['idnumber' => $idnumber], '*', MUST_EXIST);
        $modname = $DB->get_field('modules', 'name', ['id' => $cm->module], MUST_EXIST);
        // Its description, in the column the guide reads (theme_epure\content_sources): intro, else none to change.
        if (array_key_exists('intro', $DB->get_columns($modname))) {
            $DB->set_field($modname, 'intro', '<p><img src="https://example.com/gloves.png"></p>', ['id' => $cm->instance]);
        }
        rebuild_course_cache($cm->course, true);
    }

    /**
     * Creates an activity of a module of another plugin, with a short description: by its generator when it has one,
     * else as Moodle stores any activity (its record, its module of the course in the
     * section 1), the columns required by its table that the step does not know left empty.
     *
     * @Given /^the activity "(?P<mod>[a-z0-9_]+)" "(?P<name>[^"]*)" exists in "(?P<course>[^"]*)" as "(?P<id>[^"]*)"$/
     * @param string $mod Name of the module, without mod_.
     * @param string $name Name of the activity.
     * @param string $course Short name of the course.
     * @param string $id ID number of the activity.
     */
    public function the_activity_exists_in_the_course(string $mod, string $name, string $course, string $id): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        $courserecord = $DB->get_record('course', ['shortname' => $course], '*', MUST_EXIST);
        $intro = '<p>Read the instructions before you start.</p>';
        $fields = ['course' => $courserecord->id, 'name' => $name, 'intro' => $intro, 'introformat' => FORMAT_HTML,
            'idnumber' => $id, 'showdescription' => 1, 'section' => 1];
        if (
            class_exists('mod_' . $mod . '_generator')
                || file_exists(\core_component::get_component_directory('mod_' . $mod) . '/tests/generator/lib.php')
        ) {
            // Some generators call functions of the module without loading them (GeoGebra).
            $dir = \core_component::get_component_directory('mod_' . $mod);
            foreach (['lib.php', 'locallib.php'] as $file) {
                if (file_exists($dir . '/' . $file)) {
                    require_once($dir . '/' . $file);
                }
            }
            // The fields some generators need and do not fill in (Offline Quiz).
            $fields += ['offlinequiz' => ['pdfintro' => '', 'timeopen' => 0, 'timeclose' => 0]][$mod] ?? [];
            $created = \testing_util::get_data_generator()->create_module($mod, $fields);
            // The ID number, which some generators leave out (Scheduler).
            if (!empty($created->cmid)) {
                $DB->set_field('course_modules', 'idnumber', $id, ['id' => $created->cmid]);
            }
            return;
        }
        // Without a generator: the record of the activity, the columns it requires empty or zero.
        $record = ['course' => $courserecord->id, 'name' => $name, 'intro' => $intro, 'introformat' => FORMAT_HTML,
            'timemodified' => time()];
        foreach ($DB->get_columns($mod) as $column) {
            if ($column->name === 'id' || array_key_exists($column->name, $record)) {
                continue;
            }
            if ($column->not_null && !$column->has_default) {
                $record[$column->name] = in_array($column->meta_type, ['I', 'N', 'F'], true) ? 0 : '';
            }
        }
        $record = array_intersect_key($record, $DB->get_columns($mod));
        $instance = $DB->insert_record($mod, (object) $record);
        $cm = (object) ['course' => $courserecord->id, 'module' => $DB->get_field('modules', 'id', ['name' => $mod], MUST_EXIST),
            'instance' => $instance, 'section' => 1, 'idnumber' => $id, 'visible' => 1, 'showdescription' => 1];
        $cm->id = add_course_module($cm);
        course_add_cm_to_section($courserecord, $cm->id, 1);
        rebuild_course_cache($courserecord->id, true);
    }
}
