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
 * The window « Add a block »: each block with its icon, and a search that keeps the blocks whose name holds the
 * words typed (without minding the accents nor the capitals). The links that add the blocks stay Moodle's.
 *
 * @module     theme_epure/add_block
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ModalEvents from 'core/modal_events';

/** @var {Object} Icon of each block (by the name of its plugin), from Font Awesome; the others keep a piece of puzzle. */
const ICONS = {
    'accessreview': 'fa-universal-access',
    'activity_modules': 'fa-shapes',
    'activity_results': 'fa-ranking-star',
    'admin_bookmarks': 'fa-bookmark',
    'badges': 'fa-award',
    'blog_menu': 'fa-blog',
    'blog_recent': 'fa-blog',
    'blog_tags': 'fa-tags',
    'calendar_month': 'fa-calendar-days',
    'calendar_upcoming': 'fa-calendar-check',
    'comments': 'fa-comments',
    'completionstatus': 'fa-list-check',
    'course_list': 'fa-book',
    'course_summary': 'fa-circle-info',
    'feedback': 'fa-comment-dots',
    'globalsearch': 'fa-magnifying-glass',
    'glossary_random': 'fa-book-open',
    'html': 'fa-font',
    'iomad_approve_access': 'fa-user-check',
    'iomad_commerce': 'fa-cart-shopping',
    'iomad_company_admin': 'fa-building',
    'iomad_company_selector': 'fa-building',
    'iomad_html': 'fa-font',
    'iomad_learningpath': 'fa-route',
    'iomad_link': 'fa-link',
    'iomad_microlearning': 'fa-bolt',
    'iomad_onlineusers': 'fa-user-group',
    'iomad_reports': 'fa-chart-line',
    'iomad_welcome': 'fa-hand',
    'login': 'fa-right-to-bracket',
    'lp': 'fa-route',
    'mentees': 'fa-users',
    'mnet_hosts': 'fa-network-wired',
    'myoverview': 'fa-table-cells-large',
    'myprofile': 'fa-user',
    'navigation': 'fa-sitemap',
    'news_items': 'fa-bullhorn',
    'online_users': 'fa-user-group',
    'private_files': 'fa-folder',
    'recent_activity': 'fa-clock-rotate-left',
    'recentlyaccessedcourses': 'fa-clock-rotate-left',
    'recentlyaccesseditems': 'fa-clock-rotate-left',
    'rss_client': 'fa-rss',
    'search_forums': 'fa-magnifying-glass',
    'section_links': 'fa-list-ol',
    'selfcompletion': 'fa-circle-check',
    'settings': 'fa-gear',
    'social_activities': 'fa-people-group',
    'starredcourses': 'fa-star',
    'tag_flickr': 'fa-images',
    'tag_youtube': 'fa-video',
    'tags': 'fa-tags',
    'timeline': 'fa-timeline',
};

/**
 * A text to compare: without accents, capitals and extra spaces.
 *
 * @param {string} text Text.
 * @returns {string}
 */
const fold = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();

/**
 * Gives the blocks of a list their icons, and its search its filter.
 *
 * @param {HTMLElement} root Element holding the list, the body of the window or the page.
 */
const enhance = (root) => {
    const list = root.querySelector('.epure-addblock');
    if (!list || list.dataset.epureReady) {
        return;
    }
    list.dataset.epureReady = '1';
    const items = [...list.querySelectorAll('.epure-addblock-item')];
    items.forEach((item) => {
        const icon = ICONS[item.dataset.blockname];
        if (icon) {
            item.querySelector('.epure-addblock-icon i').className = 'fa ' + icon;
        }
    });

    const search = list.querySelector('[data-region="epure-addblock-search"]');
    const none = list.querySelector('[data-region="epure-addblock-none"]');
    search?.addEventListener('input', () => {
        const words = fold(search.value).split(' ').filter((word) => word);
        let shown = 0;
        items.forEach((item) => {
            const name = fold(item.querySelector('.epure-addblock-link').textContent);
            const match = words.every((word) => name.includes(word));
            item.hidden = !match;
            shown += match ? 1 : 0;
        });
        none.hidden = shown > 0;
    });
};

let registered = false;

/**
 * Enhances the list of the page, and the one of each window « Add a block » when it is shown.
 */
export const init = () => {
    enhance(document);
    if (registered) {
        return;
    }
    registered = true;
    // Moodle renders the list of the window once it is open, without running the script of its template.
    $(document).on(ModalEvents.bodyRendered, (event) => enhance(event.target));
};
