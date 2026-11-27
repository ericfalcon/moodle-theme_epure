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
 * Repairs of the ARIA attributes that Moodle and IOMAD write wrongly on some pages (WCAG 4.1.2), in the page only:
 * aria-hidden="1" becomes "true"; the lists of aria-controls keep the elements that exist (the links to hide the
 * columns of a table name a cell of every row, rows included that the page does not show); the fields of IOMAD
 * known to have an empty label get their name; the two menus of Moodle (the site, the page) get a name each, which
 * tells them apart (RGAA 12.6).
 *
 * The hidden choice « Clear my choice » of the questions with one answer is inert while it is hidden (WCAG 2.1.2,
 * 2.4.3): its button is checked before any answer, so the key Tab stopped on the first answer of the question.
 *
 * The list of the items chosen in an autocomplete field (tags, groups…) is a listbox; empty, it only held the text
 * « No selection », which is not an option (WCAG 1.3.1, 4.1.2). The text stays on the screen; the empty list tells
 * the screen readers that nothing is chosen.
 *
 * The handle that resizes the editor TinyMCE, moved with the arrow keys, has a name but no role in the version of
 * Moodle 5.0 (TinyMCE 7.7): it gets the role separator and its value, the height of the editor, as the later
 * versions of TinyMCE give it (WCAG 4.1.2).
 *
 * Found by an audit RGAA of a Moodle platform, defects of Moodle that the theme repairs in the page:
 * - the table of the month of the calendar has no title, and its headers do not say they head columns (RGAA 5.4,
 *   5.7): it gets the month as its caption, hidden, and its headers the scope col;
 * - the links made buttons (role="button") only obey the key Enter, a button obeys Space too (RGAA 7.3);
 * - a required field is only marked by an icon hidden from the screen readers, after its label (RGAA 11.10): its
 *   label gets the text « (required) », visible, and the note that explains the mark is read;
 * - some navigations have no name, which tells them apart from the others (RGAA 12.6): the letters that filter the
 *   people by their name, the choice of the page, the pages of a list, the course index;
 * - a field whose label is hidden, or which has a name only for the screen readers, says nothing to whoever sees it
 *   (RGAA 11.1): it gets its name as its title, shown on hover (test 11.1.3), the page unchanged;
 * - the lists that update the page as soon as a choice is made (the courses of the calendar, the choices of a page,
 *   the filters of « My courses » and of the timeline) did not say it (RGAA 7.4): they say it, to the screen readers;
 * - links that act as buttons, without leading anywhere (the arrows of the month and the events of the calendar, the
 *   actions on a course, the contact of the data protection officer), were said to be links (RGAA 7.1): they are
 *   buttons, and obey Space;
 * - status messages were not told to the screen readers (RGAA 7.5): the error of the login form, shown with the page;
 *   the number of messages selected; the first notification, whose region appeared with it; « No courses ».
 * Smaller defects found on the pages of Moodle, repaired the same way:
 * - the timer of a quiz with a time limit (role timer, which the screen readers do not read) is told at 5 minutes and at
 *   1 minute left (RGAA 13.1);
 * - a link that opens a new window says it, to the screen readers (WCAG 3.2.5);
 * - the fields of personal data of the profile give their purpose, for the filling in (RGAA 11.13);
 * - a picture of a user beside the name of the user says it once only (RGAA 1.2);
 * - a link whose title does not hold its visible text gets it first (RGAA 6.1); a link of a symbol only (« ◄ ») is
 *   named by its title;
 * - the sorted column of a table says its order (aria-sort);
 * - the menus and the parts to open say whether they are open from the start (RGAA 7.1);
 * - the buttons of the navigation of a quiz say the state of their question, not only by its colour (RGAA 3.1);
 * - the button « Flag question » is named by its visible text (WCAG 2.5.3);
 * - the frame of an H5P content has a title (RGAA 2.1);
 * - the same actions repeated in a table (« Edit », « Move ») say the line they act on (RGAA 6.1, 11.9);
 * - the links and buttons of an icon only are named by the title of their icon (RGAA 6.2);
 * - the charts are images named by their title, their data in the table that follows (RGAA 1.1, 1.3);
 * - the keyboard focus comes back to the control that opened a window when the page drew it again meanwhile, instead
 *   of the top of the page (RGAA 12.8);
 * - the actions of each message of a forum say the message they act on (WCAG 2.4.9);
 * - the abbreviations of the interface (H5P, SCORM, AICC, LTI, IA) are explained (WCAG 3.1.4);
 * - a form says how long the session lasts without activity, where nothing keeps it open (WCAG 2.2.6);
 * - the icons of the editor are ignored, its path is named, the attributes of presentation go, the identifiers
 *   repeated by the drawer of the messages are unique (RGAA 1.2, 12.6, 10.1, 8.2).
 *
 * @module     theme_epure/aria_repair
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ModalEvents from 'core/modal_events';

/**
 * Whether a field has a name for the screen readers.
 *
 * @param {HTMLElement} field The field.
 * @return {boolean}
 */
const named = (field) => field.getAttribute('aria-label')?.trim() || field.getAttribute('aria-labelledby')
    || [...(field.labels || [])].some((label) => label.textContent.trim());

/**
 * Repairs the attributes in a part of the page.
 *
 * @param {ParentNode} root The part of the page.
 * @param {Object<string, string>} names Names of the fields of IOMAD with an empty label, by their name.
 */
const repair = (root, names) => {
    root.querySelectorAll('[aria-hidden="1"]').forEach((element) => element.setAttribute('aria-hidden', 'true'));
    root.querySelectorAll('[aria-controls*=" "]').forEach((element) => {
        const ids = element.getAttribute('aria-controls').split(/\s+/).filter((id) => id && document.getElementById(id));
        if (ids.length) {
            element.setAttribute('aria-controls', ids.join(' '));
        } else {
            element.removeAttribute('aria-controls');
        }
    });
    Object.entries(names).forEach(([name, label]) => {
        root.querySelectorAll(`select[name="${name}"], input[name="${name}"]`).forEach((field) => {
            if (!named(field)) {
                field.setAttribute('aria-label', label);
            }
        });
    });
};

