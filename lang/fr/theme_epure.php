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
 * French strings for theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['advancedsettings'] = 'Réglages avancés';
$string['brandcolor'] = 'Couleur de marque';
$string['brandcolor_adjusted'] = 'Les liens et les textes de marque utilisent {$a->text}, un ajustement de la couleur de marque qui les garde lisibles sur fond blanc et sur les fonds teintés (contraste AA des WCAG).';
$string['brandcolor_contrast'] = 'Contraste actuel : texte sur la couleur de marque {$a->oncontrast}:1, liens sur fond blanc {$a->textcontrast}:1.';
$string['brandcolor_desc'] = 'La couleur principale du site. Les survols, les fonds teintés et la couleur des liens en sont déduits, et ajustés automatiquement pour respecter les contrastes AA des WCAG 2.2.';
$string['brandcolor_invalid'] = 'Saisissez un code couleur hexadécimal, par exemple #2559A8.';
$string['choosereadme'] = 'Épure est un thème moderne et accessible basé sur Boost. Une seule couleur de marque produit une palette accessible, et les polices sont servies par votre propre site.';
$string['configtitle'] = 'Épure';
$string['customfontbold'] = 'Police téléversée : gras';
$string['customfontbold_desc'] = 'Facultatif. Graisse grasse (700) au format .woff2 ou .woff. Sans ce fichier, les navigateurs simulent le gras.';
$string['customfontname'] = 'Police téléversée : nom';
$string['customfontname_desc'] = 'Nom de la police téléversée, par exemple celui de la typographie de votre marque.';
$string['customfontregular'] = 'Police téléversée : normale';
$string['customfontregular_desc'] = 'Graisse normale (400) au format .woff2 ou .woff. Obligatoire pour utiliser la police téléversée ; vérifiez que sa licence autorise l\'usage sur un site web.';
$string['font'] = 'Police';
$string['font_desc'] = 'Police utilisée sur tout le site. Les polices fournies et les polices téléversées sont servies par votre site, sans appel à un service externe comme Google Fonts.';
$string['fontcustom'] = 'Police téléversée (voir ci-dessous)';
$string['fontreadability'] = '{$a} (lisibilité)';
$string['generalsettings'] = 'Réglages généraux';
$string['headersettings'] = 'En-tête';
$string['headerstyle'] = 'Couleur de l\'en-tête';
$string['headerstyle_desc'] = 'En-tête blanc avec un soulignement de la couleur de marque, ou en-tête rempli de la couleur de marque. Les textes et icônes prennent automatiquement la couleur la plus lisible.';
$string['headerstylebrand'] = 'Couleur de marque';
$string['headerstylelight'] = 'Blanc';
$string['loginimage'] = 'Image de fond';
$string['loginimage_desc'] = 'Facultative. Un voile de la couleur de marque est posé sur l\'image, avec l\'opacité nécessaire pour garder le texte lisible (contraste AA des WCAG), quelle que soit l\'image.';
$string['loginlayout'] = 'Disposition';
$string['loginlayout_desc'] = 'L\'écran partagé affiche un visuel de la couleur de marque à côté du formulaire sur les grands écrans. Sur téléphone, seul le formulaire s\'affiche.';
$string['loginlayoutcentered'] = 'Formulaire centré';
$string['loginlayoutsplit'] = 'Écran partagé';
$string['loginsettings'] = 'Page de connexion';
$string['logintagline'] = 'Accroche';
$string['logintagline_default'] = 'Formez-vous à votre rythme.';
$string['logintagline_desc'] = 'Phrase courte affichée sur le visuel. Laissez vide pour l\'accroche par défaut.';
$string['logintext'] = 'Texte d\'accompagnement';
$string['logintext_desc'] = 'Phrase facultative affichée sous l\'accroche.';
$string['logo'] = 'Logo';
$string['logo_desc'] = 'Affiché dans l\'en-tête et sur la page de connexion. Les fonds transparents sont pris en charge : utilisez un fichier SVG, PNG ou WebP. Sans logo, ceux définis dans Apparence › Logos sont utilisés.';
$string['logocolours'] = 'Couleurs du logo';
$string['logocolours_applied'] = 'Couleur {$a} sélectionnée. Enregistrez les modifications pour l\'appliquer.';
$string['logocolours_error'] = 'Les couleurs de ce logo n\'ont pas pu être lues.';
$string['logocolours_help'] = 'Cliquez sur une couleur pour l\'utiliser, ou sur un point du logo pour prélever sa couleur. Enregistrez ensuite les modifications.';
$string['logocolours_none'] = 'Téléversez un logo dans l\'onglet En-tête et enregistrez : ses couleurs principales seront proposées ici.';
$string['logocolours_use'] = 'Utiliser la couleur {$a}';
$string['logoonbrand'] = 'Logo pour l\'en-tête en couleur';
$string['logoonbrand_desc'] = 'Facultatif. Une version du logo lisible sur la couleur de marque, souvent blanche sur fond transparent. Utilisée quand l\'en-tête prend la couleur de marque.';
$string['pluginname'] = 'Épure';
$string['privacy:metadata'] = 'Le thème Épure ne stocke aucune donnée personnelle.';
$string['radius'] = 'Arrondis';
$string['radius_desc'] = 'Arrondi des cartes, boutons et champs de formulaire.';
$string['radiusround'] = 'Arrondi';
$string['radiussharp'] = 'Net';
$string['radiussoft'] = 'Doux';
$string['rawscss'] = 'SCSS brut';
$string['rawscss_desc'] = 'SCSS ou CSS ajouté à la fin de la feuille de style.';
$string['rawscsspre'] = 'SCSS initial brut';
$string['rawscsspre_desc'] = 'SCSS ajouté avant les styles du thème, en général pour redéfinir des variables.';
$string['region-side-pre'] = 'Droite';
$string['vocab_company'] = 'Les entreprises s\'appellent';
$string['vocab_course'] = 'Les cours s\'appellent';
$string['vocab_department'] = 'Les départements s\'appellent';
$string['vocab_student'] = 'Les étudiants s\'appellent';
$string['vocab_teacher'] = 'Les enseignants s\'appellent';
$string['vocabafter'] = 'Avec votre vocabulaire';
$string['vocabapplied'] = '{$a->total} chaînes utilisent votre vocabulaire ({$a->changed} modifiées maintenant).';
$string['vocabapply'] = 'Enregistrer et appliquer';
$string['vocabbefore'] = 'Formulation de Moodle';
$string['vocabcustom'] = 'Autre mot…';
$string['vocabexamples'] = '{$a->lang} : {$a->count} chaînes utilisent votre vocabulaire. Exemples :';
$string['vocabfeminine'] = 'Féminin';
$string['vocabgender'] = 'Genre';
$string['vocabintro'] = 'Choisissez les mots qui désignent les cours, les étudiants et les enseignants. Ils remplacent ceux de Moodle partout : menus, tableau de bord, listes de cours, participants, rôles, rapports, notifications, ainsi que les pages des plugins. En français, les articles et les accords suivent le mot choisi. Les chaînes sont écrites avec l\'outil de personnalisation de la langue de Moodle : les personnalisations que vous y avez faites vous-même sont conservées, et chaque chaîne reste modifiable. Elles restent en place quel que soit le thème utilisé ; désinstaller Épure les retire. L\'application prend quelques secondes, et une vingtaine de secondes la première fois pour chaque langue.';
$string['vocabkept'] = '{$a} chaînes que vous avez personnalisées vous-même dans l\'outil de personnalisation de la langue n\'ont pas été modifiées.';
$string['vocabmasculine'] = 'Masculin';
$string['vocabnolanguage'] = 'Aucune des langues prises en charge (français, anglais) n\'est installée.';
$string['vocabnone'] = 'La formulation de Moodle est utilisée.';
$string['vocabplatform'] = 'Comme la plateforme ({$a})';
$string['vocabplural'] = 'Pluriel';
$string['vocabreset'] = 'Rétablir la formulation de Moodle';
$string['vocabsingular'] = 'Singulier';
$string['vocabulary'] = 'Épure : vocabulaire';
$string['vocabularylink'] = 'Les mots qui désignent les cours, les étudiants et les enseignants se choisissent sur la page <a href="{$a}">Épure : vocabulaire</a>.';
