# Épure (theme_epure)

Thème moderne, sobre et accessible pour **Moodle 4.5 LTS et 5.x**, basé sur Boost.

## Installation

1. Copiez le dossier `epure` dans le dossier `theme` de votre Moodle (`public/theme` à partir de Moodle 5.1).
2. Connectez-vous en administrateur et lancez la mise à jour de la base de données.
3. Choisissez Épure dans Administration du site › Apparence › Thèmes.

## Réglages

Administration du site › Apparence › Thèmes › Épure.

### Réglages généraux

| Réglage | Effet |
|---|---|
| Couleur de marque | Toute la palette en est déduite : survols, fonds teintés, couleur des liens. Les couleurs sont ajustées automatiquement pour respecter les contrastes AA des WCAG 2.2, et le contraste obtenu est affiché sous le réglage. |
| Police | Polices fournies : IBM Plex Sans, Inter, Source Sans 3, Figtree, Lato, et pour la lisibilité Atkinson Hyperlegible, Lexend, OpenDyslexic. Ou votre propre police téléversée. |
| Police téléversée | Nom de la police et fichiers `.woff2` ou `.woff` (normal obligatoire, gras facultatif). Vérifiez que la licence de la police autorise l'usage sur un site web. |
| Arrondis | Net, doux ou arrondi : cartes, boutons et champs. |

Sous la couleur de marque, le thème propose les **couleurs du logo** enregistré, y compris les couleurs d'accent qui occupent peu de place (lettrage fin, petit emblème) : cliquez sur une pastille, ou directement sur un point du logo (pipette), puis enregistrez. Seuls les codes hexadécimaux sont acceptés, car la palette accessible en est calculée.

Toutes les polices sont servies par votre propre site. Le thème ne fait **aucun appel à Google Fonts** ni à un autre service externe.

### Page Mes cours

