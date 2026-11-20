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
 * Reading aloud of the display preferences (« Aa »): a bar at the bottom of the screen reads the text selected, else
 * the page from the paragraph shown at the top of the screen, block by block, each one marked while it is read. With
 * the voices of the device only, in the language marked in the text: nothing leaves the browser. For the learners who
 * read with difficulty; the screen readers have their own voice.
 *
 * Besides the blocks of text (paragraphs, titles, items of lists, cells…), a text written straight in another element
 * is read too, without what that element holds in blocks of its own: the boxes of Components for Learning (C4L) and
 * of other plugins of the editor put their text in a div, without a paragraph. A note named for the screen readers
 * (role="note" and aria-label: « Key concept », « Tip »…) is said by its name before its text, as a screen reader
 * does: its icon alone tells nothing to the ear. An image with a text alternative is said by it (« Image: plan of the
 * exits »), in the order of the page; the decorative images (an empty alternative, role presentation) and the icons
 * of Moodle are not.
 *
 * The reading starts at the place clicked or reached with the keyboard last (a card, a paragraph), when it is still
 * on the screen; else at the block shown at the top of the screen.
 *
 * In a window that opens over the page (the preview of the files…), the bar goes into the window, which keeps the
 * rest of the page out of reach, and reads the window, not the page behind. A PDF shown there is read in its text
 * version: the bar presses the button that shows it (data-epure-textversion), and waits for the text.
 *
 * @module     theme_epure/read_aloud
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';
import Templates from 'core/templates';

/** @type {string} The blocks of text read one after the other. */
const BLOCKS = 'h1, h2, h3, h4, h5, h6, p, li, dt, dd, td, th, figcaption, blockquote, caption, label, legend';

/** @type {string} What is never read: hidden from everyone, or a control rather than a text. */
const SKIPPED = '[aria-hidden="true"], .sr-only, .visually-hidden, script, style, template, button, select, textarea, '
    + '[role="button"], [role="menu"], .dropdown-menu, nav';

/** @type {number[]} Speeds offered. */
const RATES = [0.75, 1, 1.25, 1.5];

/** @type {HTMLElement|null} The bar. */
let bar = null;
/** @type {HTMLElement[]} Blocks left to read. */
let queue = [];
/** @type {HTMLElement|null} Block being read. */
let current = null;
/** @type {WeakSet<HTMLElement>} Elements whose own text only is read: the blocks they hold are read on their own. */
const partial = new WeakSet();
/** @type {WeakSet<HTMLElement>} Notes whose name was said in this reading. */
let named = new WeakSet();
/**
 * What is being said: kept, since Chrome can let an utterance go while it is said, and then never tells its end, which
 * stopped the reading in the middle of the page.
 *
 * @type {SpeechSynthesisUtterance|null}
 */
let utterance = null;
/** @type {number} Speed. */
let rate = 1;
/** @type {boolean} Whether the reading goes on to the next block. */
let reading = false;
/**
 * The last text selected: on a phone, touching the button clears the selection before the button is pressed.
 *
 * @type {{text: string, node: Node, time: number}|null}
 */
let selected = null;

/**
 * Whether the browser can read aloud.
 *
 * @returns {boolean}
 */
export const supported = () => 'speechSynthesis' in window && typeof window.SpeechSynthesisUtterance === 'function';

/**
 * The voices installed on the device: those of a service on the network (localService false) would send it the text,
 * and are never used.
 *
 * @returns {SpeechSynthesisVoice[]}
 */
const localVoices = () => window.speechSynthesis.getVoices().filter((v) => v.localService);

/**
 * Whether the device has voices of its own, once the browser has listed them (it does so after the page, at its pace).
 *
 * @returns {Promise<boolean>}
 */
export const available = () => new Promise((resolve) => {
    if (!supported()) {
        resolve(false);
        return;
    }
    if (window.speechSynthesis.getVoices().length) {
        resolve(localVoices().length > 0);
        return;
    }
    const done = () => resolve(localVoices().length > 0);
    window.speechSynthesis.addEventListener('voiceschanged', done, {once: true});
    window.setTimeout(done, 2000);
});

/**
 * The voice of the device for a language: of that very language and country first, else of that language. Never one
 * of a service on the network, nor one of another language, which would read it wrong.
 *
 * @param {string} lang Language, as « fr » or « en-GB ».
 * @returns {SpeechSynthesisVoice|null}
 */
const voice = (lang) => {
    const base = lang.toLowerCase().split('-')[0];
    const matching = localVoices().filter((v) => v.lang.toLowerCase().replace('_', '-').split('-')[0] === base);
    return matching.find((v) => v.lang.toLowerCase().replace('_', '-') === lang.toLowerCase()) || matching[0] || null;
};

