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
 * Proposes the main colours of the logo under the brand colour setting.
 *
 * The colours are extracted in the browser from the saved logo. Clicking a
 * swatch, or a point of the logo (eyedropper), fills the colour field.
 *
 * @module     theme_epure/logo_colours
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

/** Size of the downscaled image used for the analysis, in pixels. */
const SAMPLE_SIZE = 72;

/** Maximum number of colours proposed. */
const MAX_COLOURS = 5;

/** Minimum share of the analysed pixels for a colour to be proposed. */
const MIN_SHARE = 0.03;

/** Minimum distance between two proposed colours, in RGB units. */
const MIN_DISTANCE = 60;

/**
 * Converts red, green and blue components to a #RRGGBB colour.
 *
 * @param {number[]} rgb Three values between 0 and 255.
 * @returns {string}
 */
const toHex = (rgb) => '#' + rgb.map((value) => Math.round(value).toString(16).padStart(2, '0')).join('').toUpperCase();

/**
 * Lightness and saturation of a colour, as in the HSL model.
 *
 * @param {number} r Red, 0 to 255.
 * @param {number} g Green, 0 to 255.
 * @param {number} b Blue, 0 to 255.
 * @returns {{l: number, s: number}}
 */
const lightnessSaturation = (r, g, b) => {
    const max = Math.max(r, g, b) / 255;
    const min = Math.min(r, g, b) / 255;
    const l = (max + min) / 2;
    const d = max - min;
    return {l, s: d === 0 ? 0 : d / (1 - Math.abs(2 * l - 1))};
};

/**
 * Draws an image on a canvas.
 *
 * @param {HTMLImageElement} img Loaded image.
 * @param {number} width Canvas width.
 * @param {number} height Canvas height.
 * @returns {CanvasRenderingContext2D}
 */
const draw = (img, width, height) => {
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(width));
    canvas.height = Math.max(1, Math.round(height));
    const context = canvas.getContext('2d', {willReadFrequently: true});
    context.drawImage(img, 0, 0, canvas.width, canvas.height);
    return context;
};

/**
 * Extracts the main colours of an image.
 *
 * Transparent pixels and near-white or near-black backgrounds are ignored, colours
 * are grouped by similarity, and vivid colours come first.
 *
 * @param {HTMLImageElement} img Loaded image.
 * @returns {string[]} Hex colours.
 */
export const extractColours = (img) => {
    const width = img.naturalWidth || SAMPLE_SIZE;
    const height = img.naturalHeight || SAMPLE_SIZE;
    const ratio = Math.min(1, SAMPLE_SIZE / Math.max(width, height));
    const context = draw(img, width * ratio, height * ratio);
    const data = context.getImageData(0, 0, context.canvas.width, context.canvas.height).data;

    const buckets = new Map();
    let total = 0;
    for (let i = 0; i < data.length; i += 4) {
        const [r, g, b, a] = [data[i], data[i + 1], data[i + 2], data[i + 3]];
        if (a < 160) {
            continue;
        }
        const {l, s} = lightnessSaturation(r, g, b);
        if (l > 0.93 || l < 0.06) {
            continue;
        }
        // Colours are grouped in steps of 16 per channel.
        const key = `${Math.floor(r / 16)},${Math.floor(g / 16)},${Math.floor(b / 16)}`;
        const bucket = buckets.get(key) || {n: 0, r: 0, g: 0, b: 0, s: 0};
        bucket.n++;
        bucket.r += r;
        bucket.g += g;
        bucket.b += b;
        bucket.s += s;
        buckets.set(key, bucket);
        total++;
    }

    const candidates = [...buckets.values()]
        .filter((bucket) => bucket.n >= total * MIN_SHARE)
        .map((bucket) => ({
            rgb: [bucket.r / bucket.n, bucket.g / bucket.n, bucket.b / bucket.n],
            saturation: bucket.s / bucket.n,
            score: bucket.n * (0.25 + bucket.s / bucket.n),
        }))
        .sort((x, y) => y.score - x.score);

    const picked = [];
    candidates.forEach((candidate) => {
        if (picked.length >= MAX_COLOURS) {
            return;
        }
        const far = picked.every((other) => Math.hypot(
            candidate.rgb[0] - other.rgb[0],
            candidate.rgb[1] - other.rgb[1],
            candidate.rgb[2] - other.rgb[2]
        ) > MIN_DISTANCE);
        if (far) {
            picked.push(candidate);
        }
    });

    return picked
        .sort((x, y) => Number(y.saturation > 0.2) - Number(x.saturation > 0.2) || y.score - x.score)
        .map((candidate) => toHex(candidate.rgb));
};