/**
 * Names the menus of Moodle that have none: the menu of the site and the menu of the page are both navigations.
 *
 * @param {{site: string, page: string}} menus Names of the menus.
 */
const nameMenus = (menus) => {
    document.querySelectorAll('nav.moremenu:not([aria-label]):not([aria-labelledby])').forEach((nav) => {
        const name = nav.closest('.primary-navigation') ? menus.site : menus.page;
        if (name) {
            nav.setAttribute('aria-label', name);
        }
    });
};

/**
 * Keeps the hidden choice « Clear my choice » of the questions out of the keyboard while it is hidden.
 */
const clearChoices = () => {
    const containers = document.querySelectorAll('.qtype_multichoice_clearchoice');
    if (!containers.length) {
        return;
    }
    const sync = (container) => {
        container.inert = container.getAttribute('aria-hidden') === 'true';
    };
    const observer = new MutationObserver((mutations) => mutations.forEach((mutation) => sync(mutation.target)));
    containers.forEach((container) => {
        sync(container);
        observer.observe(container, {attributes: true, attributeFilter: ['aria-hidden']});
    });
};

/**
 * Hides the text « No selection » of the empty lists of the autocomplete fields from the screen readers.
 */
const emptySelections = () => document.querySelectorAll(
    '.form-autocomplete-selection[role="listbox"] > :not([role]):not([aria-hidden])'
).forEach((element) => element.setAttribute('aria-hidden', 'true'));

/**
 * Gives the handles that resize the editors their role and their value, the height of the editor.
 */
const resizeHandles = () => document.querySelectorAll('.tox-statusbar__resize-handle:not([role])').forEach((handle) => {
    const value = () => {
        const editor = handle.closest('.tox-tinymce');
        if (editor) {
            handle.setAttribute('aria-valuenow', String(Math.round(editor.offsetHeight)));
        }
    };
    handle.setAttribute('role', 'separator');
    value();
    handle.addEventListener('keyup', value);
    handle.addEventListener('pointerup', value);
});

/**
 * Gives the tables of the calendar their caption, the name of the month, and their headers their scope.
 */
const calendars = () => document.querySelectorAll('table.calendartable').forEach((table) => {
    table.querySelectorAll('thead th:not([scope])').forEach((header) => header.setAttribute('scope', 'col'));
    if (table.caption) {
        return;
    }
    const month = table.closest('.calendarwrapper')?.querySelector('.calendar-controls .current, h2.current');
    const text = month?.textContent.trim();
    if (text) {
        table.createCaption().className = 'sr-only visually-hidden';
        table.caption.textContent = text;
    }
});

/**
 * Marks the required fields of the forms of Moodle in the text of their label, and lets the note that explains the
 * mark be read.
 *
 * @param {string} text The mark, « (required) ».
 */
const requiredFields = (text) => {
    if (!text) {
        return;
    }
    document.querySelectorAll('.fitem .form-label-addon > .text-danger').forEach((icon) => {
        const item = icon.closest('.fitem');
        const label = item.querySelector('.col-form-label label, .col-form-label .d-inline-block, .col-form-label p');
        if (!label || label.querySelector('.epure-required')) {
            return;
        }
        const mark = document.createElement('span');
        mark.className = 'epure-required';
        mark.textContent = ' ' + text;
        label.append(mark);
    });
    document.querySelectorAll('.fdescription.required[aria-hidden="true"]').forEach((note) => {
        note.removeAttribute('aria-hidden');
    });
};

/**
 * Names the navigations of Moodle that have none.
 *
 * @param {Object<string, string>} texts Names: paging (the pages of a list), choice (the choice of the page),
 *     courseindex.
 */
const nameNavigations = (texts) => {
    document.querySelectorAll('nav:not([aria-label]):not([aria-labelledby])').forEach((nav) => {
        let name = '';
        if (nav.classList.contains('initialbargroups')) {
            name = nav.closest('.initialbar')?.querySelector('.initialbarlabel')?.textContent.trim();
        } else if (nav.matches('[data-region="paging-bar"], .pagination')) {
            name = texts.paging;
        } else if (nav.classList.contains('tertiary-navigation-selector')) {
            name = texts.choice;
        } else if (nav.id === 'courseindex') {
            name = texts.courseindex;
        }
        if (name) {
            nav.setAttribute('aria-label', name);
        }
    });
};

/**
 * Lets the key Space press the links made buttons, as it presses the buttons. The handlers of the page come first
 * (this one listens on the window): a link they have already pressed, or which they gave the « activate » event of
 * Moodle, is left alone.
 *
 * @param {KeyboardEvent} event The key pressed.
 */
const spaceKey = (event) => {
    if (event.key !== ' ' || event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey
        || Object.keys(event).some((key) => key.startsWith('triggeredCustom_'))) {
        return;
    }
    const link = event.target.closest?.('a[role="button"]');
    if (link && link === event.target) {
        event.preventDefault();
        link.click();
    }
};

/**
 * The region of Épure that tells the status messages to the screen readers, there from the start of the page (a region
 * added with its message is not read).
 *
 * @return {HTMLElement}
 */
const statusRegion = () => {
    let region = document.getElementById('epure-status-repair');
    if (!region) {
        region = document.createElement('div');
        region.id = 'epure-status-repair';
        region.className = 'sr-only visually-hidden';
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', 'polite');
        document.body.append(region);
    }
    return region;
};

/**
 * Tells a status message to the screen readers, a moment later (so that the region is read as changed).
 *
 * @param {string} text Message.
 */
const announce = (text) => {
    const message = (text || '').replace(/\s+/g, ' ').trim();
    if (!message) {
        return;
    }
    const region = statusRegion();
    region.textContent = '';
    window.setTimeout(() => {
        region.textContent = message;
    }, 150);
};

