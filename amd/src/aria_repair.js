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
 *
 * @module     theme_epure/aria_repair
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    };
    run();
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
 *     (paging, choice, courseindex), the notes of the lists that update the page (updates, changes).
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
    window.addEventListener('keydown', spaceKey);
    window.addEventListener('load', () => {
        repair(document, names);
        nameMenus(menus);
    });
};
