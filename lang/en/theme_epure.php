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
$string['choosereadme'] = 'Épure is a modern, accessible theme built on Boost. A single brand colour drives an accessible palette, and fonts are served by your own site.';
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
