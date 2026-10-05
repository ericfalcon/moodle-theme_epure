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
 * Quick search (Ctrl+K or ⌘K): a dialogue that finds the pages of the menus, the courses, the
 * activities and the administration pages, and opens the result chosen with the keyboard or the mouse.
 *
 * @module     theme_epure/quick_search
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/** Menus of the page whose links are searched: primary navigation, user menu, tabs of the course or the page. */
const MENU_LINKS = '.primary-navigation a[href], #user-action-menu a[href], .secondary-navigation a[href],'
    + ' .moremenu a[href]';

/** Delay before the server is asked, while the user types. */
const DELAY = 250;

/**
 * Text without case nor accents.
 *
 * @param {string} text Text.
 * @returns {string}
 */
const normalise = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

/**
 * Links of the menus of the page, without duplicates and without the links that act (log out, sesskey).
 *
 * @returns {Array<{name: string, url: string, icon: string, detail: string}>}
 */
const menuLinks = () => {
    const seen = new Set();
    const links = [];
    document.querySelectorAll(MENU_LINKS).forEach((link) => {
        const url = link.href;
        const name = (link.textContent || '').replace(/\s+/g, ' ').trim();
        if (!name || !url || url.includes('#') && url.split('#')[0] === location.href.split('#')[0]
                || url.includes('sesskey=') || url.includes('logout.php') || seen.has(url)) {
            return;
        }
        seen.add(url);
        links.push({name, url, icon: 'fa-arrow-right', detail: ''});
    });
    return links;
};

/**
 * Starts the quick search.
 */
export const init = () => {
    const dialog = document.getElementById('epure-qs');
    const opener = document.querySelector('[data-action="epure-quicksearch"]');
    if (!dialog || !opener || dialog.dataset.ready) {
        return;
    }
    dialog.dataset.ready = '1';
    // On a Mac, the shortcut is ⌘K.
    if (/Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)) {
        opener.querySelector('kbd').textContent = '⌘K';
    }
    // The dialogue is a child of the body, so that no menu or header can hide it.
    document.body.append(dialog);
    const input = dialog.querySelector('.epure-qs-input');
    const list = dialog.querySelector('.epure-qs-list');
    const status = dialog.querySelector('.epure-qs-status');
    let pages = [];
    let request = 0;
    let timer = null;
    let options = [];
    let active = -1;

    const setActive = (index) => {
        options.forEach((option) => option.classList.remove('active'));
        active = options.length ? (index + options.length) % options.length : -1;
        if (active < 0) {
            input.removeAttribute('aria-activedescendant');
            return;
        }
        options[active].classList.add('active');
        input.setAttribute('aria-activedescendant', options[active].id);
        options[active].scrollIntoView({block: 'nearest'});
    };

    const go = (url, newtab) => {
        if (newtab) {
            window.open(url, '_blank', 'noopener');
            return;
        }
        dialog.close();
        window.location.href = url;
    };

    const render = (groups, query) => {
        list.replaceChildren();
        options = [];
        groups.filter((group) => group.items.length).forEach((group, g) => {
            const section = document.createElement('div');
            section.setAttribute('role', 'group');
            section.setAttribute('aria-labelledby', `epure-qs-group-${g}`);
            const label = document.createElement('div');
            label.className = 'epure-qs-group';
            label.id = `epure-qs-group-${g}`;
            label.textContent = group.label;
            section.append(label);
            group.items.forEach((item) => {
                const option = document.createElement('div');
                option.className = 'epure-qs-option';
                option.id = `epure-qs-option-${options.length}`;
                option.setAttribute('role', 'option');
                option.dataset.url = item.url;
                const icon = item.image ? Object.assign(document.createElement('img'), {src: item.image, alt: ''})
                    : Object.assign(document.createElement('i'), {className: `fa ${item.icon || 'fa-arrow-right'}`});
                icon.setAttribute('aria-hidden', 'true');
                icon.classList.add('epure-qs-icon');
                const text = document.createElement('span');
                text.className = 'epure-qs-text';
                const name = document.createElement('span');
                name.className = 'epure-qs-name';
                name.textContent = item.name;
                text.append(name);
                if (item.detail) {
                    const detail = document.createElement('span');
                    detail.className = 'epure-qs-detail';
                    detail.textContent = item.detail;
                    text.append(detail);
                }
                option.append(icon, text);
                option.addEventListener('click', (event) => go(item.url, event.ctrlKey || event.metaKey));
                option.addEventListener('mousemove', () => setActive(options.indexOf(option)));
                section.append(option);
                options.push(option);
            });
            list.append(section);
        });
        input.setAttribute('aria-expanded', options.length ? 'true' : 'false');
        setActive(0);
        if (query !== null) {
            if (options.length) {
                status.textContent = options.length === 1 ? dialog.dataset.countone
                    : dialog.dataset.count.replace('NUMBER', options.length);
            } else {
                status.textContent = dialog.dataset.none.replace('QUERY', query);
            }
            if (!options.length) {
                const empty = document.createElement('p');
                empty.className = 'epure-qs-empty';
                empty.textContent = status.textContent;
                list.append(empty);
            }
        }
    };

    const localGroups = (query) => {
        const words = normalise(query).split(/\s+/).filter(Boolean);
        const found = pages.filter((page) => words.every((word) => normalise(page.name).includes(word)));
        return found.length ? [{key: 'pages', label: dialog.dataset.pages, items: found.slice(0, words.length ? 6 : 8)}] : [];
    };

    const search = () => {
        const query = input.value.trim();
        const local = localGroups(query);
        clearTimeout(timer);
        if (normalise(query).length < 2) {
            render(local, null);
            status.textContent = '';
            return;
        }
        render(local, null);
        status.textContent = dialog.dataset.searching;
        const current = ++request;
        timer = setTimeout(() => {
            Ajax.call([{methodname: 'theme_epure_quick_search', args: {query}}])[0].then((result) => {
                if (current === request) {
                    render(result.groups.concat(local), query);
                }
                return result;
            }).catch(() => {
                if (current === request) {
                    render(local, query);
                }
            });
        }, DELAY);
    };

    const open = () => {
        if (dialog.open) {
            return;
        }
        pages = menuLinks();
        input.value = '';
        search();
        dialog.showModal();
        input.focus();
    };

    opener.addEventListener('click', open);
    dialog.querySelector('[data-action="epure-quicksearch-close"]').addEventListener('click', () => dialog.close());
    // A click on the backdrop closes the dialogue.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
    dialog.addEventListener('close', () => {
        request++;
        clearTimeout(timer);
        opener.focus();
    });
    input.addEventListener('input', search);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(active + (event.key === 'ArrowDown' ? 1 : -1));
        } else if (event.key === 'Home' && options.length && event.ctrlKey) {
            setActive(0);
        } else if (event.key === 'Enter' && active >= 0) {
            event.preventDefault();
            go(options[active].dataset.url, event.ctrlKey || event.metaKey);
        }
    });
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && !event.altKey && !event.shiftKey && event.key.toLowerCase() === 'k'
                && !event.target.isContentEditable) {
            event.preventDefault();
            if (dialog.open) {
                dialog.close();
            } else {
                open();
            }
        }
    });
};