/**
 * Whether a label is hidden from those who see the page.
 *
 * @param {HTMLLabelElement} label Label.
 * @return {boolean}
 */
const hiddenLabel = (label) => {
    const hidden = '.sr-only, .visually-hidden, .accesshide';
    if (label.matches(hidden)) {
        return true;
    }
    const text = label.textContent.trim();
    return text !== '' && [...label.querySelectorAll(hidden)].map((part) => part.textContent.trim()).join(' ').trim() === text;
};

/**
 * Gives a title, its name, to the fields whose label is hidden or which have a name for the screen readers only.
 */
const fieldTitles = () => document.querySelectorAll(
    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([title]), '
        + 'select:not([title]), textarea:not([title])'
).forEach((field) => {
    const labels = [...(field.labels || [])];
    let name = '';
    if (labels.length && labels.every(hiddenLabel)) {
        name = labels.map((label) => label.textContent.trim()).join(' ');
    } else if (!labels.length) {
        name = field.getAttribute('aria-label')?.trim() || '';
        const ids = field.getAttribute('aria-labelledby');
        if (!name && ids) {
            name = ids.split(/\s+/).map((id) => document.getElementById(id)?.textContent.trim() || '').join(' ').trim();
        }
    }
    if (name) {
        field.setAttribute('title', name.replace(/\s+/g, ' '));
    }
});

/**
 * Says that the lists which update the page as soon as a choice is made do so (RGAA 7.4).
 *
 * @param {string} list Note of the lists that update a list of the page.
 * @param {string} page Note of the lists that change the page.
 */
const contextChanges = (list, page) => {
    const note = (id, text) => {
        if (!text || document.getElementById(id)) {
            return;
        }
        const element = document.createElement('span');
        element.id = id;
        element.className = 'sr-only visually-hidden';
        element.textContent = text;
        document.body.append(element);
    };
    note('epure-note-updates', list);
    note('epure-note-changes', page);
    const describe = (selector, id) => document.querySelectorAll(selector).forEach((control) => {
        const ids = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (document.getElementById(id) && !ids.includes(id)) {
            control.setAttribute('aria-describedby', [...ids, id].join(' '));
        }
    });
    describe('.block_myoverview [data-region="filter"] .dropdown-toggle, .block_timeline .dropdown-toggle, '
        + 'select.cal_courses_flt', 'epure-note-updates');
    describe('.singleselect select, .urlselect select', 'epure-note-changes');
};

/**
 * Makes buttons of the links that act as buttons, without leading anywhere (RGAA 7.1).
 */
const linkButtons = () => document.querySelectorAll(
    'a[href="#"][data-action]:not([role]), a[data-action="view-event"]:not([role]), '
        + '.calendar-controls a.arrow_link:not([role]), a[data-action="contactdpo"]:not([role])'
).forEach((link) => link.setAttribute('role', 'button'));

/**
 * Tells the status messages that the screen readers miss (RGAA 7.5): the number of messages selected, the first
 * notification (its region appears with it), « No courses » once the courses are filtered.
 */
const statusMessages = () => {
    const watched = new WeakSet();
    const watch = (element, text) => {
        if (watched.has(element)) {
            return;
        }
        watched.add(element);
        new MutationObserver(() => announce(text(element))).observe(element, {childList: true, characterData: true,
            subtree: true});
    };
    const run = () => {
        document.querySelectorAll('[data-region="message-selected-court"]').forEach((count) => {
            watch(count, (element) => element.parentElement?.textContent || '');
        });
        document.querySelectorAll('.block_myoverview [data-region="courses-view"]').forEach((view) => {
            watch(view, (element) => {
                const empty = element.querySelector('.text-center, [data-region="empty-message"], .empty-placeholder');
                return empty && !element.querySelector('[data-region="course-content"], .course-listitem, .course-card')
                    ? empty.textContent : '';
            });
        });
    };
    run();
    // The region of the notifications, added with the first one: its message is told once it is in.
    new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === 1 && node.classList.contains('toast-wrapper')) {
            window.setTimeout(() => announce(node.textContent), 300);
        }
    }))).observe(document.body, {childList: true});
    return run;
};

/**
 * The visible text of an element, its spaces made one.
 *
 * @param {HTMLElement} element Element.
 * @return {string}
 */
const visibleText = (element) => (element.innerText || element.textContent || '').replace(/\s+/g, ' ').trim();

/**
 * Tells the time left of the quiz at 5 minutes and at 1 minute (its timer has role timer, which is not read).
 *
 * @param {string} five Message at 5 minutes.
 * @param {string} one Message at 1 minute.
 */
const quizTimer = (five, one) => {
    const left = document.getElementById('quiz-time-left');
    if (!left || left.dataset.epureTimer) {
        return;
    }
    left.dataset.epureTimer = '1';
    const told = new Set();
    const check = () => {
        const parts = left.textContent.trim().split(':').map(Number);
        if (parts.some(Number.isNaN) || !parts.length) {
            return;
        }
        const seconds = parts.reduce((total, part) => total * 60 + part, 0);
        [[300, five], [60, one]].forEach(([limit, text]) => {
            if (seconds <= limit && seconds > limit - 30 && !told.has(limit) && text) {
                told.add(limit);
                announce(text);
            }
        });
    };
    new MutationObserver(check).observe(left, {childList: true, characterData: true, subtree: true});
};

/**
 * Says to the screen readers that a link opens a new window.
 *
 * @param {string} text « (opens in a new window) ».
 */