/**
 * Fills the colour field and announces the change.
 *
 * @param {HTMLElement} region The logo colours region.
 * @param {string} colour Hex colour.
 */
const apply = async(region, colour) => {
    const input = document.getElementById(region.dataset.input);
    if (!input) {
        return;
    }
    input.value = colour;
    input.dispatchEvent(new Event('input', {bubbles: true}));
    input.dispatchEvent(new Event('change', {bubbles: true}));
    region.querySelectorAll('[data-colour]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.colour === colour));
    });
    region.querySelector('[data-region="status"]').textContent = await getString('logocolours_applied', 'theme_epure', colour);
};

/**
 * Renders a swatch button for each colour.
 *
 * @param {HTMLElement} region The logo colours region.
 * @param {string[]} colours Hex colours.
 */
const renderSwatches = async(region, colours) => {
    const container = region.querySelector('[data-region="swatches"]');
    const current = (document.getElementById(region.dataset.input)?.value || '').toUpperCase();
    const labels = await Promise.all(colours.map((colour) => getString('logocolours_use', 'theme_epure', colour)));
    container.replaceChildren(...colours.map((colour, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'epure-logocolours-swatch';
        button.dataset.colour = colour;
        button.setAttribute('aria-pressed', String(colour === current));
        button.setAttribute('aria-label', labels[index]);
        const dot = document.createElement('span');
        dot.className = 'epure-logocolours-dot';
        dot.style.backgroundColor = colour;
        dot.setAttribute('aria-hidden', 'true');
        button.append(dot, document.createTextNode(colour));
        return button;
    }));
};

/**
 * Colour of the logo under the pointer.
 *
 * @param {HTMLImageElement} img The logo.
 * @param {MouseEvent} e Click event.
 * @returns {string|null} Hex colour, or null on a transparent point.
 */
const pickColour = (img, e) => {
    // The image is drawn inside its padding and border: only that inner box maps to the pixels.
    const rect = img.getBoundingClientRect();
    const style = window.getComputedStyle(img);
    const left = rect.left + parseFloat(style.borderLeftWidth) + parseFloat(style.paddingLeft);
    const top = rect.top + parseFloat(style.borderTopWidth) + parseFloat(style.paddingTop);
    const width = img.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
    const height = img.clientHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom);
    const px = (e.clientX - left) / width;
    const py = (e.clientY - top) / height;
    if (px < 0 || px >= 1 || py < 0 || py >= 1) {
        return null;
    }
    const context = draw(img, img.naturalWidth, img.naturalHeight);
    const [r, g, b, a] = context.getImageData(Math.floor(px * img.naturalWidth), Math.floor(py * img.naturalHeight), 1, 1).data;
    return a < 40 ? null : toHex([r, g, b]);
};

/**
 * Starts the extraction once the logo is loaded.
 *
 * @param {string} selector Selector of the logo colours region.
 */
export const init = (selector) => {
    const region = document.querySelector(selector);
    const img = region?.querySelector('[data-region="logo"]');
    if (!img) {
        return;
    }

    const start = async() => {
        try {
            await renderSwatches(region, extractColours(img));
        } catch (error) {
            // An image the browser cannot read (for example a cross-origin file) proposes no colour.
            region.querySelector('[data-region="status"]').textContent = await getString('logocolours_error', 'theme_epure');
            return;
        }
        img.classList.add('epure-logocolours-pickable');
        img.addEventListener('click', (e) => {
            const colour = pickColour(img, e);
            if (colour) {
                apply(region, colour);
            }
        });
    };

    region.addEventListener('click', (e) => {
        const button = e.target.closest('[data-colour]');
        if (button) {
            apply(region, button.dataset.colour);
        }
    });

    if (img.complete && img.naturalWidth) {
        start();
    } else {
        img.addEventListener('load', start, {once: true});
    }
};
