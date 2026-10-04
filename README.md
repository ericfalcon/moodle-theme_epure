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

Sous la couleur de marque, le thème propose les **couleurs du logo** enregistré : cliquez sur une pastille, ou directement sur un point du logo (pipette), puis enregistrez. Seuls les codes hexadécimaux sont acceptés, car la palette accessible en est calculée.

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
