# Variables CSS `--epure-*`

Épure expose sa palette, ses formes et ses états sous forme de **propriétés personnalisées CSS** (`--epure-*`). Elles servent à écrire un SCSS ou un CSS personnalisé, un gabarit, ou le style d'un plugin, qui suit le thème **sans recopier ses couleurs** : la couleur de marque du site ou de l'entreprise IOMAD, le mode sombre et le contraste renforcé s'appliquent alors d'eux-mêmes.

## Où les utiliser

- Onglet **Réglages avancés** des réglages du thème (Administration du site › Présentation › Thèmes › Épure) : le champ « SCSS brut » reçoit votre code, ajouté à la fin de la feuille de style et compilé avec le thème.
- Le CSS d'un plugin, d'un bloc HTML ou d'un gabarit Mustache affiché avec Épure.

```scss
// Un encadré aux couleurs de la marque, lisible en clair, en sombre et pour chaque entreprise IOMAD.
.mon-encadre {
    padding: 1rem;
    color: var(--epure-ink);
    background-color: var(--epure-brand-soft);
    border-left: .25rem solid var(--epure-brand);
    border-radius: var(--epure-radius);
}

.mon-encadre a {
    color: var(--epure-brand-text);
}
```

**Règles d'usage :**

- Lisez toujours la variable avec `var(--epure-…)` dans la règle. Ne copiez jamais sa valeur : elle change avec le mode sombre, l'entreprise et les préférences de l'utilisateur.
- Gardez les **paires** prévues pour le contraste : un texte `--epure-on-brand` sur un fond `--epure-brand`, un texte `--epure-brand-text` ou `--epure-ink` sur `--epure-surface`, `--epure-surface-muted` ou `--epure-brand-soft`. Ces paires sont calculées pour atteindre au moins 4,5:1 (WCAG AA). Les autres combinaisons ne sont pas garanties.
- Ne redéfinissez pas ces variables sur `:root` : la couleur de marque se règle dans les réglages du thème (et dans la fiche de chaque entreprise avec IOMAD), d'où le thème déduit toute la palette.

## Couleur de marque

Le thème déduit ces couleurs de la couleur de marque choisie, en clair comme en sombre (classe `palette`). Avec IOMAD, chaque entreprise qui a sa couleur les redéfinit pour ses utilisateurs.

| Variable | Rôle | Avec quoi |
|---|---|---|
| `--epure-brand` | Fond de marque : boutons principaux, bandeaux, en-tête de couleur. Ajusté si la couleur choisie est trop claire ou trop sombre pour porter un texte. | Texte `--epure-on-brand` |
| `--epure-brand-hover` | Fond de marque au survol et au focus. | Texte `--epure-on-brand` |
| `--epure-on-brand` | Texte et icônes posés sur `--epure-brand` (blanc ou presque noir). | — |
| `--epure-brand-text` | Couleur de marque pour un texte ou un lien sur une surface : liens, titres colorés, icônes. | Fonds `--epure-surface`, `--epure-surface-muted`, `--epure-brand-soft` |
| `--epure-brand-soft` | Teinte légère de la marque : fonds d'encadrés, élément actif, sélection. | Textes `--epure-ink`, `--epure-brand-text` |
| `--epure-brand-soft-2` | Teinte un peu plus soutenue : survol d'un élément teinté, deuxième niveau. | Textes `--epure-ink`, `--epure-brand-text` |
| `--epure-focus` | Couleur du contour de focus clavier (celle de `--epure-brand-text`). | — |
| `--epure-focus-ring` | Halo des champs, boutons et onglets au focus, à partir de `--epure-focus`. | — |
| `--epure-accent` | Couleur d'accent : barres de progression, sections terminées. Réglage « Couleur d’accent » du site ou de l'entreprise, sinon la couleur de marque. | Texte `--epure-on-accent` |
| `--epure-on-accent` | Texte posé sur `--epure-accent`. | — |

**Variantes de la palette :**

