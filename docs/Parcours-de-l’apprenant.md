Ce que voient les apprenants et les enseignants, du tableau de bord jusqu’aux activités.

## Tableau de bord de l'apprenant

Pour un utilisateur qui suit des cours, le tableau de bord commence par un aperçu, au-dessus des blocs de Moodle :

- **Reprendre où vous en étiez** : le dernier cours visité et pas encore terminé, avec son image, sa progression, sa prochaine activité et un bouton pour continuer ;
- **En cours** : les autres cours en cours, avec leur progression, et un lien vers « Mes cours » ;
- **Cette semaine** : sa semaine jour par jour dans tous ses cours (échéances des activités avec leur état, séances et événements du calendrier), les activités en retard d'abord ;
- **Terminé** : les cours terminés, avec leur date, et un lien vers ses attestations quand la plateforme en délivre (IOMAD, Certificate, Custom certificate).

Dans un cours au format **Section tabs** (1.4 ou plus) sans activité à continuer, le bouton **Continuer** du tableau de bord et de « Mes cours » mène à la dernière section et sous-section consultées (« Vous en étiez à : Bloc 1 › Exercices »). Sur téléphone, dans un tel cours, la barre de navigation d'Épure ajoute une rangée **Précédent / Suivant** qui reprend celle du format ; la barre du format, en bas de la section, n'est alors plus affichée sur téléphone, pour n'avoir qu'une paire de boutons.

