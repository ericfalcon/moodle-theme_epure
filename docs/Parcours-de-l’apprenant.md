Ce que voient les apprenants et les enseignants, du tableau de bord jusqu’aux activités.

## Tableau de bord de l'apprenant

Pour un utilisateur qui suit des cours, le tableau de bord commence par un aperçu, au-dessus des blocs de Moodle :

- **Reprendre où vous en étiez** : le dernier cours visité et pas encore terminé, avec son image, sa progression, sa prochaine activité et un bouton pour continuer ;
- **En cours** : les autres cours en cours, avec leur progression, et un lien vers « Mes cours » ;
- **Cette semaine** : sa semaine jour par jour dans tous ses cours (échéances des activités avec leur état, séances et événements du calendrier), les activités en retard d'abord ;
- **Terminé** : les cours terminés, avec leur date, et un lien vers ses attestations quand la plateforme en délivre (IOMAD, Certificate, Custom certificate).

L'aperçu se désactive dans l'onglet Cours du thème (« Aperçu de l'apprenant sur le tableau de bord ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. Sur téléphone, le tableau de bord peut ne montrer que l'aperçu, sans les blocs (onglet Mobile, « Tableau de bord sur téléphone »).

## Page de cours

En tête de chaque cours, une **bannière** reprend l'image du cours (ou un motif généré), sa catégorie et son titre, avec le fil d'Ariane et les actions habituelles de Moodle. Ensuite, selon le rôle :

- **apprenant** : sa progression, la prochaine activité à faire, la prochaine échéance, et un bouton **Continuer** qui y mène ;
- **enseignant** : les participants, les apprenants actifs cette semaine, les devoirs à corriger, et des accès directs (participants, notes, paramètres) ;
- **visiteur** (invité, utilisateur non inscrit) : l'image, la catégorie et le titre seulement.

Pour l'apprenant, le titre de chaque section indique combien de ses activités il a terminées (« 2/5 », coche une fois la section terminée). La bannière se désactive dans l'onglet Cours du thème (« Bannière des cours ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. La progression des sections reste affichée sans la bannière, et se désactive à part (« Progression des sections »). Les barres de progression et les sections terminées prennent la couleur d'accent, si elle est réglée (onglet Identité).

## Sous-sections

Depuis Moodle 4.5, une section peut contenir des sous-sections (activité « Sous-section »). Moodle range leurs activités à la fin du cours ; Épure les remet à la place de la sous-section, comme sur la page du cours :

- la prochaine activité (bannière, Mes cours, tableau de bord), la position « Activité 3 sur 12 » et les activités précédente et suivante suivent cet ordre ; les cartes précédente et suivante indiquent « Section › Sous-section » ;
- une section compte les activités de ses sous-sections dans sa progression, et chaque sous-section a la sienne ;
- le programme de la présentation du cours compte les activités d'une sous-section dans sa section ;
- sur la page du cours, les sous-sections se distinguent par un fond teinté et une bande de la couleur de marque, en clair comme en sombre.

## Catalogue des cours

La page des cours (`/course/index.php`) devient un **catalogue** : les sous-catégories en pastilles avec leur nombre de cours, puis les cours de la catégorie et de ses sous-catégories en **cartes** (image, catégorie, nom, résumé, enseignants). Chaque carte indique si l'utilisateur est **inscrit**, ou comment entrer dans le cours (**inscription libre**, **accès invité**). La barre de Moodle reste en haut (menu des catégories, recherche, actions de gestion). Les résultats de la recherche de cours s'affichent avec les mêmes cartes.

Avant l'inscription, la page d'inscription d'un cours le **présente** : bannière avec l'image et un bouton vers les options d'inscription, résumé, **programme** (sections et nombre d'activités), enseignants, dates, contenu par type d'activité et champs personnalisés du cours. Les sections et activités cachées n'y figurent pas.

Avec IOMAD, le catalogue ne montre que les catégories et les cours qu'IOMAD autorise à l'utilisateur. Le catalogue et la présentation se désactivent dans l'onglet Cours du thème (« Catalogue des cours »).

## Pages d'activité

Dans Moodle 4 et 5, avec le sommaire latéral, les liens vers l'activité précédente et suivante ont disparu. Épure les rétablit sur chaque page d'activité :

- **en haut**, une bande avec le cours (lien de retour), la position de l'activité (« Activité 3 sur 12 »), la progression de l'apprenant et un bouton **Mode lecture** ;
- **en bas**, l'activité précédente et la suivante en cartes (icône, nom, section, ou « Section › Sous-section » pour une activité d'une sous-section) ; après la dernière, une carte ramène au cours. Les étiquettes et les activités invisibles pour l'utilisateur sont ignorées.

Le **mode lecture** masque les panneaux latéraux et centre le contenu ; il est gardé dans le profil de l'utilisateur, et la touche Échap le quitte. Le bouton **Marquer comme terminé** est agrandi et prend la couleur de la marque. Ces ajouts se désactivent dans l'onglet Cours du thème (« Pages d'activité »).
