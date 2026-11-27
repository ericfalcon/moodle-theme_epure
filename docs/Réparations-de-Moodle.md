Certains défauts d'accessibilité sont dans Moodle lui-même, quel que soit le thème. Là où un thème le peut, Épure les répare : d'abord par ses gabarits (Mustache) et ses styles, quand Moodle permet de les remplacer ; sinon dans la page, en JavaScript (`amd/src/aria_repair.js`), au chargement puis chaque fois que Moodle ajoute quelque chose à la page.

Une réparation dans la page est une **dette** : elle arrive un instant après l'affichage, et une refonte de Moodle peut la rendre inutile ou la casser (d'où les tests Behat et le pré-audit, sur chaque version de Moodle de 4.5 à la version de développement). L'objectif est donc de **faire corriger chaque défaut dans Moodle**, puis de retirer la réparation pour les versions qui incluent la correction.

## La règle

1. Un défaut trouvé est d'abord réparé là où il fait du tort : dans Épure, pour les sites d'aujourd'hui.
2. Il est signalé au suivi de Moodle (moodle.atlassian.net), avec un correctif quand le défaut existe encore dans la version de développement (branches `MDL-…-main` du fork <https://github.com/ericfalcon/moodle>).
3. Quand Moodle intègre la correction, la réparation est limitée aux versions qui ne l'ont pas, puis retirée quand la dernière d'entre elles n'est plus prise en charge par Épure.

Chaque réparation ci-dessous porte le nom de sa fonction dans `amd/src/aria_repair.js` (sauf mention contraire). La colonne « Moodle » dit où en est le défaut : **signalé** (avec son ticket), **corrigé** (avec la version), ou **à signaler**.

## Réparations dans la page

| Réparation | Critère | Fonction | Moodle |
|---|---|---|---|
| Le menu du site et le menu de la page, deux navigations, reçoivent un nom | RGAA 12.6 | `nameMenus` | [MDL-90603](https://moodle.atlassian.net/browse/MDL-90603) : corrigé dans Moodle 5.3 ; la réparation reste pour 4.5 à 5.2 |
| Les navigations sans nom en reçoivent un (lettres des participants, choix de la page, index du cours) | RGAA 12.6 | `nameNavigations` | à signaler |
| Le choix caché « Effacer mon choix » des questions est tenu hors du clavier : la touche Tab sort des réponses d'une question à choix unique | RGAA 12.8 | `clearChoices` | [MDL-90599](https://moodle.atlassian.net/browse/MDL-90599), correctif proposé |
| La liste vide des éléments choisis d'un champ à suggestions n'est plus une liste sans option | RGAA 11.1 | `emptySelections` | [MDL-90600](https://moodle.atlassian.net/browse/MDL-90600), correctif proposé |
| Les poignées qui agrandissent les éditeurs ont leur rôle et leur valeur | RGAA 7.1 | `resizeHandles` | à signaler |
| Le mois du calendrier titre son tableau, ses jours titrent leurs colonnes | RGAA 5.4, 5.7 | `calendars` | à signaler |
| Un champ obligatoire le dit dans son libellé, la note qui l'explique est lue | RGAA 11.10 | `requiredFields` | à signaler |
| Les liens faits boutons obéissent à la touche Espace | RGAA 7.3 | `spaceKey` | à signaler |
| Un champ au libellé masqué reçoit ce nom comme titre | RGAA 11.1 | `fieldTitles` | à signaler |
| Les listes qui mettent la page à jour dès le choix le disent | RGAA 7.4 | `contextChanges` | à signaler |
| Les liens qui ne mènent nulle part deviennent des boutons | RGAA 7.1 | `linkButtons` | à signaler |
| Les messages d'état sont annoncés (messages sélectionnés, notification, « Aucun cours ») | RGAA 7.5 | `statusMessages` | à signaler |
| Le minuteur d'un test annonce 5 minutes puis 1 minute | RGAA 13.1 | `quizTimer` | à signaler |
| Un lien qui ouvre une nouvelle fenêtre le dit | RGAA 13.2 | `newWindows` | à signaler |
| Les champs de données personnelles du profil indiquent leur nature | RGAA 11.13 | `purposes` | à signaler |
| L'avatar qui répète un nom voisin se tait | RGAA 1.2 | `userPictures` | à signaler |
| Le titre d'un lien commence par son texte visible | RGAA 6.1 | `linkTitles` | à signaler |
| La colonne triée d'un tableau dit son ordre | RGAA 5.7 | `sortedColumns` | à signaler |
| Les menus disent dès le départ s'ils sont ouverts | RGAA 7.1 | `toggles` | à signaler |
| La navigation d'un test dit l'état de chaque question | RGAA 3.1 | `quizNavigation` | à signaler |
| Le cadre d'un contenu H5P a un titre | RGAA 2.1 | `h5pFrames` | à signaler |
| Les actions répétées d'un tableau disent la ligne qu'elles touchent | RGAA 6.1, 11.9 | `tableActions` | à signaler |
| Les actions d'un message de forum disent le message qu'elles touchent | WCAG 2.4.9 | `postActions` | à signaler |
| Les abréviations de l'interface (H5P, SCORM, LTI…) sont expliquées | WCAG 3.1.4 | `abbreviations` | à signaler |
| Un formulaire dit combien de temps dure la session sans activité | WCAG 2.2.6 | `sessionNote` | à signaler |
| Les icônes de l'éditeur sont ignorées, son chemin est nommé, son cadre a un titre | RGAA 1.2, 12.6, 2.1 | `codeRepairs` | à signaler |
| Les attributs de présentation des tableaux disparaissent | RGAA 10.1 | `codeRepairs` | à signaler |
| Les tableaux du résumé des données et le rapport de l'utilisateur reçoivent titre, en-têtes et résumé | RGAA 5.1, 5.4, 5.6 | `codeRepairs` | à signaler |
| Une page sans titre de niveau 1 (mot de passe oublié) en reçoit un | RGAA 9.1 | `codeRepairs` | à signaler |
| Les identifiants répétés par le tiroir des messages sont uniques | RGAA 8.2 | `codeRepairs` | à signaler |
| Les sections d'un format de cours données comme régions dans une liste (Collapsed Topics) en redeviennent les éléments | RGAA 9.3 | `codeRepairs` | défaut du format Collapsed Topics, à signaler à son auteur |
| Une liste qui contient autre chose que ses éléments (Dialogue, format Buttons) n'est plus donnée comme une liste | RGAA 9.3 | `pluginRepairs` | défauts de ces plugins, à signaler à leurs auteurs |
| Une liste déroulante sans nom prend le texte de son premier choix vide (« Choisir… ») | RGAA 11.1 | `pluginRepairs` | défaut du format Buttons, à signaler |
| Le bouton « Tout replier » laissé vide par un format prend son nom | RGAA 6.2 | `pluginRepairs` | défaut du format Buttons, à signaler |
| Une barre de progression sans nom est nommée « Progression » | RGAA 11.1 | `pluginRepairs` | défaut du format Edwiser, à signaler |
| Un cadre sans titre prend le titre de la page, le nom de l'activité | RGAA 2.1 | `pluginRepairs` | défaut de PDF Protect, à signaler |
| Une icône nommée sans rôle devient une image, pour que son nom soit lu | RGAA 1.1 | `pluginRepairs` | défaut d'Organizer, à signaler |
| Un tableau qui défile horizontalement sans contrôle dedans se rejoint au clavier, nommé par sa légende | WCAG 2.1.1 | `scrollTables` | à signaler (Attendance register) |
| Les liens et boutons d'une seule icône prennent le titre de l'icône pour nom | RGAA 6.2 | `iconControls` | à signaler |
| Les graphiques des rapports sont des images nommées, leurs données dans le tableau qui suit | RGAA 1.1 | `charts` | à signaler |
| Le focus revient au contrôle qui a ouvert une fenêtre, même redessiné | RGAA 7.3, 12.8 | `modalFocus` | à signaler |

## Réparations par les gabarits et les styles

| Réparation | Critère | Où | Moodle |
|---|---|---|---|
| La page de la messagerie garde son titre de niveau 1 | RGAA 9.1 | `scss/epure/_people.scss` | [MDL-90602](https://moodle.atlassian.net/browse/MDL-90602), correctif proposé |
| L'index du cours défile dans son tiroir | WCAG 1.4.10 | `amd/src/courseindex_scroll.js` | [MDL-90605](https://moodle.atlassian.net/browse/MDL-90605), correctif proposé |
| Le champ de réponse rapide d'un forum est nommé par un libellé | bonne pratique | — | [MDL-90604](https://moodle.atlassian.net/browse/MDL-90604), correctif proposé |

Le suivi détaillé des défauts trouvés par le pré-audit, avec ce que cite le VPAT de Moodle, est dans le [rapport de pré-audit](Pr%C3%A9-audit-d%E2%80%99accessibilit%C3%A9.md).

## Contribuer

Signaler un défaut de Moodle, ou proposer son correctif, rend service à tous les sites, avec ou sans Épure : voir [CONTRIBUTING.md](../CONTRIBUTING.md). Une réparation « à signaler » est un bon premier ticket.