/**
 * Says something in the bar, to the screen readers too.
 *
 * @param {string} text Message, or empty.
 */
const tell = (text) => {
    const status = bar?.querySelector('[data-region="status"]');
    if (status) {
        status.textContent = text;
        status.hidden = !text;
    }
};

/**
 * The language of an element: the one marked on it or on one of its parents, else the one of the page.
 *
 * @param {Node} node Element or text.
 * @returns {string}
 */
const language = (node) => {
    const element = node instanceof Element ? node : node?.parentElement;
    return element?.closest('[lang]')?.getAttribute('lang') || document.documentElement.lang || 'en';
};

/**
 * The text of a block, read aloud: without what is hidden from everyone.
 *
 * @param {HTMLElement} block Block.
 * @returns {string}
 */
const textOf = (block) => {
    if (block.tagName === 'IMG') {
        const alt = block.getAttribute('alt').replace(/\s+/g, ' ').trim();
        return alt ? bar.dataset.image.replace('{$a}', alt) : '';
    }
    return (partial.has(block) ? ownText(block) : block.innerText).replace(/\s+/g, ' ').trim();
};

/**
 * Whether an element flows in the line of its parent (a link, a word in bold) rather than being a block of its own.
 *
 * @param {Element} element Element.
 * @returns {boolean}
 */
const inline = (element) => {
    const display = window.getComputedStyle(element).display;
    return display.startsWith('inline') || display === 'contents';
};

/**
 * The text written straight in an element, with its words in links or in bold, without the blocks it holds.
 *
 * @param {HTMLElement} element Element.
 * @returns {string}
 */
const ownText = (element) => [...element.childNodes].map((node) => {
    if (node.nodeType === Node.TEXT_NODE) {
        return node.nodeValue;
    }
    if (node.nodeType !== Node.ELEMENT_NODE || node.matches(BLOCKS) || node.querySelector(BLOCKS) || node.matches(SKIPPED)
            || !inline(node)) {
        return ' ';
    }
    return node.innerText;
}).join(' ');

/**
 * Marks the block read, and keeps it on the screen.
 *
 * @param {HTMLElement|null} block Block, or none.
 */
const mark = (block) => {
    current?.classList.remove('epure-a11y-speaking');
    current = block;
    if (block) {
        block.classList.add('epure-a11y-speaking');
        const rect = block.getBoundingClientRect();
        if (rect.top < 0 || rect.bottom > window.innerHeight - (bar?.offsetHeight || 0)) {
            const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches
                || document.documentElement.classList.contains('epure-a11y-motion');
            block.scrollIntoView({block: 'center', behavior: still ? 'auto' : 'smooth'});
        }
    }
};

/**
 * Says a text, then calls back once it is said. Without a voice of the device for its language, it is not said: the
 * bar tells it, and the reading goes on. When the device fails to say it, the reading stops and the bar tells it.
 *
 * @param {string} text Text.
 * @param {string} lang Its language.
 * @param {Function} next Called once it is said.
 */
const say = (text, lang, next) => {
    const chosen = voice(lang);
    if (!chosen) {
        tell(bar.dataset.novoice.replace('{$a}', lang));
        next();
        return;
    }
    utterance = new window.SpeechSynthesisUtterance(text);
    utterance.lang = chosen.lang;
    utterance.voice = chosen;
    utterance.rate = rate;
    utterance.onend = next;
    utterance.onerror = (e) => {
        // Stopped or replaced by the bar itself: not a failure.
        if (e.error === 'interrupted' || e.error === 'canceled') {
            return;
        }
        stop();
        tell(bar.dataset.failed);
    };
    window.speechSynthesis.speak(utterance);
};

/**
 * Reads the next block of the queue.
 */
const next = () => {
    if (!reading) {
        return;
    }
    const block = queue.shift();
    if (!block) {
        stop();
        return;
    }
    const text = textOf(block);
    if (!text) {
        next();
        return;
    }
    mark(block);
    // The name of a note (a box of C4L…), before its first text.
    const note = block.closest('[role="note"][aria-label]');
    if (note && !named.has(note)) {
        named.add(note);
        say(`${note.getAttribute('aria-label').trim()}. ${text}`, language(block), next);
        return;
    }
    say(text, language(block), next);
};

/**
 * The blocks of the main content of the page, from the one shown at the top of the screen: the innermost only (a
 * paragraph in an item of a list is read once), visible ones.
 *
 * @returns {HTMLElement[]}
 */
