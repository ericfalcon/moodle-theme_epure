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
 * Panel of display preferences (button « Aa », shortcut Alt+A).
 *
 * Each change is applied at once, as classes on the html element, and saved in the profile of
 * the user, so that it holds on every page and every device.
 *
 * @module     theme_epure/a11y_panel
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {getString} from 'core/str';

/** Preferences that are on or off. */
const SWITCHES = ['spacing', 'contrast', 'underline', 'motion'];

/**
 * Current choices of the panel.
 *
 * @param {HTMLElement} panel The panel.
 * @returns {{text: number, font: string, switches: Object<string, boolean>}}
 */
const read = (panel) => ({
    text: Number(panel.querySelector('[name="epure-a11y-text"]:checked')?.value || 100),
    font: panel.querySelector('[name="epure-a11y-font"]:checked')?.value || '',
    scheme: panel.querySelector('[name="epure-a11y-scheme"]:checked')?.value || '',
    switches: Object.fromEntries(SWITCHES.map((name) => [name, panel.querySelector(`[name="epure-a11y-${name}"]`).checked])),
});

/**
 * Applies the choices to the page.
 *
 * @param {{text: number, font: string, scheme: string, switches: Object<string, boolean>}} choices Choices.
 * @param {string} defaultScheme Scheme of the site or the company: light, dark or auto.
 */
const applyToPage = (choices, defaultScheme) => {
    const html = document.documentElement;
    [...html.classList].filter((name) => name.startsWith('epure-a11y-') || name.startsWith('epure-dark'))
        .forEach((name) => html.classList.remove(name));
    const scheme = choices.scheme || defaultScheme;
    if (scheme === 'dark') {
        html.classList.add('epure-dark');
    } else if (scheme === 'auto') {
        html.classList.add('epure-dark-auto');
    }
    if (choices.text !== 100) {
        html.classList.add(`epure-a11y-text-${choices.text}`);
    }
    if (choices.font) {
        html.classList.add(`epure-a11y-font-${choices.font}`);
    }
    SWITCHES.filter((name) => choices.switches[name]).forEach((name) => html.classList.add(`epure-a11y-${name}`));
    // The primary navigation moves the items that no longer fit into its « More » menu on resize.
    window.dispatchEvent(new Event('resize'));
};

/**
 * Saves the choices in the profile of the user.
 *
 * @param {HTMLElement} panel The panel.
 * @param {{text: number, font: string, switches: Object<string, boolean>}} choices Choices.
 */
const save = async(panel, choices) => {
    const preferences = [
        {type: 'theme_epure_a11y_text', value: String(choices.text)},
        {type: 'theme_epure_a11y_font', value: choices.font},
        {type: 'theme_epure_a11y_scheme', value: choices.scheme},
        ...SWITCHES.map((name) => ({type: `theme_epure_a11y_${name}`, value: choices.switches[name] ? '1' : '0'})),
    ];
    const status = panel.querySelector('[data-region="status"]');
    try {
        await Ajax.call([{methodname: 'core_user_update_user_preferences', args: {preferences}}])[0];
        status.textContent = await getString('a11ysaved', 'theme_epure');
    } catch (error) {
        status.textContent = await getString('a11ynotsaved', 'theme_epure');
    }
};

/**
 * Starts the panel.
 */
export const init = () => {
    const region = document.querySelector('[data-region="epure-a11y"]');
    // The header may be rendered more than once: the panel is only started once.
    if (!region || region.dataset.initialised) {
        return;
    }
    region.dataset.initialised = '1';
    const toggle = region.querySelector('[data-action="toggle"]');
    const panel = region.querySelector('.epure-a11y-panel');
    const defaultScheme = region.dataset.defaultScheme || 'light';

    const open = () => {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        // After the click has ended: Moodle's own handlers of header buttons give them the focus back.
        setTimeout(() => (panel.querySelector('input:checked') || panel.querySelector('input')).focus(), 0);
    };
    const close = (restoreFocus = true) => {
        if (panel.hidden) {
            return;
        }
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (restoreFocus) {
            toggle.focus();
        }
    };

    toggle.addEventListener('click', () => (panel.hidden ? open() : close()));
    region.querySelector('[data-action="close"]').addEventListener('click', () => close());
    panel.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            close();
        }
    });
    document.addEventListener('click', (e) => {
        if (!region.contains(e.target)) {
            close(false);
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.altKey && !e.ctrlKey && !e.metaKey && (e.key === 'a' || e.key === 'A' || e.code === 'KeyA')) {
            e.preventDefault();
            if (panel.hidden) {
                open();
            } else {
                close();
            }
        }
    });

    panel.addEventListener('change', () => {
        const choices = read(panel);
        applyToPage(choices, defaultScheme);
        save(panel, choices);
    });
    region.querySelector('[data-action="reset"]').addEventListener('click', () => {
        panel.querySelector('[name="epure-a11y-text"][value="100"]').checked = true;
        panel.querySelector('[name="epure-a11y-font"][value=""]').checked = true;
        panel.querySelector('[name="epure-a11y-scheme"][value=""]').checked = true;
        SWITCHES.forEach((name) => {
            panel.querySelector(`[name="epure-a11y-${name}"]`).checked = false;
        });
        const choices = read(panel);
        applyToPage(choices, defaultScheme);
        save(panel, choices);
    });
};
