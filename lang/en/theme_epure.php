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
$string['companyvocabchoose'] = 'Choose a company…';
$string['companyvocabcompany'] = 'Company';
$string['companyvocabintro'] = 'Choose the words used for « company » and « department »: for all companies (the platform), and if needed for each company. They apply on every page to the users of the company, and to the administrator who selected it in the IOMAD dashboard. A company without its own words uses those of the platform.';
$string['companyvocablist'] = 'Companies with their own words';
$string['companyvocabnoiomad'] = 'IOMAD is not installed on this site.';
$string['companyvocabplatform'] = 'All companies (platform)';
$string['companyvocabreset'] = 'Use the words of the platform';
$string['companyvocabsaved'] = 'The vocabulary of {$a} is saved.';
$string['companyvocabulary'] = 'Épure: vocabulary of the companies';
$string['companyvocabunavailable'] = 'config.php sets another custom string manager ($CFG->customstringmanager): the vocabulary of the companies cannot be applied. Remove that line, or contact the developer of that string manager.';
$string['companyvocabwords'] = 'Words';
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
$string['logocolours_error'] = 'The colours of this logo could not be read.';
$string['logocolours_help'] = 'Click a colour to use it, or click a point of the logo to pick its colour. Then save the changes.';
$string['logocolours_none'] = 'Upload a logo in the Header tab and save: its main colours will be proposed here.';
$string['logocolours_use'] = 'Use the colour {$a}';
$string['logoonbrand'] = 'Logo for the brand-coloured header';
$string['logoonbrand_desc'] = 'Optional. A version of the logo that reads on the brand colour, often white on a transparent background. Used when the header colour is set to the brand colour.';
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
$string['vocabintro'] = 'Choose the words used for courses, students and teachers. They replace Moodle\'s wording everywhere: menus, dashboard, course lists, participants, roles, reports, notifications, and the pages of plugins. In French, articles and agreements follow the word you choose. The strings are written with Moodle\'s language customisation tool: the customisations you made there yourself are kept, and you can still edit each string. They stay in place whatever theme is used; uninstalling Épure removes them. Applying takes a few seconds, and about twenty seconds the first time for each language.';
$string['vocabkept'] = '{$a} strings you customised yourself in the language customisation tool were left unchanged.';
$string['vocabmasculine'] = 'Masculine';
$string['vocabnolanguage'] = 'None of the supported languages (French, English) is installed.';
$string['vocabnone'] = 'Moodle\'s wording is used.';
$string['vocabplatform'] = 'Same as the platform ({$a})';
$string['vocabplural'] = 'Plural';
$string['vocabreset'] = 'Restore Moodle\'s wording';
$string['vocabsingular'] = 'Singular';
$string['vocabulary'] = 'Épure: vocabulary';
$string['vocabularylink'] = 'The words used for courses, students and teachers are chosen on the page <a href="{$a}">Épure: vocabulary</a>. With IOMAD, the words for companies and departments are chosen on the page Épure: vocabulary of the companies.';