const newWindows = (text) => {
    if (!text) {
        return;
    }
    document.querySelectorAll('a[target="_blank"]:not([data-epure-window])').forEach((link) => {
        link.dataset.epureWindow = '1';
        const name = (link.getAttribute('aria-label') || visibleText(link)).toLowerCase();
        if (name.includes(text.replace(/[()]/g, '').trim().toLowerCase())) {
            return;
        }
        if (link.hasAttribute('aria-label')) {
            link.setAttribute('aria-label', link.getAttribute('aria-label') + ' ' + text);
            return;
        }
        const note = document.createElement('span');
        note.className = 'sr-only visually-hidden';
        note.textContent = ' ' + text;
        link.append(note);
    });
};

/**
 * The purpose of the fields of personal data, by their name (RGAA 11.13).
 *
 * @type {Object<string, string>}
 */
const PURPOSES = {firstname: 'given-name', lastname: 'family-name', middlename: 'additional-name', email: 'email',
    city: 'address-level2', country: 'country', phone1: 'tel', phone2: 'tel', address: 'street-address',
    institution: 'organization', url: 'url'};

/**
 * Gives the fields of personal data of the forms of the profile their purpose.
 */
const purposes = () => document.querySelectorAll('form.mform input:not([autocomplete]), form.mform select:not([autocomplete])')
    .forEach((field) => {
        const purpose = PURPOSES[field.name];
        if (purpose && (field.type !== 'password')) {
            field.setAttribute('autocomplete', purpose);
        }
    });

/**
 * Empties the text alternative of a picture of a user that repeats the name beside it (RGAA 1.2).
 */
const userPictures = () => document.querySelectorAll('img.userpicture[alt]:not([alt=""])').forEach((picture) => {
    const holder = picture.closest('a, td, th, li') || picture.parentElement;
    const alt = picture.alt.trim().toLowerCase();
    if (holder && alt && visibleText(holder).toLowerCase().includes(alt)) {
        picture.alt = '';
    }
});

/**
 * Puts the visible text of a link first in its title; names by its title a link of a symbol only (RGAA 6.1).
 */
const linkTitles = () => document.querySelectorAll(
    'a[href][title]:not([aria-label]):not([aria-labelledby]):not(.qnbutton)'
).forEach((link) => {
    const text = visibleText(link);
    const title = link.title.trim();
    if (!text || !title || title.toLowerCase().includes(text.toLowerCase())) {
        return;
    }
    if (!/[\p{L}\p{N}]/u.test(text)) {
        link.setAttribute('aria-label', title);
    } else {
        link.title = `${text} – ${title}`;
    }
});

/**
 * Says the order of the sorted column of a table (aria-sort).
 */
const sortedColumns = () => document.querySelectorAll('th:not([aria-sort])').forEach((header) => {
    if (!header.querySelector('a[data-sortable], a[href*="tsort="], a[href*="sort="]')) {
        return;
    }
    const icon = header.querySelector('.icon[class*="fa-arrow-"], .icon[class*="fa-sort-"], i[class*="fa-sort-"]');
    if (!icon) {
        return;
    }
    const down = /(arrow-down|sort-desc|sort-down|amount-desc)/.test(icon.className);
    const up = /(arrow-up|sort-asc|sort-up|amount-asc)/.test(icon.className);
    if (down || up) {
        header.setAttribute('aria-sort', down ? 'descending' : 'ascending');
    }
});

/**
 * Says whether the menus and the parts to open are open, from the start (RGAA 7.1).
 */
const toggles = () => document.querySelectorAll(
    '[data-toggle="dropdown"]:not([aria-expanded]), [data-bs-toggle="dropdown"]:not([aria-expanded]), '
        + '[data-toggle="collapse"]:not([aria-expanded]), [data-bs-toggle="collapse"]:not([aria-expanded])'
).forEach((toggle) => {
    let open = false;
    if (/dropdown/.test(toggle.dataset.toggle || toggle.dataset.bsToggle || '')) {
        open = toggle.classList.contains('show');
    } else {
        const target = toggle.dataset.target || toggle.dataset.bsTarget || toggle.getAttribute('href') || '';
        try {
            const part = (target.startsWith('#') || target.startsWith('.')) ? document.querySelector(target) : null;
            open = !!part?.classList.contains('show');
        } catch (e) {
            // Expected: an address that is not a selector; the part is closed.
            open = false;
        }
    }
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
});

/**
 * Names the buttons of the navigation of a quiz with the state of their question (RGAA 3.1), and the button
 * « Flag question » by its visible text (WCAG 2.5.3).
 */
const quizNavigation = () => {
    document.querySelectorAll('.qn_buttons .qnbutton[title]').forEach((button) => {
        // Its state from its title (« Question 1 - Not answered »), then the page shown and the flag, as Moodle marks them.
        const parts = [button.title.trim()];
        const flag = visibleText(button.querySelector('.flagstate') || document.createElement('span'));
        const hidden = [...button.querySelectorAll('.accesshide')].pop();
        const page = hidden ? hidden.textContent.replace(flag, '').replace(/\s+/g, ' ').trim() : '';
        if (button.classList.contains('thispage') && page) {
            parts.push(page);
        }
        if (flag) {
            parts.push(flag);
        }
        const name = parts.join(', ');
        if (button.getAttribute('aria-label') !== name) {
            button.setAttribute('aria-label', name);
        }
    });
    document.querySelectorAll('.questionflag [role="button"][aria-label]').forEach((flag) => {
        const text = visibleText(flag);
        if (text && flag.getAttribute('aria-label') !== text) {
            flag.setAttribute('aria-label', text);
        }
    });
};

/**
 * Gives the frames of the H5P contents a title (RGAA 2.1).
 *
 * @param {string} fallback Name when the page has no heading.
 */
const h5pFrames = (fallback) => document.querySelectorAll(
    'iframe.h5p-player:not([title]), iframe[src*="/h5p/embed.php"]:not([title]), iframe.h5p-iframe:not([title])'
).forEach((frame) => {
    const heading = document.querySelector('#region-main h1, .page-header-headings h1, h1');
    frame.title = (heading ? visibleText(heading) : '') || fallback;
});

/**
 * Adds the line they act on to the same actions repeated in a table (RGAA 6.1, 11.9).
 */
