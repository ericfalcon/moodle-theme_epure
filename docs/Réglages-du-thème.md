Les réglages se trouvent dans Administration du site › Présentation › Thèmes › Épure.

Les réglages sont rangés par sujet en onglets : Identité, Navigation, Cours, Accessibilité, Apparence, Mobile, Page de connexion, Pied de page et Réglages avancés. Les réglages gardent leur nom d'une version à l'autre ; quand l'un est déplacé ou renommé, les notes de mise à jour du [README](../README.fr.md#notes-de-mise-à-jour) l'indiquent.

## Identité

Les logos viennent en premier : leurs couleurs sont ensuite proposées pour la couleur de marque, juste en dessous.

| Réglage | Effet |
|---|---|
| Logo | Affiché dans l'en-tête et sur la page de connexion. Les fonds transparents sont pris en charge (SVG, PNG, WebP). Sans logo, ceux d'Apparence › Logos sont utilisés. |
| Logo pour l'en-tête en couleur | Facultatif : une version lisible sur la couleur de marque, souvent blanche sur fond transparent. |
| Logo pour téléphone | Facultatif : une version petite ou carrée du logo, affichée dans l'en-tête des téléphones à côté du bouton de menu, là où Moodle n'affiche aucun logo. |
| Favicon | Facultatif : l'icône du site dans les onglets des navigateurs et les favoris (ICO, PNG ou SVG). Sans elle, celle de Présentation › Logos. |
| Couleur de marque | Toute la palette en est déduite : survols, fonds teintés, couleur des liens. Les couleurs sont ajustées automatiquement pour respecter les contrastes AA des WCAG 2.2, et le contraste obtenu est affiché sous le réglage. |
| Couleur d'accent | Facultative : la couleur des barres de progression et des sections terminées, pour les distinguer de la couleur de marque. Vide : la couleur de marque. Avec IOMAD, une entreprise qui a sa propre couleur la garde partout. |
| Couleur de l'en-tête | Blanc avec soulignement de la couleur de marque, ou rempli de la couleur de marque. Les textes et icônes prennent la couleur la plus lisible. |

Sous la couleur de marque, le thème propose les **couleurs du logo**, y compris les couleurs d'accent qui occupent peu de place (lettrage fin, petit emblème), et celles d'un logo tout juste déposé plus haut, avant même l'enregistrement : cliquez sur une pastille, ou directement sur un point du logo (pipette), puis enregistrez. Seuls les codes hexadécimaux sont acceptés, car la palette accessible en est calculée.

L'onglet se termine par les **e-mails** aux couleurs de la marque (voir [Mode sombre et e-mails](Mode-sombre-et-e-mails.md)) et un lien vers la page « Épure : vocabulaire ».

## Navigation

