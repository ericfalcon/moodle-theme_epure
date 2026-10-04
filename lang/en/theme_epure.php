<?php
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
 * English strings for theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['advancedsettings'] = 'Advanced settings';
$string['brandcolor'] = 'Brand colour';
$string['brandcolor_adjusted'] = 'Links and brand-coloured text use {$a->text}, slightly adjusted from the brand colour so they stay readable on white and on tinted backgrounds (WCAG AA).';
$string['brandcolor_contrast'] = 'Current contrast: text on the brand colour {$a->oncontrast}:1, links on white {$a->textcontrast}:1.';
$string['brandcolor_desc'] = 'The main colour of the site. Hover states, tinted backgrounds and link colours are derived from it, and adjusted automatically to meet the WCAG 2.2 AA contrast ratios.';
$string['brandcolor_invalid'] = 'Enter a hex colour code, such as #2559A8.';
$string['cachedef_companystrings'] = 'Strings with the words of an IOMAD company';
$string['choosereadme'] = 'Épure is a modern, accessible theme built on Boost. A single brand colour drives an accessible palette, and fonts are served by your own site.';
$string['companyformvocabulary'] = 'Vocabulary';
$string['companyformvocabulary_desc'] = 'Words used for « company » and « department » on every page, for the users of this company. By default, those chosen for all companies (Épure: vocabulary).';
$string['companyvocabunavailable'] = 'config.php sets another custom string manager ($CFG->customstringmanager): the vocabulary of the companies cannot be applied. Remove that line, or contact the developer of that string manager.';
$string['configtitle'] = 'Épure';
$string['customfontbold'] = 'Uploaded font: bold';
$string['customfontbold_desc'] = 'Optional. Bold weight (700) as a .woff2 or .woff file. Without it, browsers simulate bold.';
$string['customfontname'] = 'Uploaded font: name';
$string['customfontname_desc'] = 'Name of the uploaded font, for example the name of your brand typeface.';
$string['customfontregular'] = 'Uploaded font: regular';
$string['customfontregular_desc'] = 'Regular weight (400) as a .woff2 or .woff file. Required to use the uploaded font; check that its licence allows use on a website.';
$string['font'] = 'Font';
$string['font_desc'] = 'Font used across the site. Bundled fonts and uploaded fonts are served by your site, with no request to an external service such as Google Fonts.';
$string['fontcustom'] = 'Uploaded font (see below)';
$string['fontreadability'] = '{$a} (readability)';
$string['generalsettings'] = 'General settings';
$string['headersettings'] = 'Header';
$string['headerstyle'] = 'Header colour';
$string['headerstyle_desc'] = 'White header with a brand-coloured underline, or a header filled with the brand colour. Text and icons switch to the colour that reads best on it.';
$string['headerstylebrand'] = 'Brand colour';
$string['headerstylelight'] = 'White';
$string['iomadintent_configure'] = 'Configure';
$string['iomadintent_create'] = 'Create';
$string['iomadintent_follow'] = 'Follow up';
$string['iomadintent_manage'] = 'Manage';
$string['iomadintent_transfer'] = 'Import and export';
$string['iomadselectedcompany'] = 'Selected company';
$string['localepureinstalled'] = 'The former companion plugin « Épure tools » (local_epure) is still installed. Its settings were taken over by the theme: uninstall it in Site administration › Plugins › Plugins overview.';
$string['loginimage'] = 'Background image';
$string['loginimage_desc'] = 'Optional. A veil of the brand colour is laid over the image, with the opacity needed to keep the text readable (WCAG AA) whatever the image.';
$string['loginlayout'] = 'Layout';
$string['loginlayout_desc'] = 'Split screen shows a brand-coloured visual beside the form on large screens. On phones, only the form is shown.';
$string['loginlayoutcentered'] = 'Centred form';
$string['loginlayoutsplit'] = 'Split screen';
$string['loginsettings'] = 'Login page';
$string['logintagline'] = 'Headline';
$string['logintagline_default'] = 'Learn at your own pace.';
$string['logintagline_desc'] = 'Short sentence shown on the visual. Leave empty for the default headline.';
$string['logintext'] = 'Supporting text';
$string['logintext_desc'] = 'Optional sentence shown under the headline.';
$string['logo'] = 'Logo';
$string['logo_desc'] = 'Shown in the header and on the login page. Transparent backgrounds are supported: use SVG, PNG or WebP. When empty, the logos set in Appearance > Logos are used.';
$string['logocolours'] = 'Colours of the logo';
$string['logocolours_applied'] = 'Colour {$a} selected. Save the changes to apply it.';
$string['logocolours_company_help'] = 'Click a colour to use it as the heading colour, which is the brand colour of the company in Épure, or click a point of the logo. Then save the company.';
$string['logocolours_company_none'] = 'Add a company logo above: its main colours will be proposed here.';
$string['logocolours_error'] = 'The colours of this logo could not be read.';
$string['logocolours_help'] = 'Click a colour to use it, or click a point of the logo to pick its colour. Then save the changes.';
$string['logocolours_none'] = 'Upload a logo in the Header tab and save: its main colours will be proposed here.';
$string['logocolours_use'] = 'Use the colour {$a}';
$string['logoonbrand'] = 'Logo for the brand-coloured header';
$string['logoonbrand_desc'] = 'Optional. A version of the logo that reads on the brand colour, often white on a transparent background. Used when the header colour is set to the brand colour.';
$string['mycourses_active'] = 'Active this week';
$string['mycourses_available'] = '{$a} available';
$string['mycourses_availablebadge'] = 'Available';
$string['mycourses_completed'] = 'Completed';
$string['mycourses_continue'] = 'Continue';
$string['mycourses_discover'] = 'Discover';
$string['mycourses_filter'] = 'Show the courses';
$string['mycourses_learning'] = '{$a} I take';
$string['mycourses_next'] = 'Next:';
$string['mycourses_nomatch'] = 'No course matches the search.';
$string['mycourses_participants'] = 'Participants';
$string['mycourses_progress'] = 'Progress';
$string['mycourses_review'] = 'Review';
$string['mycourses_search'] = 'Search my courses';
$string['mycourses_shown'] = '{$a} courses shown.';
$string['mycourses_start'] = 'Start';
$string['mycourses_teaching'] = '{$a} I teach';
$string['mycourses_tograde'] = 'To grade';
$string['mycoursesbyrole'] = 'My courses by role';
$string['mycoursesbyrole_desc'] = 'On the My courses page, the courses you teach and the courses you take are shown in two sections, with cards suited to each role: progress, next activity and deadline for a learner; participants, submissions to grade and quick links for a teacher. Untick to show Moodle\'s own block.';
$string['pluginname'] = 'Épure';
$string['privacy:metadata'] = 'The Épure theme does not store any personal data.';
$string['radius'] = 'Corner style';
$string['radius_desc'] = 'Roundness of cards, buttons and form fields.';
$string['radiusround'] = 'Round';
$string['radiussharp'] = 'Sharp';
$string['radiussoft'] = 'Soft';
$string['rawscss'] = 'Raw SCSS';
$string['rawscss_desc'] = 'SCSS or CSS added at the end of the stylesheet.';
$string['rawscsspre'] = 'Raw initial SCSS';
$string['rawscsspre_desc'] = 'SCSS added before the theme styles, typically to override variables.';
$string['region-side-pre'] = 'Right';
$string['vocab_company'] = 'Companies are called';
$string['vocab_course'] = 'Courses are called';
$string['vocab_department'] = 'Departments are called';
$string['vocab_student'] = 'Students are called';
$string['vocab_teacher'] = 'Teachers are called';
$string['vocabafter'] = 'With your vocabulary';
$string['vocabapplied'] = '{$a->total} strings use your vocabulary ({$a->changed} changed now).';
$string['vocabapply'] = 'Save and apply';
$string['vocabbefore'] = 'Moodle wording';
$string['vocabcustom'] = 'Other word…';
$string['vocabexamples'] = '{$a->lang}: {$a->count} strings use your vocabulary. Examples:';
$string['vocabfeminine'] = 'Feminine';
$string['vocabgender'] = 'Gender';
$string['vocabintro'] = 'Choose the words used for courses, students and teachers, and with IOMAD for companies and departments (each company can choose its own in its form: Edit company › Appearance). They replace Moodle\'s wording everywhere: menus, dashboard, course lists, participants, roles, reports, notifications, and the pages of plugins. In French, articles and agreements follow the word you choose. The strings are written with Moodle\'s language customisation tool: the customisations you made there yourself are kept, and you can still edit each string. They stay in place whatever theme is used; uninstalling Épure removes them. Applying takes a few seconds, and about twenty seconds the first time for each language.';
$string['vocabkept'] = '{$a} strings you customised yourself in the language customisation tool were left unchanged.';
$string['vocabmasculine'] = 'Masculine';
$string['vocabnolanguage'] = 'None of the supported languages (French, English) is installed.';
$string['vocabnone'] = 'Moodle\'s wording is used.';
$string['vocabplatform'] = 'Same as the platform ({$a})';
$string['vocabplural'] = 'Plural';
$string['vocabreset'] = 'Restore Moodle\'s wording';
$string['vocabsingular'] = 'Singular';
$string['vocabulary'] = 'Épure: vocabulary';
$string['vocabularylink'] = 'The words used for courses, students and teachers (and, with IOMAD, companies and departments) are chosen on the page <a href="{$a}">Épure: vocabulary</a>. Each IOMAD company can choose its own words in its form: Edit company › Appearance.';