const tableActions = () => document.querySelectorAll('table').forEach((table) => {
    const controls = [...table.querySelectorAll('a[href], button')].filter((control) => !control.dataset.epureLine
        && !control.hasAttribute('aria-labelledby'));
    const nameOf = (control) => (control.getAttribute('aria-label') || visibleText(control)
        || [...control.querySelectorAll('img[alt]')].map((image) => image.alt).join(' ') || control.title || '').trim();
    const byName = new Map();
    controls.forEach((control) => {
        const name = nameOf(control);
        if (name && name.length <= 30) {
            byName.set(name, [...(byName.get(name) || []), control]);
        }
    });
    byName.forEach((same, name) => {
        if (same.length < 2) {
            return;
        }
        same.forEach((control) => {
            const row = control.closest('tr');
            const cell = control.closest('td, th');
            const head = row && [...row.cells].find((other) => other !== cell && visibleText(other)
                && (other.tagName === 'TH' || other.cellIndex <= 1));
            const line = head ? visibleText(head).slice(0, 80) : '';
            control.dataset.epureLine = '1';
            if (line && !name.toLowerCase().includes(line.toLowerCase())) {
                control.setAttribute('aria-label', `${name} : ${line}`);
            }
        });
    });
});

/**
 * The actions of each message of a forum (« Edit », « Delete », « Reply »…), the same from one message to the next:
 * each says the message it acts on, its subject and its author (WCAG 2.4.9).
 */
const postActions = () => document.querySelectorAll('[data-region="post-actions-container"]').forEach((actions) => {
    const post = actions.closest('[data-region-content="forum-post-core"], article');
    const subject = post?.querySelector('[data-region-content="forum-post-core-subject"]');
    if (!subject) {
        return;
    }
    // The subject and the author: the first link of the line under the subject, else the line.
    const byline = subject.parentElement.querySelector('.mb-3');
    const author = byline ? byline.querySelector('a') || byline : null;
    const about = [visibleText(subject), author ? visibleText(author) : ''].filter(Boolean).join(', ').slice(0, 120);
    actions.querySelectorAll('a[href], button').forEach((control) => {
        if (control.dataset.epurePost || control.hasAttribute('aria-label') || control.hasAttribute('aria-labelledby')) {
            return;
        }
        const name = visibleText(control);
        control.dataset.epurePost = '1';
        if (name && about) {
            control.setAttribute('aria-label', `${name} : ${about}`);
        }
    });
});

/** @var {string} The parts of the interface whose abbreviations are explained: not the texts of the courses. */
const INTERFACE = '.breadcrumb, .page-header-headings, .modchooser, .optionname, .activityname, .instancename, '
    + '.nav-link, .dropdown-item, legend, label, th, .col-form-label, .activity-header, .page-context-header, '
    + 'table td:first-child, .form-description';

/** @var {string} The texts written by the teachers and the learners, left as they are written. */
const CONTENTS = '[contenteditable="true"], .text_to_html, .tox, .activity-description, .summarytext, .course-description, '
    + '.post-content-container, .box.contents, .book_content, .generalbox .no-overflow, #intro';

/**
 * The abbreviations of the interface of Moodle (H5P, SCORM, AICC, LTI, IA), explained the first time they appear in
 * each part of the interface (WCAG 3.1.4): an element abbr with their full form as its title.
 *
 * @param {Object<string, string>} abbreviations Full forms, by abbreviation, in the language of the page.
 */
const abbreviations = (abbreviations) => {
    const words = Object.keys(abbreviations || {});
    if (!words.length) {
        return;
    }
    const pattern = new RegExp(`(^|[^\\p{L}\\p{N}])(${words.join('|')})(?=$|[^\\p{L}\\p{N}])`, 'u');
    document.querySelectorAll(INTERFACE).forEach((part) => {
        if (part.dataset.epureAbbr || part.closest(CONTENTS)) {
            return;
        }
        part.dataset.epureAbbr = '1';
        const walker = document.createTreeWalker(part, NodeFilter.SHOW_TEXT);
        const done = new Set();
        const nodes = [];
        for (let node = walker.nextNode(); node; node = walker.nextNode()) {
            if (!node.parentElement.closest('abbr, script, style, .sr-only, .visually-hidden')) {
                nodes.push(node);
            }
        }
        nodes.forEach((node) => {
            const match = node.nodeValue.match(pattern);
            if (!match || done.has(match[2])) {
                return;
            }
            done.add(match[2]);
            const start = match.index + match[1].length;
            const word = node.splitText(start);
            word.splitText(match[2].length);
            const abbr = document.createElement('abbr');
            abbr.title = abbreviations[match[2]];
            word.replaceWith(abbr);
            abbr.append(word);
        });
    });
};

/**
 * Before the buttons of a form, how long the session lasts without activity, when nothing keeps it open (WCAG 2.2.6):
 * whoever takes their time knows when to save. Once on the page, under the first form that saves something.
 *
 * @param {string} text The note, empty where the session is kept open.
 */
const sessionNote = (text) => {
    if (!text || document.querySelector('.epure-session-note')) {
        return;
    }
    const buttons = document.querySelector('#region-main form.mform [id^="fgroup_id_buttonar"], #region-main form.mform '
        + '.fitem_actionbuttons');
    if (!buttons) {
        return;
    }
    const note = document.createElement('p');
    note.className = 'epure-session-note text-muted small';
    note.textContent = text;
    buttons.before(note);
};

