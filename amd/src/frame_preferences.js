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
 * The display preferences of « Aa » in the frames of the page that the styles of the theme do not reach: the text being
 * written in an editor (TinyMCE), the contents of H5P, the packages of SCORM and IMS, the files shown in the page. Each
 * frame of the site (the same origin) takes the classes of the preferences and a style sheet that applies them: the
 * size of the text, the reading font, the spacing, the underlined links, no motion, the focus, the colour schemes
 * (yellow on black over every colour of the content, the dark scheme by inversion, the images kept); the cream
 * overlay of the page already covers them. The reading guide follows the pointer over them. The frames of other sites
 * are out of reach of any page.
 *
 * @module     theme_epure/frame_preferences
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @type {RegExp} The classes of the html element that carry the preferences. */
const PREFERENCE = /^epure-(a11y-|dark$)/;

/** @type {string} Id of the style sheet put in the frames. */
const STYLE = 'epure-frame-preferences';

/** @type {number[]} Sizes of the text, in percent, besides 100. */
const SIZES = [90, 115, 130, 150, 175, 200];

/** @type {Object<string, string>} The reading fonts and their family. */
const FONTS = {atkinson: '"Atkinson Hyperlegible"', lexend: '"Lexend"', opendyslexic: '"OpenDyslexic"'};

/** @type {string} What is not inverted with the dark scheme: the images, the videos, the drawings. */
const MEDIA = ':is(img, video, canvas, picture, iframe, embed, object, [style*="background-image"])';

/**
 * The style sheet of the preferences in a frame.
 *
 * @returns {string}
 */
const rules = () => [
    ...SIZES.map((size) => `html.epure-a11y-text-${size} body { zoom: ${size / 100}; }`),
    // The icons of the contents (fonts of icons) keep their font: the elements i and those named icon are left out.
    ...Object.entries(FONTS).map(([name, family]) => `html.epure-a11y-font-${name} body :not(i, [class*="icon"]),`
        + ` html.epure-a11y-font-${name} body { font-family: ${family}, system-ui, sans-serif !important; }`),
    'html.epure-a11y-spacing body * { line-height: 1.5 !important; letter-spacing: .12em !important;'
        + ' word-spacing: .16em !important; }',
    'html.epure-a11y-underline a { text-decoration: underline !important; text-underline-offset: .15em; }',
    'html.epure-a11y-motion *, html.epure-a11y-motion *::before, html.epure-a11y-motion *::after {'
        + ' animation: none !important; transition: none !important; scroll-behavior: auto !important; }',
    'html.epure-a11y-focusring :focus-visible { outline: 3px solid #fff !important; outline-offset: 0 !important;'
        + ' box-shadow: 0 0 0 6px #000 !important; }',
    // The dark scheme: the content inverted, its images and videos as they are.
    'html.epure-dark:not(.epure-a11y-scheme-yellow) { background: #fff; filter: invert(1) hue-rotate(180deg); }',
    `html.epure-dark:not(.epure-a11y-scheme-yellow) ${MEDIA} { filter: invert(1) hue-rotate(180deg); }`,
    // Yellow on black, over every colour of the content.
    'html.epure-a11y-scheme-yellow, html.epure-a11y-scheme-yellow body { color: #ff0 !important; background: #000 !important; }',
    'html.epure-a11y-scheme-yellow body * { color: inherit !important; background-color: transparent !important;'
        + ' border-color: #b8b800 !important; }',
    'html.epure-a11y-scheme-yellow :is(a, a *) { color: #8fd9ff !important; }',
    'html.epure-a11y-scheme-yellow :is(button, [role="button"], input, select, textarea) {'
        + ' border: 1px solid #ff0 !important; }',
].join('\n');

/**
 * The faces of the reading fonts of the page, for the frames: the same files, already known to the browser.
 *
 * @returns {string}
 */
const fontFaces = () => {
    const faces = [];
    Array.from(document.styleSheets).forEach((sheet) => {
        let list;
        try {
            list = sheet.cssRules;
        } catch (e) {
            return;
        }
        Array.from(list || []).forEach((rule) => {
            if (rule instanceof CSSFontFaceRule && /Atkinson|Lexend|OpenDyslexic/i.test(rule.style.fontFamily)) {
                faces.push(rule.cssText);
            }
        });
    });
    return faces.join('\n');
};

/** @type {string|null} The style sheet, built once. */
let sheet = null;

/**
 * Whether a frame shows a page of Moodle (H5P, a file preview), whose styles apply the preferences already: it takes
 * their classes only, its frames get the style sheet.
 *
 * @param {HTMLIFrameElement} frame
 * @returns {boolean}
 */
const moodlePage = (frame) => !!frame.contentWindow?.M?.cfg;

/**
 * Gives a frame the preferences of the page.
 *
 * @param {HTMLIFrameElement} frame
 */
const sync = (frame) => {
    if (!frame.epurePreferences) {
        frame.epurePreferences = true;
        frame.addEventListener('load', () => sync(frame));
    }
    let doc;
    try {
        doc = frame.contentDocument;
    } catch (e) {
        return;
    }
    if (!doc || !doc.documentElement || !doc.head) {
        return;
    }
    // The frames added to the frame later (the content of H5P in its page).
    if (!doc.epureFrames && doc.body) {
        doc.epureFrames = true;
        new MutationObserver(() => doc.querySelectorAll('iframe').forEach(sync))
            .observe(doc.body, {childList: true, subtree: true});
    }
    doc.querySelectorAll('iframe').forEach(sync);
    // The reading guide of the page follows the pointer over the frame too: the moves, in the coordinates of the
    // document around the frame, which passes them on when it is a frame itself.
    if (!doc.epurePointer) {
        doc.epurePointer = true;
        doc.addEventListener('pointermove', (e) => {
            const box = frame.getBoundingClientRect();
            frame.ownerDocument.dispatchEvent(new PointerEvent('pointermove', {clientX: box.left + e.clientX,
                clientY: box.top + e.clientY, pointerType: e.pointerType}));
        }, {passive: true});
    }
    // The classes of the preferences, those chosen in the panel « Aa » of the page included.
    const html = doc.documentElement;
    const wanted = Array.from(document.documentElement.classList).filter((name) => PREFERENCE.test(name));
    Array.from(html.classList).filter((name) => PREFERENCE.test(name) && !wanted.includes(name))
        .forEach((name) => html.classList.remove(name));
    wanted.forEach((name) => html.classList.add(name));
    if (!moodlePage(frame) && !doc.getElementById(STYLE)) {
        sheet = sheet ?? fontFaces() + '\n' + rules();
        const style = doc.createElement('style');
        style.id = STYLE;
        style.textContent = sheet;
        doc.head.append(style);
    }
};

/**
 * Gives every frame of the page the preferences.
 */
const syncAll = () => document.querySelectorAll('iframe').forEach(sync);

/**
 * Starts: the frames of the page now, those added later, and again when the preferences change (the panel « Aa »).
 */
export const init = () => {
    syncAll();
    new MutationObserver((mutations) => {
        if (mutations.some((mutation) => Array.from(mutation.addedNodes).some((node) => node.nodeType === 1
                && (node.tagName === 'IFRAME' || node.querySelector('iframe'))))) {
            syncAll();
        }
    }).observe(document.body, {childList: true, subtree: true});
    new MutationObserver(syncAll).observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
};
