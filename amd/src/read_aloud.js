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
 * @module     theme_epure/read_aloud
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';

/** @type {string} The blocks of text read one after the other. */
const BLOCKS = 'h1, h2, h3, h4, h5, h6, p, li, dt, dd, td, th, figcaption, blockquote, caption, label, legend';

/** @type {number[]} Speeds offered. */
const RATES = [0.75, 1, 1.25, 1.5];

/** @type {HTMLElement|null} The bar. */
let bar = null;
/** @type {HTMLElement[]} Blocks left to read. */
let queue = [];
/** @type {HTMLElement|null} Block being read. */
let current = null;
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
const textOf = (block) => block.innerText.replace(/\s+/g, ' ').trim();

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
    const utterance = new window.SpeechSynthesisUtterance(text);
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
    say(text, language(block), next);
};

/**
 * The blocks of the main content of the page, from the one shown at the top of the screen: the innermost only (a
 * paragraph in an item of a list is read once), visible ones.
 *
 * @returns {HTMLElement[]}
 */
const blocks = () => {
    const main = document.querySelector('#region-main, [role="main"], main') || document.body;
    const all = [...main.querySelectorAll(BLOCKS)].filter((block) => !block.querySelector(BLOCKS)
        && block.getClientRects().length && !block.closest('[aria-hidden="true"], .sr-only, .visually-hidden, script'));
    const first = all.findIndex((block) => block.getBoundingClientRect().bottom > 0);
    return all.slice(Math.max(0, first));
};

/**
 * Stops the reading.
 */
const stop = () => {
    reading = false;
    queue = [];
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
        queue = blocks();
        next();
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
    const [title, play, pause, resume, stopLabel, rateLabel, novoice, failed] = await getStrings([
        {key: 'a11yspeech', component: 'theme_epure'},
        {key: 'a11yspeech_play', component: 'theme_epure'},
        {key: 'a11yspeech_pause', component: 'theme_epure'},
        {key: 'a11yspeech_resume', component: 'theme_epure'},
        {key: 'a11yspeech_stop', component: 'theme_epure'},
        {key: 'a11yspeech_rate', component: 'theme_epure'},
        {key: 'a11yspeech_novoice', component: 'theme_epure', param: '{$a}'},
        {key: 'a11yspeech_failed', component: 'theme_epure'},
    ]);
    if (bar) {
        return;
    }
    bar = document.createElement('div');
    bar.className = 'epure-a11y-speechbar d-print-none';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', title);
    Object.assign(bar.dataset, {play, pause, resume, novoice, failed});
    const options = RATES.map((value) => `<option value="${value}"${value === rate ? ' selected' : ''}>`
        + `${String(value).replace('.', document.documentElement.lang.startsWith('en') ? '.' : ',')} ×</option>`).join('');
    bar.innerHTML = `<button type="button" class="btn btn-primary" data-action="play">`
        + `<i class="fa fa-play" aria-hidden="true"></i> <span></span></button>`
        + `<button type="button" class="btn btn-secondary" data-action="stop"><i class="fa fa-stop" aria-hidden="true"></i> `
        + `<span></span></button>`
        + `<label class="epure-a11y-speechrate"><span></span> `
        + `<select class="form-select custom-select">${options}</select></label>`
        + `<p class="epure-a11y-speechstatus" role="status" data-region="status" hidden></p>`;
    bar.querySelector('[data-action="stop"] span').textContent = stopLabel;
    bar.querySelector('.epure-a11y-speechrate span').textContent = rateLabel;
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
    document.body.append(bar);
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
    }
};

/**
 * Shows the bar when the user chose the reading aloud, read from the classes of the page.
 */
export const init = () => {
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
