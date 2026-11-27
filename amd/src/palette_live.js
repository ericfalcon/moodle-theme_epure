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
 * Live preview of the colours (theme_epure/palette_live): as the brand colour, the accent colour or the style of the
 * header change, before they are saved, the theme computes the palette (theme_epure_palette_preview) and the preview
 * and its swatches follow. The colour pickers of Moodle change the fields without an event: they are also read every
 * moment.
 *
 * @module     theme_epure/palette_live
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Starts the preview of a region.
 *
 * @param {string} regionid Identifier of the region of the preview.
 * @param {Object} selectors Selectors of the fields: brand, accent, header.
 */
export const init = (regionid, selectors) => {
    const region = document.getElementById(regionid);
    if (!region) {
        return;
    }
    const field = (name) => (selectors[name] ? document.querySelector(selectors[name]) : null);
    const fields = {brand: field('brand'), accent: field('accent'), header: field('header')};
    const read = () => ({
        brand: fields.brand?.value.trim() || '',
        accent: fields.accent?.value.trim() || '',
        headerstyle: fields.header?.value || '',
    });
    let last = JSON.stringify(read());
    let timer = null;
    let request = 0;

    const update = () => {
        const values = read();
        const key = JSON.stringify(values);
        if (key === last) {
            return;
        }
        last = key;
        const current = ++request;
        Ajax.call([{methodname: 'theme_epure_palette_preview', args: values}])[0].then((result) => {
            // An answer to an older change does not overwrite a newer one.
            if (current === request) {
                result.vars.forEach((item) => region.style.setProperty('--' + item.name, item.value));
                const swatches = region.querySelector('[data-region="swatches"]');
                if (swatches) {
                    swatches.innerHTML = result.swatches;
                }
            }
            return result;
        }).catch(() => {
            // The preview stays as it was: the colours are checked when they are saved.
            last = '';
        });
    };
    const later = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(update, 250);
    };
    Object.values(fields).forEach((input) => {
        input?.addEventListener('input', later);
        input?.addEventListener('change', later);
    });
    window.setInterval(update, 800);
};