L'aperçu se désactive dans l'onglet Cours du thème (« Aperçu de l'apprenant sur le tableau de bord ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. Sur téléphone, le tableau de bord peut ne montrer que l'aperçu, sans les blocs (onglet Mobile, « Tableau de bord sur téléphone »).

## Page « À faire »

Chaque apprenant a une page « À faire » : toutes ses activités restant à faire avec une échéance, dans tous les cours qu'il suit, groupées par échéance (en retard, cette semaine, plus tard), chacune avec son cours, sa date et son état (« Échéance proche », « En cours »…). La partie « Cette semaine » du tableau de bord y mène (« Tout ce qui est à faire »). Un formulaire, qui fonctionne sans script, la filtre par cours et par échéance ; la page dit combien d'activités sont affichées, et le dit clairement quand il ne reste rien à faire. Ce sont les mêmes activités que celles du tableau de bord : avec suivi d'achèvement, une échéance, et pas encore terminées. Aucun réglage.

## Ma synthèse

Avec le réglage « Ma synthèse » (onglet Cours, désactivé par défaut), l'apprenant trouve un lien « Ma synthèse » dans la partie « Terminé » de sa vue d'ensemble, dans le menu utilisateur (après « Notes ») et dans les rapports de son profil. La page liste les cours qu'il suit avec leur progression, la date d'achèvement, sa note (quand le cours montre les notes aux apprenants) et les activités terminées avec leur date, puis ses attestations. Ses propres données seulement. Un bouton imprime la page, aux couleurs de la marque. Une note dit ce qu'elle prouve : ce que la plateforme a enregistré, pas à elle seule l'assiduité ni l'atteinte des objectifs.

**PDF avec un code de vérification.** « Télécharger le PDF » émet un document : la synthèse de ce moment, aux couleurs de la marque (de l'entreprise IOMAD de l'apprenant avec IOMAD), avec un code de vérification (douze lettres et chiffres, par exemple ABCD-EFGH-JKMN) et un QR code. Chaque document est conservé avec ce qu'il montrait, sa date et son code. Qui le reçoit (employeur, financeur, auditeur) scanne le QR code ou saisit le code sur la page de vérification (`/theme/epure/verify.php`, ouverte sans connexion, comme les codes des attestations de Moodle) : la plateforme montre ce qu'elle a émis sous ce code, à quelle date, pour le comparer au document. Un code inconnu indique qu'aucun document ne le porte. Avant le téléchargement, la page liste ce que verra le destinataire, et l'apprenant choisit combien de temps le document est vérifiable : 3 mois, 1 an (par défaut) ou sans limite. En dessous, les documents émis, chacun avec son code, sa date, sa validité et son état, se révoquent (la page de vérification indique alors seulement qu'il a été émis puis révoqué) ou se suppriment (le code n'existe plus). Une tâche planifiée efface le contenu des documents expirés. Le PDF est balisé pour les lecteurs d’écran (PDF/UA-1, ISO 14289-1) : ses titres, paragraphes, tableaux, liens et le QR code avec son texte sont dans sa structure, dans la langue de la page, avec des polices intégrées (DejaVu Sans, pour les lettres de presque toutes les langues) ; le bandeau de la marque et le pied de page sont des décorations, que les lecteurs d’écran sautent. Il est produit par tc-lib-pdf, le successeur de TCPDF par le même auteur (LGPL, fourni dans `thirdparty/tclib`), qui demande PHP 8.2 ; chaque version d’Épure le vérifie avec veraPDF, le validateur du PDF/UA. Avec PHP 8.1 (Moodle 4.5), le PDF est produit par la bibliothèque PDF de Moodle (TCPDF), non balisé : la page reste alors la version accessible, et le dit. Les documents sont exportés et supprimés par l'API de confidentialité, et supprimés avec l'utilisateur.

## Page de cours

En tête de chaque cours, une **bannière** reprend l'image du cours (ou un motif généré), sa catégorie et son titre, avec le fil d'Ariane et les actions habituelles de Moodle. Ensuite, selon le rôle :

- **apprenant** : sa progression, la prochaine activité à faire, la prochaine échéance, et un bouton **Continuer** qui y mène ;

- **enseignant** : les participants, les apprenants actifs cette semaine, les devoirs à corriger, et des accès directs (participants, notes, paramètres), avec, sur téléphone, « Activer le mode édition » (l'interrupteur de l'en-tête peut être poussé hors de l'écran par les boutons de plugins) ;
- **visiteur** (invité, utilisateur non inscrit) : l'image, la catégorie et le titre seulement.

Pour l'apprenant, le titre de chaque section indique combien de ses activités il a terminées (« 2/5 », coche une fois la section terminée). La bannière se désactive dans l'onglet Cours du thème (« Bannière des cours ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. La progression des sections reste affichée sans la bannière, et se désactive à part (« Progression des sections »). Les barres de progression et les sections terminées prennent la couleur d'accent, si elle est réglée (onglet Identité).

**Sous-sections** (Moodle 4.5 et suivants) : une section compte les activités de ses sous-sections dans sa progression, et chaque sous-section a la sienne. Les activités d'une sous-section viennent à la place de la sous-section dans l'ordre du cours, pour la prochaine activité (bannière, Mes cours, tableau de bord), la position dans le cours et les activités précédente et suivante. Sur la page du cours, les sous-sections se distinguent par un fond teinté et une bande de la couleur de marque, en clair comme en sombre.

## Nouvelles activités

Sur la page d'un cours, un badge « Nouveau » marque chaque activité ajoutée depuis la visite précédente de l'apprenant, avec « depuis votre dernière visite » pour les lecteurs d'écran (un texte, pas seulement une couleur). Sur la carte du cours (« Mes cours »), leur nombre : « 2 nouvelles activités depuis votre dernière visite ». La visite précédente est le dernier accès au cours avant la session en cours, que Moodle lit à la connexion : la même sur chaque appareil, rien n'est gardé dans le navigateur, aucun réglage. Un cours jamais visité n'en montre aucune.

## Aperçu des fichiers

Sur la page d'un cours, le fichier d'une ressource s'ouvre dans une fenêtre au-dessus du cours, sans le quitter : un PDF dans le lecteur du navigateur, une image, une vidéo ou un son avec ses lecteurs, un fichier texte dans la page (avec la police, la taille et les couleurs choisies par l'utilisateur). Un document Word, PowerPoint, Excel ou LibreOffice montre son texte, sans téléchargement : titres, paragraphes, listes et tableaux, chaque diapositive, chaque feuille, avec les textes alternatifs de ses images ; une version lisible au clavier et au lecteur d'écran, « Télécharger » restant là pour la mise en page. En haut, le nom du fichier et « Fichier 2 sur 5 » ; en bas, les fichiers précédent et suivant de la page (boutons, et flèches du clavier sur les boutons), « Ouvrir dans un nouvel onglet » et « Télécharger ». C'est une vraie fenêtre modale : le focus va sur « Fermer l'aperçu », Échap la ferme, une vidéo s'arrête et le focus revient au lien. Ouvrir un fichier compte comme le consulter (achèvement « consulter » compris). Aucun réglage.

**Quand l'aperçu s'ouvre.** Il faut que :

- l'utilisateur soit connecté (pas en invité) et que le **mode édition soit désactivé** ;
- le clic parte de la **page du cours** ou de celle d'une section (pas de l'index du cours ni d'une autre page), sans Ctrl, Maj ni le bouton du milieu, qui gardent leur comportement ;
- la ressource ait, dans ses paramètres (Apparence → Affichage), **Automatique** (la valeur par défaut de Moodle), **Intégrer**, **Dans un cadre** ou **Ouvrir**. Avec **Forcer le téléchargement**, **Nouvelle fenêtre** ou **Pop-up**, Moodle garde son comportement habituel ;
- le fichier principal de la ressource soit d'un type que l'aperçu sait montrer (PDF, image, vidéo, son, texte, document bureautique) ; une archive ZIP ou un fichier d'un autre type se télécharge comme d'habitude.

**Si rien ne s'ouvre** : désactivez le mode édition, puis ouvrez les paramètres de la ressource, Apparence → Affichage : si elle est en « Forcer le téléchargement », « Nouvelle fenêtre » ou « Pop-up », passez-la en « Automatique ». Vérifiez aussi le type du fichier. Pour toutes les nouvelles ressources, l'administrateur choisit la valeur par défaut dans Administration du site → Plugins → Modules d'activité → Fichier (« Affichage »).

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