| Réglage | Effet |
|---|---|
| Barre principale | Le menu principal de Moodle et les éléments du menu personnalisé, réglés dans Présentation › Réglages thème avancés (l'onglet y mène). |
| Recherche rapide | La fenêtre de recherche de l'en-tête, Ctrl+K (voir [Navigation et recherche](Navigation-et-recherche.md)). |
| Fil d'Ariane | Le chemin de la page au-dessus de son titre : affiché, affiché sur les grands écrans seulement, ou masqué. |
| Menu utilisateur | Des liens s'ajoutent dans Présentation › Réglages thème avancés (Éléments du menu utilisateur) ; l'onglet y mène. |
| Vue simplifiée de l'apprenant | Désactivée par défaut ; réglable par entreprise IOMAD. Pour qui ne gère aucune activité : la navigation d'un cours réduite à Cours, Notes et Badges (les autres pages sous « Plus »), les autres pages d'une activité sous « Plus », le menu utilisateur sans calendrier, fichiers personnels ni rapports, le tiroir des blocs fermé à l'ouverture d'une page, un bouton d'aide discret. Rien n'est retiré. Quel que soit le réglage, « Badges » n'apparaît dans un cours que s'il a un badge actif, ou pour qui peut y créer des badges. |

## Cours

| Réglage | Effet |
|---|---|
| Mes cours par rôle | Voir Page Mes cours, ci-dessous. |
| Toutes les entreprises dans Mes cours (IOMAD) | Décoché : comme IOMAD, seulement les cours de l’entreprise où l’utilisateur est connecté. Coché : un apprenant de plusieurs entreprises voit tous ses cours, ceux de ses autres entreprises avec le nom de l’entreprise, et chaque cours s’ouvre dans son entreprise (à ses couleurs, son activité comptée pour elle). Pas pour les gestionnaires ni les administrateurs. |
| Catalogue des cours | Voir [Parcours de l'apprenant](Parcours-de-l%E2%80%99apprenant.md). |
| Bannière des cours | Voir [Parcours de l'apprenant](Parcours-de-l%E2%80%99apprenant.md). |
| Progression des sections | Sur la page du cours, combien de ses activités l'apprenant a terminées dans chaque section et sous-section. |
| Aperçu de l'apprenant sur le tableau de bord | Voir [Parcours de l'apprenant](Parcours-de-l%E2%80%99apprenant.md). |
| Icônes d'activités | Dans la couleur de marque (par défaut), dans les couleurs de Moodle, une par type d'activité (évaluation, contenu, communication…), ou masquées pour des pages de cours plus sobres (le sélecteur d'activités les garde). Avec IOMAD, chaque entreprise peut faire un autre choix. |
| Pages d'activité | La bande en haut et les activités précédente et suivante (voir [Parcours de l'apprenant](Parcours-de-l%E2%80%99apprenant.md)). |

## Accessibilité

Les **préférences d'affichage** : le bouton « Aa » de l'en-tête peut être désactivé, et l'affichage par défaut du site se règle pour les visiteurs et pour les utilisateurs qui n'ont pas choisi le leur (voir [Accessibilité](Accessibilit%C3%A9.md)). Puis la **déclaration d'accessibilité**.

## Apparence

| Réglage | Effet |
|---|---|
| Police | Polices fournies : IBM Plex Sans, Inter, Source Sans 3, Figtree, Lato, et pour la lisibilité Atkinson Hyperlegible, Lexend, OpenDyslexic. Ou votre propre police téléversée. |
| Police téléversée | Nom de la police et fichiers `.woff2` ou `.woff` (normal obligatoire, gras facultatif). Vérifiez que la licence de la police autorise l'usage sur un site web. |
| Arrondis | Peu arrondis (coins presque droits), moyennement arrondis (par défaut) ou très arrondis : cartes, boutons, champs, pastilles, et sections et activités des pages de cours, chaque niveau un peu moins arrondi que celui qui le contient (section, puis activités et sous-sections, puis activités d'une sous-section). Avec « Peu arrondis », les pastilles deviennent des rectangles aux coins adoucis. |
| Densité | Confortable, ou compacte : moins d'espace autour et entre les éléments, pour plus de contenu à l'écran. |
| Mode sombre | Jamais, automatique comme l'appareil, ou toujours. |

Toutes les polices sont servies par votre propre site. Le thème ne fait **aucun appel à Google Fonts** ni à un autre service externe.

## Mobile

| Réglage | Effet |
|---|---|
| Barre de navigation mobile | Voir [Navigation et recherche](Navigation-et-recherche.md). |
| Blocs sur téléphone | Dans leur tiroir, ouvert par un bouton sur le côté de l'écran, ou masqués, pour des pages réduites à leur contenu. |
| Tableau de bord sur téléphone | La vue d'ensemble de l'apprenant et les blocs, ou la vue d'ensemble seule. |
| Application Moodle aux couleurs de la marque | Voir [Activités aux couleurs de la marque](Activit%C3%A9s-aux-couleurs-de-la-marque.md). |
| Application web installable | Désactivée par défaut ; avec son nom et son icône (voir [Navigation et recherche](Navigation-et-recherche.md)). |

## Page Mes cours

Les cours que vous **animez** et ceux que vous **suivez** sont présentés en deux sections (« Formations que j'anime », « Formations que je suis », avec vos mots de vocabulaire). Avec un seul rôle, la page affiche une liste unique.

| Carte d'un cours suivi | Carte d'un cours animé |
|---|---|
| Progression, prochaine activité à faire, prochaine échéance, bouton Commencer / Continuer / Revoir, mention Terminé | Bandeau et badge de rôle, participants, apprenants actifs cette semaine, devoirs à corriger, accès directs Participants, Notes, Paramètres |

Une recherche (insensible aux accents) et des filtres En cours, À venir, Passés complètent la page. Les cours favoris viennent en premier, puis les plus récemment consultés ; les cours que vous avez masqués restent masqués. Un enseignant est reconnu à sa capacité de voir toutes les notes du cours (`moodle/grade:viewall`). Avec IOMAD, qui remplace le bloc de Moodle par le sien (« Mes cours » avec les onglets disponibles, en cours, terminés), la même présentation s'applique, avec en plus une section « Formations disponibles » et le bouton de téléchargement des certificats d'IOMAD. Réglage « Mes cours par rôle » (onglet Cours) : décochez pour retrouver le bloc de Moodle ou d'IOMAD.

## Page de connexion

| Réglage | Effet |
|---|---|
| Disposition | Écran partagé : un visuel de la couleur de marque à côté du formulaire de Moodle (sur téléphone, seul le formulaire s'affiche). Ou formulaire centré. |
| Accroche et texte d'accompagnement | Affichés sur le visuel. Sans accroche, une phrase par défaut est utilisée. |
| Image de fond | Facultative. Un voile de la couleur de marque est posé dessus, avec l'opacité nécessaire pour que le texte reste lisible (contraste AA) quelle que soit l'image. |

Le formulaire lui-même reste celui de Moodle : il suit chaque version (4.5 à 5.3) et les méthodes d'authentification configurées.

## Pied de page

En bas de chaque page, page de connexion comprise : le nom du site, un texte libre (par exemple l'adresse de l'établissement), puis les liens **Mentions légales**, **Données personnelles**, **Accessibilité : …** (une fois la déclaration publiée) et **Contact**, et des liens libres. Il se règle dans l'onglet **Pied de page** des réglages du thème ; avec IOMAD, chaque entreprise peut avoir ses propres texte et liens (fiche de l'entreprise › Apparence). Laissés vides, les liens « Données personnelles » et « Contact » reprennent ceux de Moodle quand il en a (politiques du site, formulaire du support).

## Réglages avancés

SCSS initial (pour redéfinir des variables) et SCSS ajouté à la fin de la feuille de style.