/**
 * What the RGAA asks of the code of the page, repaired where the interface of Moodle and of its editor writes it
 * otherwise:
 * - the icons of the editor TinyMCE (svg), drawn in buttons that have their name, are ignored by the screen readers
 *   (RGAA 1.2); the path of the element written, a navigation, gets a name (RGAA 12.6); its frame, without a title
 *   in TinyMCE 7, gets the label of its field (RGAA 2.1);
 * - the attributes of presentation (frameborder of a frame, cellspacing, cellpadding and border of the tables of the
 *   interface) go, the styles do the same (RGAA 10.1); the tables written by the teachers keep theirs;
 * - on the page of the messages, the drawer of the messages, hidden, repeats the identifiers of the page (RGAA 8.2):
 *   its own get a suffix, and the attributes of the drawer that name them follow;
 * - the sections given the role region in a list (course format Collapsed Topics) are its items again (RGAA 9.3);
 * - the tables of the summary of the retention of the data get a caption and the headers of their rows (RGAA 5.4,
 *   5.6); the user report of the gradebook gets a caption and a summary (RGAA 5.1, 5.4); a page without a title of
 *   level 1 gets one, for the screen readers (RGAA 9.1).
 *
 * @param {Object<string, string>} texts The name of the path of the element of the editor (editorpath), of its frame
 *     without a label (editorframe), the summary of the user report (gradesummary, its columns as COLUMNS).
 */
/**
 * Repairs of the pages of other plugins (formats of courses, activities), found by the continuous integration:
 * - a list with other children than its items (Dialogue, Buttons) is no longer given as a list (RGAA 9.3);
 * - a select without a name takes the text of its empty first choice, « Choose… » (RGAA 11.1);
 * - the button « Collapse all » left empty (Buttons) takes the name of the core button (RGAA 6.2);
 * - a progress bar without a name (Edwiser, Designer) is named by its text, else « Progress »;
 * - the items of a course index drawn as a tree without their role (Designer) are items of the tree;
 * - a field without a name (Organizer) is named by its placeholder;
 * - a frame without a title (PDF Protect) takes the title of the page (RGAA 2.1);
 * - an icon named with aria-label but without a role (Organizer) becomes an image, for its name to be read;
 * - a table that scrolls across, without any control in it, is reached with the keyboard to scroll it (WCAG 2.1.1),
 *   named by its caption.
 *
 * @param {Object<string, string>} texts The names of « Collapse all » and « Expand all » (collapseall, expandall), of a
 *     table that scrolls (scrolltable) and of a progress bar (progress).
 */
const pluginRepairs = (texts) => {
    document.querySelectorAll(':is(ul, ol):not([role]):has(> :not(li, script, template))').forEach((list) => {
        if (list.closest(CONTENTS)) {
            return;
        }
        list.setAttribute('role', 'none');
        Array.from(list.children).filter((child) => child.tagName === 'LI')
            .forEach((item) => item.setAttribute('role', 'none'));
    });
    document.querySelectorAll('select:not([aria-label]):not([aria-labelledby]):not([title])').forEach((select) => {
        const first = select.options[0];
        if (!select.labels?.length && first && first.value === '' && first.textContent.trim()) {
            select.setAttribute('aria-label', first.textContent.trim());
        }
    });
    // A progress bar without a name (Edwiser): « Progress », its value given by the bar itself (RGAA 11.1).
    document.querySelectorAll('[role="progressbar"]:not([aria-label]):not([aria-labelledby]):not([title])')
        .forEach((bar) => bar.setAttribute('aria-label', visibleText(bar) || texts.progress));
    // The items of a course index drawn as a tree without their role (Designer): items of the tree (RGAA 7.1).
    document.querySelectorAll('[role="tree"] li:not([role]), [role="group"] > li:not([role])')
        .forEach((item) => item.setAttribute('role', 'treeitem'));
    // A field of a form without a name (the filter of Organizer): its placeholder, else its title (RGAA 11.1).
    document.querySelectorAll('input:not([type="hidden"]):not([aria-label]):not([aria-labelledby]):not([title])')
        .forEach((field) => {
            if (!field.labels?.length && field.placeholder) {
                field.setAttribute('aria-label', field.placeholder);
            }
        });
    // An icon named with aria-label but without a role (Organizer): an image, for its name to be read (RGAA 1.1).
    document.querySelectorAll(':is(i, span)[aria-label]:not([role])').forEach((icon) => icon.setAttribute('role', 'img'));
    document.querySelectorAll('#collapsesections:not([aria-label])').forEach((button) => {
        if (!visibleText(button)) {
            button.setAttribute('aria-label',
                button.getAttribute('aria-expanded') === 'false' ? texts.expandall : texts.collapseall);
        }
    });
    // A frame of an activity without a title (PDF Protect): the title of the page, the name of the activity (RGAA 2.1).
    const heading = document.querySelector('#page h1, h1');
    document.querySelectorAll('iframe:not([title]):not([aria-label]):not([aria-labelledby])').forEach((frame) => {
        if (heading && visibleText(heading)) {
            frame.title = visibleText(heading);
        }
    });
    scrollTables(texts);
};

/**
 * The tables that scroll across without any control in them: reached with the keyboard, named by their caption.
 * Again when the size of the text changes (the panel « Aa ») or the window.
 *
 * @param {Object<string, string>} texts The name of a table that scrolls (scrolltable).
 */
const scrollTables = (texts) => {
    document.querySelectorAll('.table-responsive:not([tabindex]), .no-overflow:not([tabindex])').forEach((box) => {
        if (box.scrollWidth > box.clientWidth + 1
                && !box.querySelector('a[href], button, input, select, textarea, [tabindex], .table-responsive')) {
            const caption = box.querySelector('caption');
            box.tabIndex = 0;
            box.setAttribute('role', 'region');
            box.setAttribute('aria-label', caption ? visibleText(caption) : texts.scrolltable);
        }
    });
};

