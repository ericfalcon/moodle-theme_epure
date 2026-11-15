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
 * Stops the animated images (GIF) of the page while the display preference « Stop the animated images » is on (the
 * class epure-a11y-stillimages of the html element), WCAG 2.2.2: each shows its first frame, with a button to play it
 * and to stop it again. Follows the preference when it changes in the panel « Aa », without reloading the page. An
 * image of another site, which the browser does not let the page read, stays as it is.
 *
 * @module     theme_epure/still_images
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';

/** @var {string} The class of the html element while the preference is on. */
const CLASS = 'epure-a11y-stillimages';

/** @var {Object} Texts of the button. */
let strings = null;

/**
 * Whether an image is a GIF, by its address.
 *
 * @param {HTMLImageElement} img Image.
 * @returns {boolean}
 */
const isGif = (img) => /\.gif($|[?#])/i.test(img.currentSrc || img.src);

/**
 * The first frame of an image, as the address of a still image; null when the browser does not let the page read it.
 *
 * @param {HTMLImageElement} img Image, loaded.
 * @returns {string|null}
 */
const firstFrame = (img) => {
    try {
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        canvas.getContext('2d').drawImage(img, 0, 0);
        return canvas.toDataURL('image/png');
    } catch (e) {
        // Expected: an image of another site taints the canvas.
        return null;
    }
};

/**
 * Stops an image: its first frame instead of it, and a button to play it.
 *
 * @param {HTMLImageElement} img Image.
 */
const stop = (img) => {
    if (img.dataset.epureStill || !img.complete || !img.naturalWidth) {
        return;
    }
    const still = firstFrame(img);
    if (!still) {
        return;
    }
    img.dataset.epureAnimated = img.src;
    img.dataset.epureStill = still;
    img.src = still;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-secondary btn-sm epure-still-play';
    button.setAttribute('aria-pressed', 'false');
    const alt = img.getAttribute('alt')?.trim();
    button.innerHTML = '<i class="fa fa-play" aria-hidden="true"></i> ';
    button.append(alt ? strings.play.replace('{$a}', alt) : strings.playnoalt);
    button.addEventListener('click', () => {
        const playing = button.getAttribute('aria-pressed') === 'true';
        img.src = playing ? img.dataset.epureStill : img.dataset.epureAnimated;
        button.setAttribute('aria-pressed', String(!playing));
        button.querySelector('i').className = `fa ${playing ? 'fa-play' : 'fa-pause'}`;
    });
    img.after(button);
};

/**
 * Plays an image again for good: the preference was turned off.
 *
 * @param {HTMLImageElement} img Image.
 */
const restore = (img) => {
    if (!img.dataset.epureAnimated) {
        return;
    }
    img.src = img.dataset.epureAnimated;
    delete img.dataset.epureAnimated;
    delete img.dataset.epureStill;
    if (img.nextElementSibling?.classList.contains('epure-still-play')) {
        img.nextElementSibling.remove();
    }
};

/**
 * Stops or plays the animated images of the page, as the preference says.
 */
const apply = () => {
    const on = document.documentElement.classList.contains(CLASS);
    document.querySelectorAll('img').forEach((img) => {
        if (!on) {
            restore(img);
        } else if (isGif(img) || img.dataset.epureAnimated) {
            if (img.complete) {
                stop(img);
            } else {
                img.addEventListener('load', () => stop(img), {once: true});
            }
        }
    });
};

/**
 * Starts: now, and when the preference changes in the panel « Aa ».
 */
export const init = async() => {
    if (document.documentElement.dataset.epureStill) {
        return;
    }
    document.documentElement.dataset.epureStill = '1';
    const [play, playnoalt] = await getStrings([
        {key: 'stillimages_play', component: 'theme_epure'},
        {key: 'stillimages_playnoalt', component: 'theme_epure'},
    ]);
    strings = {play, playnoalt};
    apply();
    let on = document.documentElement.classList.contains(CLASS);
    new MutationObserver(() => {
        const now = document.documentElement.classList.contains(CLASS);
        if (now !== on) {
            on = now;
            apply();
        }
    }).observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
};