Les cours que vous **animez** et ceux que vous **suivez** sont présentés en deux sections (« Formations que j'anime », « Formations que je suis », avec vos mots de vocabulaire). Avec un seul rôle, la page affiche une liste unique.

| Carte d'un cours suivi | Carte d'un cours animé |
|---|---|
| Progression, prochaine activité à faire, prochaine échéance, bouton Commencer / Continuer / Revoir, mention Terminé | Bandeau et badge de rôle, participants, apprenants actifs cette semaine, devoirs à corriger, accès directs Participants, Notes, Paramètres |

Une recherche (insensible aux accents) et des filtres En cours, À venir, Passés complètent la page. Les cours favoris viennent en premier, puis les plus récemment consultés ; les cours que vous avez masqués restent masqués. Un enseignant est reconnu à sa capacité de voir toutes les notes du cours (`moodle/grade:viewall`). Avec IOMAD, qui remplace le bloc de Moodle par le sien (« Mes cours » avec les onglets disponibles, en cours, terminés), la même présentation s'applique, avec en plus une section « Formations disponibles » et le bouton de téléchargement des certificats d'IOMAD. Réglage « Mes cours par rôle » (Réglages généraux) : décochez pour retrouver le bloc de Moodle ou d'IOMAD.

### En-tête

| Réglage | Effet |
|---|---|
| Couleur de l'en-tête | Blanc avec soulignement de la couleur de marque, ou rempli de la couleur de marque. Les textes et icônes prennent la couleur la plus lisible. |
| Logo | Affiché dans l'en-tête et sur la page de connexion. Les fonds transparents sont pris en charge (SVG, PNG, WebP). Sans logo, ceux d'Apparence › Logos sont utilisés. |
| Logo pour l'en-tête en couleur | Facultatif : une version lisible sur la couleur de marque, souvent blanche sur fond transparent. |

### Page de connexion

| Réglage | Effet |
|---|---|
| Disposition | Écran partagé : un visuel de la couleur de marque à côté du formulaire de Moodle (sur téléphone, seul le formulaire s'affiche). Ou formulaire centré. |
| Accroche et texte d'accompagnement | Affichés sur le visuel. Sans accroche, une phrase par défaut est utilisée. |
| Image de fond | Facultative. Un voile de la couleur de marque est posé dessus, avec l'opacité nécessaire pour que le texte reste lisible (contraste AA) quelle que soit l'image. |

Le formulaire lui-même reste celui de Moodle : il suit chaque version (4.5, 5.0, 5.1) et les méthodes d'authentification configurées.

### Avec IOMAD

Épure applique l'apparence que vous définissez pour chaque entreprise dans IOMAD (Tableau de bord IOMAD › Modifier l'entreprise › Apparence) :

| Réglage IOMAD de l'entreprise | Effet dans Épure |
|---|---|
| Couleur des titres (à défaut, couleur des liens) | Devient la couleur de marque de l'entreprise : la palette accessible est recalculée pour elle (en-tête, boutons, liens, contrastes AA). |
| Logo de l'entreprise | Remplace le logo d'Épure pour les utilisateurs de l'entreprise. |
| CSS personnalisé | Ajouté aux pages des utilisateurs de l'entreprise. |

Ces réglages s'appliquent aux utilisateurs rattachés à l'entreprise, et à l'administrateur qui a sélectionné l'entreprise dans le tableau de bord IOMAD. Seuls les codes couleur hexadécimaux sont pris en compte. La couleur principale (fond de page) d'IOMAD n'est pas appliquée, pour préserver la lisibilité.

**Tableau de bord IOMAD** : le tableau de bord d'IOMAD garde ses actions, ses onglets et ses droits, mais Épure en change la présentation :

- l'entreprise sélectionnée en en-tête, avec son logo, un accès direct à sa catégorie de cours et à ses sous-catégories (page de gestion pour qui gère les cours, liste des cours sinon), lien repris en tête de l'onglet Cours, et le sélecteur d'entreprise à côté ;
- des onglets sobres, soulignés de la couleur de marque, qui défilent sur mobile ;
- les actions en cartes, **regroupées par intention** dans chaque onglet : Créer, Paramétrer, Gérer, Importer et exporter, Suivre ;
- la palette de l'entreprise à la place des couleurs fixes d'IOMAD, et des onglets accessibles aux lecteurs d'écran.

**Tout se règle dans la fiche de l'entreprise** (Tableau de bord IOMAD › Créer une entreprise ou Modifier l'entreprise › Apparence). En tête de cette partie, la section **Apparence Épure** propose :

| Réglage de l'entreprise | Effet |
|---|---|
| Couleur de marque | Code couleur libre, sélecteur de couleur, ou couleurs du logo de l'entreprise (pastilles et pipette, y compris pour un logo tout juste téléversé). Vide : la couleur du titre d'IOMAD, sinon celle du site. La palette accessible est recalculée. |
| Couleur de l'en-tête | Comme le site, blanc, ou couleur de marque. |
| Bannière des cours | Comme le site, affichée ou masquée, pour les utilisateurs de l'entreprise. |
| Aperçu de l'apprenant sur le tableau de bord | Comme le site, affiché ou masqué. |
| Mode sombre | Comme le site, jamais, automatique selon l'appareil, ou toujours. |
| Logo pour l'en-tête en couleur | Facultatif : une version du logo lisible sur la couleur de marque de l'entreprise, souvent blanche sur fond transparent. |
| Police | Comme le site, ou l'une des polices fournies. |

Les champs natifs d'IOMAD (logo, logo compact, CSS personnalisé) restent en dessous ; Épure se déclare thème IOMAD pour qu'IOMAD les affiche. Les couleurs d'IOMAD (titre, principale, lien), qui ne servent qu'aux thèmes IOMAD, sont masquées tant que l'entreprise utilise Épure ; une couleur du titre déjà enregistrée est reprise comme couleur de marque. Enfin, la section **Vocabulaire** règle les mots de l'entreprise, c'est-à-dire ses mots pour « entreprise » et « département », en français et en anglais (« client » et « équipe » pour l'une, « agence » et « service » pour une autre). Épure se déclare thème IOMAD pour qu'IOMAD affiche ces champs. Les mots pour toutes les entreprises se choisissent sur la page Épure : vocabulaire ; une entreprise sans mots propres utilise ceux-là.