const codeRepairs = (texts) => {
    // The sections of some course formats (Collapsed Topics) are items of a list given the role region: the list loses
    // its items (RGAA 9.3). Items of the list again, as the sections of the formats of Moodle; their name stays.
    document.querySelectorAll(':is(ul, ol) > li[role="region"]').forEach((item) => item.removeAttribute('role'));
    document.querySelectorAll('.tox svg:not([aria-hidden])').forEach((svg) => svg.setAttribute('aria-hidden', 'true'));
    document.querySelectorAll('.tox-statusbar__path[role="navigation"]:not([aria-label])')
        .forEach((nav) => nav.setAttribute('aria-label', texts.editorpath));
    document.querySelectorAll('iframe[frameborder]').forEach((frame) => frame.removeAttribute('frameborder'));
    // The frame of the editor without a title (TinyMCE 7, Moodle 5.x, names it otherwise): the RGAA asks for its title
    // (2.1), the label of the field it edits, else its other name.
    document.querySelectorAll('iframe.tox-edit-area__iframe:not([title])').forEach((frame) => {
        const field = frame.id.replace(/_ifr$/, '');
        const label = field && document.querySelector(`label[for="${CSS.escape(field)}"]`);
        const named = (frame.getAttribute('aria-labelledby') || '').split(/\s+/)
            .map((id) => document.getElementById(id)?.textContent.trim() || '').filter(Boolean).join(' ');
        const name = (label ? visibleText(label) : '') || named || texts.editorframe;
        if (name) {
            frame.title = name;
        }
    });
    document.querySelectorAll('table[cellspacing], table[cellpadding], table[border]').forEach((table) => {
        if (!table.closest(CONTENTS)) {
            ['cellspacing', 'cellpadding', 'border'].forEach((name) => table.removeAttribute(name));
        }
    });
    // The summary of the retention of the data: the title of each table in a header cell spanning the table, the names
    // of the lawful bases in data cells (RGAA 5.4, 5.6): a caption, and the headers of their rows.
    document.querySelectorAll('#page-admin-tool-dataprivacy-summary #region-main table:not([data-epure-table])')
        .forEach((table) => {
            table.dataset.epureTable = '1';
            const head = table.tHead;
            const cells = head?.rows.length === 1 ? head.rows[0].cells : [];
            if (cells.length === 1 && cells[0].colSpan > 1 && !table.caption) {
                table.createCaption().textContent = visibleText(cells[0]);
                head.remove();
            }
            [...table.tBodies].forEach((body) => [...body.rows].forEach((row) => {
                const first = row.cells[0];
                if (first?.tagName === 'TD' && row.cells.length > 1) {
                    const header = document.createElement('th');
                    header.scope = 'row';
                    header.append(...first.childNodes);
                    first.replaceWith(header);
                }
            }));
        });
    // The user report of the gradebook, a complex table (categories over several rows, cells tied to their headers),
    // without a title nor a summary (RGAA 5.1, 5.4): the title of the page as its caption, its structure as its summary.
    document.querySelectorAll('table.user-grade:not([data-epure-table])').forEach((table) => {
        table.dataset.epureTable = '1';
        if (!table.caption) {
            const caption = table.createCaption();
            caption.className = 'sr-only visually-hidden';
            // The title of the page without the name of the site: the report and its course.
            const parts = document.title.split(' | ');
            caption.textContent = (parts.length > 1 ? parts.slice(0, -1) : parts).join(', ');
        }
        const columns = [...table.querySelectorAll('thead th, tr:first-child th[scope="col"]')].map(visibleText)
            .filter(Boolean);
        if (texts.gradesummary && columns.length && !table.hasAttribute('aria-describedby')) {
            const summary = document.createElement('p');
            summary.id = `epure-grade-summary-${Math.random().toString(36).slice(2)}`;
            summary.className = 'sr-only visually-hidden';
            summary.textContent = texts.gradesummary.replace('COLUMNS', [...new Set(columns)].join(', '));
            table.before(summary);
            table.setAttribute('aria-describedby', summary.id);
        }
    });
    // A page without a title of level 1 (the forgotten password): the title of the page, for the screen readers, at
    // the start of its content (RGAA 9.1).
    const main = document.querySelector('[role="main"], main, #region-main');
    if (main && !document.querySelector('h1')) {
        const title = document.createElement('h1');
        title.className = 'sr-only visually-hidden';
        title.textContent = document.title.split(' | ')[0];
        main.prepend(title);
    }
    const drawer = document.querySelector('[data-region="message-drawer"]');
    const page = document.querySelector('#region-main [data-region="message-drawer"], #region-main .message-app');
    if (drawer && page && !drawer.dataset.epureIds && !drawer.closest('#region-main')) {
        drawer.dataset.epureIds = '1';
        const renamed = new Map();
        drawer.querySelectorAll('[id]').forEach((element) => {
            if (page.querySelector(`#${CSS.escape(element.id)}`)) {
                renamed.set(element.id, `${element.id}-drawer`);
                element.id = `${element.id}-drawer`;
            }
        });
        const follow = (value) => value.split(/\s+/).map((id) => renamed.get(id) || id).join(' ');
        drawer.querySelectorAll('[for], [aria-labelledby], [aria-describedby], [aria-controls], [aria-owns]')
            .forEach((element) => ['for', 'aria-labelledby', 'aria-describedby', 'aria-controls', 'aria-owns']
                .filter((name) => element.hasAttribute(name))
                .forEach((name) => element.setAttribute(name, follow(element.getAttribute(name)))));
    }
};

/**
 * Names the links and buttons of an icon only by the title of their icon (RGAA 6.2).
 */
const iconControls = () => document.querySelectorAll('a[href], button, [role="button"]').forEach((control) => {
    if (control.hasAttribute('aria-label') || control.hasAttribute('aria-labelledby') || visibleText(control)
        || control.querySelector('img[alt]:not([alt=""])')) {
        return;
    }
    const titled = control.title ? control : control.querySelector('[title]');
    if (titled && titled.title.trim()) {
        control.setAttribute('aria-label', titled.title.trim());
    }
});

/**
 * The charts of Moodle (reports, statistics): an image named by its title, its data in the table that follows, instead
 * of a role presentation that hides the canvas from the screen readers (RGAA 1.1, 1.3).
 *
 * @param {string} chart Name of a chart without a title; with one, the chart and its title (TITLE).
 */
