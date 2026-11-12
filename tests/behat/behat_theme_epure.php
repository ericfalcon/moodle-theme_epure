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
     * The pre-audit: each page in the four displays (light and dark on a computer, light on phones of 390 and 320
     * pixels), with the axe audit of Moodle (WCAG 2.2 A and AA) and, on phones, no scroll across the page (WCAG
     * 1.4.10). All the pages are checked before the step fails, with the list of what failed.
     *
     * A path can name a course by its short name, {course SAFE}, an activity by its ID number, {cm page1} (or its
     * instance, {instance forum1}), a user by their username, {user learner1}, a discussion by its name and the last
     * attempt at a quiz by the ID number of the quiz, {attempt quiz1}. The column « except » takes « wide table »:
     * a table in two dimensions, which may scroll across (WCAG 1.4.10).
     *
     * @Then /^the following pages should meet accessibility standards in the four displays:$/
     * @param TableNode $data Columns path and, optionally, except.
     */
    public function the_following_pages_should_meet_accessibility_standards_in_the_four_displays(TableNode $data): void {
        $displays = ['light 1300' => [1300, false], 'dark 1300' => [1300, true], 'light 390' => [390, false],
            'light 320' => [320, false]];
        $failures = [];
        foreach ($data->getHash() as $row) {
            $path = $this->epure_path($row['path']);
            foreach ($displays as $label => [$width, $dark]) {
                $this->getSession()->resizeWindow($width, 900);
                $this->getSession()->visit($this->locate_path($path));
                $this->wait_for_pending_js();
                if ($dark) {
                    // Without the transitions of the colours, axe reads the colours of the dark mode, not those on
                    // the way to it.
                    $this->getSession()->executeScript(
                        "const style = document.createElement('style');" .
                        "style.textContent = '*, *::before, *::after { transition: none !important; }';" .
                        "document.head.appendChild(style);" .
                        "document.documentElement.classList.add('epure-dark');"
                    );
                    $this->getSession()->wait(500);
                }
                if (($axe = $this->axe_failure()) !== null) {
                    $failures[] = "{$row['path']} ({$label}): " . $axe;
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
     * The audit of axe-core (WCAG 2.2 A and AA) of the page.
     *
     * @return string|null The violations, or null when there are none.
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
        // While the page is still settling, axe may read no results: it runs once more.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $this->execute('behat_accessibility::run_axe_validation_for_tags', ['', 'wcag22aa']);
                return null;
            } catch (\Exception $e) {
                if ($attempt === 1 && preg_match('/^No data$|axe is not defined/', trim($e->getMessage()))) {
                    $this->getSession()->wait(1000);
                    continue;
                }
                return trim($e->getMessage());
            }
        }
        return null;
    }

    /**
     * The audit of axe-core (WCAG 2.2 A and AA) of the page, run in the page itself: the step of Moodle that runs it
     * looks for exceptions afterwards, and fails on the error pages that Moodle marks as fatal errors.
     *
     * @return string|null The violations, or null when there are none.
     */
    protected function axe_violations(): ?string {
        $this->getSession()->executeScript(
            "window.epureAxe = null;" .
            "const run = () => axe.run(document, {runOnly: {type: 'tag', values: " .
            "['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']}})" .
            ".then((r) => { window.epureAxe = r.violations.map((v) => v.id + ' (' + v.nodes.length + ')').join(', '); })" .
            ".catch((e) => { window.epureAxe = 'axe: ' + e.message; });" .
            "if (typeof axe !== 'undefined') { run(); } else {" .
            "const tag = document.createElement('script');" .
            "tag.src = M.cfg.wwwroot + '/lib/behat/axe/axe.min.js';" .
            "tag.onload = run;" .
            "document.head.append(tag);" .
            "}"
        );
        $this->getSession()->wait(30000, 'window.epureAxe !== null');
        $violations = (string) $this->getSession()->evaluateScript('return window.epureAxe;');
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
     * Visual regression: each page, in its display, compared with its reference image (tests/behat/visual/NAME.png).
     * Only where the environment variable EPURE_VISUAL names a folder (one job of the continuous integration): the
     * fonts and the browser draw the pixels, which differ from a machine to another. Without its reference, the image
     * of a page is saved in EPURE_VISUAL/new, to be added to the references; when it differs, the image and its
     * differences (in red) are saved in EPURE_VISUAL/diff, and the step fails.
     *
     * The animations, the transitions and the caret are stopped; the dates (elements time), the year of the footer and
     * the elements of the column « mask » (CSS selectors) are hidden, as content that changes from a day to another.
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
            $mask = implode(', ', array_filter(['time', '.epure-footer-copyright', trim($row['mask'] ?? '')]));
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
}