Les paquets de langue étant communs à tout le site, ces mots sont appliqués au chargement des chaînes par un gestionnaire de chaînes que le thème active à chaque page (le mécanisme `$CFG->customstringmanager` de Moodle), avec un cache par entreprise. **Aucune modification de `config.php` n'est nécessaire.** Il n'est activé que si des mots sont choisis ; si `config.php` définit déjà un autre gestionnaire de chaînes, celui-ci est conservé et la page Épure : vocabulaire l'indique.

### Vocabulaire

Administration du site › Présentation › Thèmes › Épure : vocabulaire (lien aussi dans l'onglet Réglages généraux du thème).

Choisissez, pour le français et l'anglais, les mots qui désignent :

| Objet | Mots proposés en français | Mots proposés en anglais |
|---|---|---|
| Cours | cours, formation, parcours, module, autre | course, program, programme, module, class, learning path, autre |
| Étudiants | étudiant, apprenant, stagiaire, participant, collaborateur, autre | student, learner, trainee, participant, employee, autre |
| Enseignants | enseignant, formateur, tuteur, intervenant, professeur, coach, autre | teacher, trainer, tutor, instructor, facilitator, coach, autre |

Le vocabulaire s'applique à **tout Moodle** : menus, tableau de bord, listes de cours, participants, rôles, rapports, notifications, ainsi qu'aux pages des plugins et d'IOMAD.

En français, le plugin ne se contente pas de remplacer un mot : les articles, l'élision et les accords suivent le mot choisi. « Tous les cours » devient « Toutes les formations », « Ce cours est masqué » « Cette formation est masquée », « l'étudiant » « le stagiaire ». Pour un mot de votre choix, indiquez son singulier, son pluriel et son genre.

Fonctionnement :

- les chaînes sont écrites avec l'outil natif **Personnalisation de la langue** de Moodle (paquets `fr_local`, `en_local`) : aucun fichier de Moodle ni `config.php` n'est modifié ;
- les chaînes que vous avez personnalisées vous-même dans cet outil sont conservées, jamais écrasées ;
- chaque chaîne réécrite reste modifiable dans l'outil de personnalisation ;
- « Rétablir la formulation de Moodle » retire uniquement les chaînes écrites par le thème, tout comme la désinstallation du thème ;
- les mots restent en place quel que soit le thème utilisé par un cours ou un utilisateur ;
- la première application prend une vingtaine de secondes par langue (Moodle charge le paquet de langue dans l'outil), les suivantes quelques secondes.

Après l'installation d'une nouvelle version de Moodle ou d'un paquet de langue, appliquez à nouveau le vocabulaire pour couvrir les nouvelles chaînes.

Avec IOMAD, la même page propose les mots « entreprise » et « département » pour toutes les entreprises ; chaque entreprise peut choisir les siens dans sa fiche (voir la section IOMAD).

### Réglages avancés

SCSS initial (pour redéfinir des variables) et SCSS ajouté à la fin de la feuille de style.

## Mode sombre

Le réglage **Mode sombre** (réglages généraux du thème) vaut *Jamais*, *Automatique, selon l'appareil* ou *Toujours*. Avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. Chaque utilisateur peut aussi choisir son affichage dans ses préférences (bouton « Aa ») : comme le site, clair, sombre, ou selon son appareil.

Les couleurs sombres sont calculées à partir de la couleur de marque, avec les mêmes contrastes AA ; l'en-tête aux couleurs de la marque garde sa couleur. Les pages de Moodle et d'IOMAD (tableaux de bord, cours, formulaires, menus, tableaux) passent en sombre ; l'éditeur de texte garde l'apparence de son propre thème.

## Tableau de bord de l'apprenant

Pour un utilisateur qui suit des cours, le tableau de bord commence par un aperçu, au-dessus des blocs de Moodle :

- **Reprendre où vous en étiez** : le dernier cours visité et pas encore terminé, avec son image, sa progression, sa prochaine activité et un bouton pour continuer ;
- **En cours** : les autres cours en cours, avec leur progression, et un lien vers « Mes cours » ;
- **À venir** : les prochaines échéances de tous ses cours (devoirs à rendre, tests qui ferment…) ;
- **Terminé** : les cours terminés, avec leur date, et un lien vers ses attestations quand la plateforme en délivre (IOMAD, Certificate, Custom certificate).

L'aperçu se désactive dans les réglages généraux du thème (« Aperçu de l'apprenant sur le tableau de bord ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche.

## Page de cours

En tête de chaque cours, une **bannière** reprend l'image du cours (ou un motif généré), sa catégorie et son titre, avec le fil d'Ariane et les actions habituelles de Moodle. Ensuite, selon le rôle :

- **apprenant** : sa progression, la prochaine activité à faire, la prochaine échéance, et un bouton **Continuer** qui y mène ;
- **enseignant** : les participants, les apprenants actifs cette semaine, les devoirs à corriger, et des accès directs (participants, notes, paramètres) ;
- **visiteur** (invité, utilisateur non inscrit) : l'image, la catégorie et le titre seulement.

Pour l'apprenant, le titre de chaque section indique combien de ses activités il a terminées (« 2/5 », coche une fois la section terminée). La bannière se désactive dans les réglages généraux du thème (« Bannière des cours ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche.

## Accessibilité

Le thème vise les WCAG 2.2 niveau AA, le RGAA 4.1.2 et l'EN 301 549 : contrastes calculés, focus clavier toujours visible, lien d'évitement, cibles d'au moins 24 × 24 px, liens soulignés dans le texte, respect du réglage « réduire les animations ».

**Préférences d'affichage.** Le bouton **Aa** de l'en-tête (raccourci Alt + A) ouvre un panneau où chaque utilisateur connecté choisit :

- la taille du texte : petite, normale, grande ou très grande (90, 100, 115 ou 130 %) ;
- une police de lecture : celle du site, Atkinson Hyperlegible ou OpenDyslexic ;
- un texte plus espacé (valeurs du critère WCAG 1.4.12), un contraste renforcé, des liens soulignés, des animations réduites.

Le changement est immédiat, puis enregistré dans les préférences de son profil Moodle : il vaut sur toutes les pages et tous ses appareils, dès le premier affichage. Les visiteurs non connectés et les invités ont l'affichage par défaut. Ces préférences sont déclarées à l'API de confidentialité et exportées avec les données de l'utilisateur.

**Déclaration d'accessibilité.** L'onglet **Accessibilité** des réglages du thème la remplit au format français (RGAA) : état de conformité, entité, taux de conformité, auditeur et date de l'audit, non-conformités, dérogations, contenus non soumis, contact (par défaut, le courriel du support). Tant qu'aucun état n'est choisi, rien n'est publié. Une fois l'état choisi :

- la déclaration est publiée à l'adresse `/theme/epure/accessibility.php`, lisible sans compte. Elle liste aussi les mesures d'accessibilité prises par le thème (contrastes, clavier, structure, agrandissement, préférences d'affichage, tests automatiques), et les voies de recours auprès du Défenseur des droits ; une adresse électronique saisie comme contact devient un lien ;
- la mention « Accessibilité : partiellement conforme » (selon l'état) apparaît en bas de chaque page, et un lien dans le pied de page.

Un thème ne rend pas une plateforme conforme à lui seul : les contenus des cours comptent aussi, et un audit manuel reste nécessaire avant de déclarer la conformité.

## Développement

Les variables de la palette sont exposées en propriétés CSS (`--epure-brand`, `--epure-brand-text`, `--epure-on-brand`, etc.) pour les SCSS personnalisés.

Tests :

```
vendor/bin/phpunit --testsuite theme_epure_testsuite
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @theme_epure
```

Les tests Behat passent l'audit axe-core de Moodle (« the page should meet accessibility standards ») sur le tableau de bord, le panneau des préférences et la déclaration d'accessibilité.

## Licence

GNU GPL v3 ou ultérieure. Les polices fournies sont sous SIL Open Font License 1.1 (voir `fonts/` et `thirdpartylibs.xml`).