- `--epure-dark-brand`, `--epure-dark-brand-hover`, `--epure-dark-on-brand`, `--epure-dark-brand-text`, `--epure-dark-brand-soft`, `--epure-dark-brand-soft-2`, `--epure-dark-accent` et `--epure-dark-on-accent` sont les mêmes rôles **calculés pour le mode sombre**. En mode sombre, le thème les reporte lui-même sur les variables ci-dessus : utilisez toujours les noms sans `dark`.
- `--epure-light-brand-text` et `--epure-light-brand-soft` gardent les valeurs **claires** en mode sombre. Elles servent à ce qui reste blanc quel que soit le mode (l'éditeur de texte, les « îlots clairs » de certains plugins).

## Surfaces et texte

| Variable | Rôle |
|---|---|
| `--epure-page` | Fond de la page, derrière les cartes. |
| `--epure-surface` | Fond des cartes, des fenêtres et des contenus. |
| `--epure-surface-muted` | Fond discret : encadrés, lignes alternées, zones secondaires. |
| `--epure-surface-raised` | Fond d'un élément posé au-dessus (menus, fenêtres en mode sombre). |
| `--epure-border` | Bordures et séparateurs. |
| `--epure-ink` | Texte principal. |
| `--epure-ink-muted` | Texte secondaire : aides, dates, légendes (contraste AA sur les surfaces). |
| `--epure-shadow` | Ombre d'une carte. |
| `--epure-shadow-hover` | Ombre d'une carte au survol. |

## Formes

Elles suivent le réglage **Arrondis** du thème (ou de l'entreprise IOMAD).

| Variable | Rôle |
|---|---|
| `--epure-radius-sm` | Petits éléments : touches, étiquettes. |
| `--epure-radius` | Cartes, champs, boutons. |
| `--epure-radius-lg` | Grandes cartes et fenêtres. |
| `--epure-radius-pill` | Pastilles, compteurs, champ de recherche (pilule, ou rectangle arrondi avec les coins droits). |

## États des activités

Les états (en retard, bientôt dû, terminé, réussi…) ont chacun une couleur de texte et une teinte de fond, contrastées en clair comme en sombre.

| Variable | Rôle |
|---|---|
| `--epure-state-done`, `--epure-state-done-soft` | Terminé, réussi : texte, fond. |
| `--epure-state-late`, `--epure-state-late-soft` | En retard, échoué : texte, fond. |
| `--epure-state-soon`, `--epure-state-soon-soft` | Échéance proche, à confirmer : texte, fond. |
| `--epure-state-neutral` | À faire, sans échéance. |
| `--epure-state-ink`, `--epure-state-bg` | Couleurs de l'état d'un élément `.epure-state-…`, posées par le thème sur cet élément : à lire, pas à définir. |

## En-tête

| Variable | Rôle |
|---|---|
| `--epure-header-bg` | Fond de l'en-tête de couleur de marque (il garde la couleur claire en mode sombre). |
| `--epure-header-on` | Texte et icônes de cet en-tête. |
| `--epure-header-hover` | Survol d'un élément de l'en-tête. |
| `--epure-header-divider` | Séparateurs de l'en-tête. |
| `--epure-header-toggler-filter` | Filtre de l'icône du menu (blanche ou sombre selon la marque). |

## Autres

| Variable | Rôle |
|---|---|
| `--epure-login-overlay` | Voile de couleur de marque sur l'image de la page de connexion, assez opaque pour que le texte reste lisible quelle que soit l'image. |
| `--epure-event` | Couleur d'un type d'événement dans le calendrier, posée sur l'événement. |
| `--epure-mobilenav-height` | Hauteur de la barre de navigation mobile, quand elle est affichée (à réserver en bas d'un élément fixe). |
| `--epure-contrast-border` | Bordure renforcée, avec la préférence « contraste renforcé ». |

## Ce qui les fait changer

- **Mode sombre** : les classes `epure-dark` (toujours sombre) et `epure-dark-auto` (selon l'appareil) sur l'élément `html` reportent les variantes `--epure-dark-*` et des surfaces sombres sur les mêmes noms.
- **Entreprise IOMAD** : la feuille de style de l'entreprise redéfinit la palette de marque (claire et sombre), l'en-tête et le voile de connexion.
- **Préférences d'affichage** (panneau « Aa ») : le contraste renforcé (`epure-a11y-contrast`) assombrit `--epure-ink`, `--epure-ink-muted` et `--epure-border`.
- **Îlots clairs** : dans un élément qu'un plugin peint en blanc quel que soit le mode, le thème remet les surfaces et le texte à leurs valeurs claires (mixin SCSS `epure-light-island`).

Les noms de ces variables sont stables d'une version à l'autre. Un renommage ou une suppression serait annoncé dans les notes de mise à jour du [README](../README.md#upgrade-notes).