const blocks = () => {
    const main = root();
    const shown = (element) => element.getClientRects().length && !element.closest(SKIPPED);
    const found = new Set([...main.querySelectorAll(BLOCKS)].filter((block) => !block.querySelector(BLOCKS) && shown(block)));
    // The texts written straight in another element (a div of C4L…): the element that holds them, its own text read.
    const walker = document.createTreeWalker(main, NodeFilter.SHOW_TEXT);
    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        const parent = node.parentElement;
        if (!node.nodeValue.trim() || !parent || parent.closest(BLOCKS) || parent.closest(SKIPPED)) {
            continue;
        }
        let holder = parent;
        while (holder !== main && inline(holder)) {
            holder = holder.parentElement;
        }
        if (holder !== main && !found.has(holder) && shown(holder)) {
            partial.add(holder);
            found.add(holder);
        }
    }
    // The images with a text alternative, but the decorative ones and the icons of Moodle.
    main.querySelectorAll('img[alt]').forEach((image) => {
        if (image.getAttribute('alt').trim() && !image.matches('.icon, [role="presentation"], [role="none"]') && shown(image)) {
            found.add(image);
        }
    });
    // In the order of the page.
    const after = [Node.DOCUMENT_POSITION_FOLLOWING, Node.DOCUMENT_POSITION_FOLLOWING + Node.DOCUMENT_POSITION_CONTAINED_BY];
    const all = [...found].sort((a, b) => (after.includes(a.compareDocumentPosition(b)) ? -1 : 1));
    // From the place clicked last (the block that holds it, or the first one after it), else from the top of the screen.
    const from = onScreen(point) && main.contains(point) ? point : null;
    const first = from
        ? all.findIndex((block) => block.contains(from) || after.includes(from.compareDocumentPosition(block)))
        : all.findIndex((block) => block.getBoundingClientRect().bottom > 0);
    return all.slice(Math.max(0, first));
};

/**
 * What is read: the window open over the page, else the page from its header (its title, the cards of the
 * administration…) to the end of its content, without its footer.
 *
 * @returns {HTMLElement}
 */
const root = () => [...document.querySelectorAll('dialog[open]')].find((dialog) => dialog.matches(':modal'))
    || document.querySelector('#topofscroll, #region-main, [role="main"], main') || document.body;

/**
 * Shows the text of a PDF in the window, before reading it: presses the button of its text version, and waits for the
 * text (5 seconds at most).
 *
 * @param {HTMLElement} where What is read.
 * @returns {Promise}
 */
const textVersion = (where) => {
    const toggle = where.querySelector('[data-epure-textversion][data-state="pdf"]:not([hidden])');
    if (!toggle) {
        return Promise.resolve();
    }
    toggle.click();
    return new Promise((resolve) => {
        const started = Date.now();
        const wait = () => {
            if (!where.querySelector('[aria-busy="true"]') || Date.now() - started > 5000) {
                resolve();
            } else {
                setTimeout(wait, 100);
            }
        };
        wait();
    });
};

/**
 * Keeps the bar in the window open over the page, where it can be reached, and back in the page once it is closed.
 */
const followWindows = () => {
    const place = () => {
        if (!bar) {
            return;
        }
        const modal = [...document.querySelectorAll('dialog[open]')].find((dialog) => dialog.matches(':modal'));
        const home = modal || document.body;
        if (bar.parentElement !== home) {
            home.append(bar);
        }
    };
    new MutationObserver((mutations) => {
        if (mutations.some((mutation) => mutation.target.tagName === 'DIALOG')) {
            place();
        }
    }).observe(document.body, {attributes: true, attributeFilter: ['open'], subtree: true});
    return place;
};

/** @type {Element|null} The place clicked or reached with the keyboard last, out of the bar. */
let point = null;

/**
 * Keeps the place clicked or reached with the keyboard: the reading starts there.
 *
 * @param {Event} e The click or the focus.
 */
const remember = (e) => {
    if (e.target instanceof Element && !bar?.contains(e.target)) {
        point = e.target;
    }
};

/**
 * Whether an element is still in the page and on the screen.
 *
 * @param {Element|null} element Element.
 * @returns {boolean}
 */
const onScreen = (element) => {
    if (!element || !document.contains(element) || !element.getClientRects().length) {
        return false;
    }
    const rect = element.getBoundingClientRect();
    return rect.bottom > 0 && rect.top < window.innerHeight - (bar?.offsetHeight || 0);
};

/** @type {Function|null} Puts the bar where it can be reached. */
let placeBar = null;

/**
 * Stops the reading.
 */
const stop = () => {
    reading = false;
    queue = [];
    utterance = null;
    window.speechSynthesis.cancel();
    mark(null);
    update();
};

/**
 * Starts the reading: the text selected, else the page.
 */
