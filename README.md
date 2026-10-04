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

**Vocabulaire des entreprises** (Administration du site › Présentation › Thèmes › Épure : vocabulaire des entreprises) : choisissez les mots pour « entreprise » et « département », en français et en anglais, pour toutes les entreprises et, au besoin, pour chacune (« client » et « équipe » pour l'une, « agence » et « service » pour une autre). Une entreprise sans mots propres utilise ceux de toutes les entreprises. Les règles de grammaire du vocabulaire s'appliquent : « Modifier l'entreprise » devient « Modifier le client », « Afficher les entreprises suspendues » « Afficher les clients suspendus ».

Les paquets de langue étant communs à tout le site, ces mots sont appliqués au chargement des chaînes par un gestionnaire de chaînes que le thème active à chaque page (le mécanisme `$CFG->customstringmanager` de Moodle), avec un cache par entreprise. **Aucune modification de `config.php` n'est nécessaire.** Il n'est activé que si des mots sont choisis ; si `config.php` définit déjà un autre gestionnaire de chaînes, celui-ci est conservé et la page l'indique.

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

Avec IOMAD, les mots « entreprise » et « département » se règlent à part, pour toutes les entreprises et pour chacune : voir la section IOMAD.

### Réglages avancés

SCSS initial (pour redéfinir des variables) et SCSS ajouté à la fin de la feuille de style.

## Accessibilité

Le thème vise les WCAG 2.2 niveau AA, le RGAA 4.1.2 et l'EN 301 549 : contrastes calculés, focus clavier toujours visible, lien d'évitement, cibles d'au moins 24 × 24 px, liens soulignés dans le texte, respect du réglage « réduire les animations ».

Un thème ne rend pas une plateforme conforme à lui seul : les contenus des cours comptent aussi, et un audit manuel reste nécessaire avant de déclarer la conformité.

## Développement

Les variables de la palette sont exposées en propriétés CSS (`--epure-brand`, `--epure-brand-text`, `--epure-on-brand`, etc.) pour les SCSS personnalisés.

Tests :

```
vendor/bin/phpunit --testsuite theme_epure_testsuite
```

## Licence

GNU GPL v3 ou ultérieure. Les polices fournies sont sous SIL Open Font License 1.1 (voir `fonts/` et `thirdpartylibs.xml`).
