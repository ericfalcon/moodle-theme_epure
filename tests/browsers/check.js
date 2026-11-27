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

/**
 * The pages of a learner in the browsers that Behat does not drive: WebKit (Safari, on a computer and on an iPhone), and
 * Chromium with the forced colours of the Windows contrast themes. For each page: no error of JavaScript, no violation of
 * axe (WCAG 2.2 A and AA), nothing wider than the screen; an image of each page in EPURE_BROWSERS.
 *
 * Run by the continuous integration (.github/workflows/ci.yml), after tests/browsers/setup.php:
 * node check.js <wwwroot> <course id>, with playwright and axe-core in NODE_PATH.
 *
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable no-console */
const {webkit, chromium, devices} = require('playwright');
const fs = require('fs');
const path = require('path');

const [wwwroot, courseid] = process.argv.slice(2);
const out = process.env.EPURE_BROWSERS;
const axe = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
fs.mkdirSync(out, {recursive: true});

const runs = [
    {name: 'safari', type: webkit, options: {viewport: {width: 1280, height: 800}}},
    {name: 'iphone', type: webkit, options: {...devices['iPhone SE']}},
    {name: 'forced', type: chromium, options: {viewport: {width: 1280, height: 800}, forcedColors: 'active'}},
];
const pages = [
    ['dashboard', '/my/'],
    ['course', '/course/view.php?id=' + courseid],
    ['mycourses', '/my/courses.php'],
    ['aa', '/my/', 'panel'],
];
const failures = [];

(async() => {
    for (const run of runs) {
        const browser = await run.type.launch();
        const context = await browser.newContext(run.options);
        const page = await context.newPage();
        page.setDefaultTimeout(60000);
        const errors = [];
        // The requests that the browser stops when the page goes to another one (WebKit says « due to access control
        // checks »): not an error of the page.
        page.on('pageerror', (error) => {
            if (!/due to access control checks/.test(error.message)) {
                errors.push(error.message);
            }
        });

        await page.goto(wwwroot + '/login/index.php');
        if (!await page.locator('#username').count()) {
            // Not the login page: what the site shows instead, for the log.
            console.log(`${run.name}: no login form at ${page.url()} (${await page.title()}):`,
                (await page.locator('body').innerText().catch(() => '')).slice(0, 800));
        }
        await page.fill('#username', 'learner');
        await page.fill('#password', 'Browsers-2026!');
        await Promise.all([page.waitForNavigation(), page.click('#loginbtn')]);

        for (const [name, url, panel] of pages) {
            const where = `${run.name} ${name}`;
            errors.length = 0;
            await page.goto(wwwroot + url, {waitUntil: 'networkidle'});
            if (!await page.locator('link[href*="/theme/styles.php/epure/"]').count()) {
                failures.push(`${where}: the page is not in Épure`);
            }
            if (panel) {
                const toggle = page.locator('.epure-a11y-toggle').first();
                await toggle.click();
                await page.locator('#epure-a11y-panel').waitFor({state: 'visible'});
            }
            await page.waitForTimeout(500);
            await page.screenshot({path: path.join(out, `${run.name}-${name}.png`), fullPage: !panel});

            const wide = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
            if (wide > 1) {
                failures.push(`${where}: the page is ${wide} px wider than the screen`);
            }
            await page.addScriptTag({content: axe});
            const violations = await page.evaluate(async() => (await window.axe.run(document, {
                runOnly: {type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']},
            })).violations.map((v) => `${v.id} (${v.nodes.length}): ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(', ')}`));
            violations.forEach((v) => failures.push(`${where}: axe ${v}`));
            errors.forEach((e) => failures.push(`${where}: JavaScript error: ${e}`));
            console.log(`${where}: ${violations.length} violation(s) of axe, ${errors.length} error(s), ${Math.max(wide, 0)} px too wide`);
        }
        await browser.close();
    }
    if (failures.length) {
        console.log('\n' + failures.join('\n'));
        process.exit(1);
    }
})().catch((error) => {
    console.log(error);
    process.exit(1);
});