const start = () => {
    window.speechSynthesis.cancel();
    tell('');
    // The text selected now, else the one selected just before the button was touched.
    const now = window.getSelection();
    const text = now ? now.toString().replace(/\s+/g, ' ').trim() : '';
    const recent = selected && Date.now() - selected.time < 10000 ? selected : null;
    const chosen = text ? {text, node: now.anchorNode} : recent;
    selected = null;
    reading = true;
    if (chosen) {
        queue = [];
        say(chosen.text, language(chosen.node), stop);
    } else {
        named = new WeakSet();
        textVersion(root()).then(() => {
            if (reading) {
                queue = blocks();
                next();
            }
            return null;
        }).catch(() => null);
    }
    update();
};

/**
 * Shows on the buttons of the bar whether it reads, is paused or waits.
 */
const update = () => {
    if (!bar) {
        return;
    }
    const synth = window.speechSynthesis;
    const play = bar.querySelector('[data-action="play"]');
    let label = bar.dataset.play;
    if (reading) {
        label = synth.paused ? bar.dataset.resume : bar.dataset.pause;
    }
    play.querySelector('span').textContent = label;
    play.querySelector('i').className = `fa ${reading && !synth.paused ? 'fa-pause' : 'fa-play'}`;
    bar.querySelector('[data-action="stop"]').disabled = !reading;
};

/**
 * Creates the bar of the reading.
 */
const create = async() => {
    const [title, play, pause, resume, stopLabel, rateLabel, novoice, failed, image] = await getStrings([
        {key: 'a11yspeech', component: 'theme_epure'},
        {key: 'a11yspeech_play', component: 'theme_epure'},
        {key: 'a11yspeech_pause', component: 'theme_epure'},
        {key: 'a11yspeech_resume', component: 'theme_epure'},
        {key: 'a11yspeech_stop', component: 'theme_epure'},
        {key: 'a11yspeech_rate', component: 'theme_epure'},
        {key: 'a11yspeech_novoice', component: 'theme_epure', param: '{$a}'},
        {key: 'a11yspeech_failed', component: 'theme_epure'},
        {key: 'a11yspeech_image', component: 'theme_epure', param: '{$a}'},
    ]);
    const decimal = document.documentElement.lang.startsWith('en') ? '.' : ',';
    const {html, js} = await Templates.renderForPromise('theme_epure/read_aloud_bar', {
        title,
        play,
        stop: stopLabel,
        rate: rateLabel,
        rates: RATES.map((value) => ({value, label: String(value).replace('.', decimal), selected: value === rate})),
    });
    if (bar) {
        return;
    }
    bar = Templates.appendNodeContents(document.body, html, js).find((node) => node.nodeType === Node.ELEMENT_NODE);
    placeBar = placeBar || followWindows();
    placeBar();
    Object.assign(bar.dataset, {play, pause, resume, novoice, failed, image});
    // The selection is read once the button is pressed: a click on it must not clear it.
    bar.querySelector('[data-action="play"]').addEventListener('mousedown', (e) => e.preventDefault());
    bar.querySelector('[data-action="play"]').addEventListener('click', () => {
        const synth = window.speechSynthesis;
        if (!reading) {
            start();
        } else if (synth.paused) {
            synth.resume();
        } else {
            synth.pause();
        }
        update();
    });
    bar.querySelector('[data-action="stop"]').addEventListener('click', stop);
    bar.querySelector('select').addEventListener('change', (e) => {
        rate = Number(e.target.value);
    });
    // The room of the bar under the page (_preferences.scss): its height, that of several lines with a large text.
    const room = () => document.documentElement.style.setProperty('--epure-speechbar-room', `${bar.offsetHeight + 16}px`);
    room();
    if (window.ResizeObserver) {
        new window.ResizeObserver(room).observe(bar);
    }
    update();
};

/**
 * Shows the bar of the reading, or takes it away.
 *
 * @param {boolean} on Whether the reading aloud is on.
 */
export const set = (on) => {
    if (!supported()) {
        return;
    }
    if (on) {
        create();
        return;
    }
    if (bar) {
        stop();
        bar.remove();
        bar = null;
        document.documentElement.style.removeProperty('--epure-speechbar-room');
    }
};

/**
 * Shows the bar when the user chose the reading aloud, read from the classes of the page.
 */
export const init = () => {
    document.addEventListener('pointerdown', remember, true);
    document.addEventListener('focusin', remember, true);
    set(document.documentElement.classList.contains('epure-a11y-speech'));
    // The last text selected out of the bar, kept a moment: on a phone, touching the button clears it first.
    document.addEventListener('selectionchange', () => {
        const selection = window.getSelection();
        const text = selection ? selection.toString().replace(/\s+/g, ' ').trim() : '';
        if (text && !bar?.contains(selection.anchorNode)) {
            selected = {text, node: selection.anchorNode, time: Date.now()};
        }
    });
    // Leaving the page stops the voice.
    window.addEventListener('pagehide', () => supported() && window.speechSynthesis.cancel());
};