const charts = (chart) => document.querySelectorAll('.chart-area .chart-image').forEach((image) => {
    const area = image.closest('.chart-area');
    const caption = area.querySelector('.chart-table-data caption');
    // Its title, else the heading just before it (the reports of the quizzes write it above the chart).
    const before = [area.previousElementSibling, area.parentElement?.previousElementSibling]
        .find((element) => element && /^H[2-6]$/.test(element.tagName));
    const title = (caption ? visibleText(caption) || caption.textContent.trim() : '') || (before ? visibleText(before) : '');
    const label = title ? chart.replace('TITLE', title) : chart.replace(/\s*\W*\s*TITLE\s*/, '');
    if (image.getAttribute('aria-label') !== label) {
        image.setAttribute('role', 'img');
        image.setAttribute('aria-label', label);
    }
});

/** @var {Object|null} The control that had the keyboard focus before the last window opened, and how to find it again. */
let opener = null;

/**
 * How to find a control again once the page updated it: by its id, else by its action and what it acts on.
 *
 * @param {Element} control Control.
 * @returns {string|null} Selector.
 */
const findAgain = (control) => {
    if (control.id) {
        return `#${CSS.escape(control.id)}`;
    }
    const attributes = ['data-action', 'data-id', 'data-itemid', 'data-cmid', 'data-userid', 'data-region']
        .filter((name) => control.hasAttribute(name))
        .map((name) => `[${name}="${CSS.escape(control.getAttribute(name))}"]`);
    return attributes.length ? control.tagName.toLowerCase() + attributes.join('') : null;
};

/**
 * The keyboard focus back after a window of Moodle closes (RGAA 7.3, 12.8): Moodle gives it back to the control that
 * opened it, but when the page replaced this control meanwhile (an action that reloads a part of the page), the focus
 * falls to the top of the page. Then, to the same control drawn again, else to the title of the page.
 */
const modalFocus = () => {
    document.addEventListener('focusin', (event) => {
        const target = event.target;
        if (target instanceof Element && !target.closest('.modal, [role="dialog"], .moodle-dialogue')) {
            opener = {element: target, selector: findAgain(target)};
        }
    });
    const back = () => window.setTimeout(() => {
        const active = document.activeElement;
        if (!opener || (active && active !== document.body && active !== document.documentElement)
                || document.querySelector('.modal.show, .moodle-dialogue:not(.moodle-dialogue-hidden)')) {
            return;
        }
        let target = opener.element.isConnected ? opener.element : null;
        if (!target && opener.selector) {
            target = [...document.querySelectorAll(opener.selector)].find((element) => element.getClientRects().length);
        }
        if (!target) {
            target = document.querySelector('#region-main h1, #page-header h1, h1');
            if (target && !target.hasAttribute('tabindex')) {
                target.setAttribute('tabindex', '-1');
            }
        }
        target?.focus();
    }, 300);
    // The windows of Moodle say they closed on their root (core/modal_events), with jQuery: the event bubbles.
    $(document).on(ModalEvents.hidden, back);
};

/**
 * The repairs of what Moodle draws after the page: now, and each time something is added to the page.
 *
 * @param {Object<string, string>} texts The mark of the required fields, the names of the navigations.
 */
const later = (texts) => {
    const watchStatus = statusMessages();
    const run = () => {
        emptySelections();
        resizeHandles();
        calendars();
        requiredFields(texts.required);
        nameNavigations(texts);
        fieldTitles();
        contextChanges(texts.updates, texts.changes);
        linkButtons();
        watchStatus();
        quizTimer(texts.fiveminutes, texts.oneminute);
        newWindows(texts.newwindow);
        purposes();
        userPictures();
        linkTitles();
        sortedColumns();
        toggles();
        quizNavigation();
        h5pFrames(texts.h5p);
        tableActions();
        iconControls();
        charts(texts.chart);
        postActions();
        abbreviations(texts.abbreviations);
        sessionNote(texts.session);
        codeRepairs(texts);
        pluginRepairs(texts);
    };
    run();
    // The size of the text changed in « Aa », or the window: the tables that scroll now.
    new MutationObserver(() => scrollTables(texts)).observe(document.documentElement,
        {attributes: true, attributeFilter: ['class']});
    window.addEventListener('resize', () => scrollTables(texts));
    new MutationObserver((mutations) => {
        if (mutations.some((mutation) => mutation.addedNodes.length)) {
            run();
        }
    }).observe(document.body, {childList: true, subtree: true});
};

/**
 * Repairs the page, and again once it is loaded.
 *
 * @param {Object<string, string>} names Names of the fields of IOMAD with an empty label, by their name.
 * @param {{site: string, page: string}} menus Names of the menu of the site and of the menu of the page.
 * @param {Object<string, string>} texts The mark of the required fields (required), the names of the navigations
 *     (paging, choice, courseindex), the notes of the lists that update the page (updates, changes), the time left
 *     of a quiz (fiveminutes, oneminute), the note of the links to a new window (newwindow), the name of the frames
 *     of H5P without a heading (h5p), the name of a chart (chart), the full forms of the abbreviations of the interface
 *     (abbreviations), how long the session lasts without activity (session, empty where it is kept open), the name of the
 *     path of the element of the editor (editorpath).
 */
export const init = (names = {}, menus = {site: '', page: ''}, texts = {}) => {
    statusRegion();
    // The error of the login form, shown with the page: its role alert is not read when the page opens.
    const error = document.getElementById('loginerrormessage');
    if (error && error.textContent.trim()) {
        window.setTimeout(() => announce(error.textContent), 600);
    }
    repair(document, names);
    nameMenus(menus);
    clearChoices();
    later(texts);
    modalFocus();
    window.addEventListener('keydown', spaceKey);
    window.addEventListener('load', () => {
        repair(document, names);
        nameMenus(menus);
    });
};
