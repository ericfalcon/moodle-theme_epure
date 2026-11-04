Les icônes d'activités, les contenus H5P, les tests, les leçons, les forums, les glossaires, les devoirs, les SCORM, les livres, les badges, les attestations IOMAD et l'application Moodle prennent la couleur de marque, celle de l'entreprise avec IOMAD. Les sections et activités des pages de cours suivent aussi les arrondis choisis.

## Icônes d'activités

Réglage « Icônes d'activités » (onglet Cours, partie Activités) : dans la couleur de marque (par défaut), dans les couleurs de Moodle, une par type d'activité (évaluation, contenu, communication…), ou masquées, pour des pages de cours plus sobres. Masquées, elles disparaissent de la page de cours, des blocs du tableau de bord, du calendrier et des pages d'activité ; le sélecteur d'activités des formateurs les garde. Avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche (Identité › Couleurs).

## Contenus et activités

- **H5P** : H5P affiche ses contenus dans un cadre à part, que les styles du thème n'atteignent pas. Épure leur ajoute une feuille de style dans la couleur de marque (celle de l'entreprise avec IOMAD) : boutons, réponses choisies, barres de score et de progression, pour les types de contenus récents (variables de thème de H5P) et plus anciens (Course Presentation, Interactive Video…). Le vert et le rouge des réponses justes et fausses sont gardés. Les contenus qui viennent d'ailleurs (paquets SCORM, outils externes, PDF, vidéos intégrées) gardent leurs propres couleurs.
- **Tests** : la question sur une teinte légère de la marque, les commentaires dans un cadre neutre, et une navigation du test plus lisible (numéro au centre, page en cours dans la couleur de marque, questions répondues teintées, réponses justes et fausses marquées en bas de leur bouton).
- **Leçons** : les réponses les unes sous les autres, en cartes, celle choisie mise en valeur, et la barre de progression dans la couleur de marque.
- **Boutons radio et cases à cocher**, partout, dans la couleur de marque.
- **Forums** : les messages en cartes, le premier marqué dans la couleur de marque, les réponses sur une ligne qui montre le fil.
- **Glossaires** : l'index en boutons, les lettres dans la couleur de marque, les entrées en cartes.
- **Devoirs** : l'état de la remise en carte, sans rayures.
- **SCORM** : la description et les informations en encadrés ; dans le lecteur, un sommaire avec l'élément en cours dans la couleur de marque et des boutons de navigation du thème. Le contenu du paquet garde ses propres couleurs.
- **Livres et pages** : le chapitre en cours, les flèches des chapitres et les citations dans la couleur de marque, le texte à une largeur lisible.
- **Badges** : les badges de l'utilisateur en cartes ; la page d'un badge avec son image sur une teinte de la marque.
- **Rapports** : la ligne sous le pointeur mise en valeur dans le carnet de notes, le rapport d'achèvement et les rapports IOMAD ; la cellule mise en avant des résultats d'un test dans une teinte de la marque.
- **Ateliers** : les phases aux couleurs du thème, celle en cours dans une teinte de la marque au lieu du vert anis ; en mode sombre, des icônes de tâches claires.
- **Feedback** : les titres de la vue d'ensemble plus petits ; les questions en cartes numérotées, les réponses d'un choix multiple en lignes cliquables.
- **Calendrier** : les événements du mois en étiquettes teintées de la couleur de leur type (site, cours, catégorie, groupe, utilisateur), sur deux lignes, au lieu d'un nom coupé derrière une petite pastille ; les week-ends teintés ; les cartes du jour et des événements à venir marquées de la couleur de leur type. Sur téléphone, les mois précédent et suivant tiennent sur une ligne.
- **Formulaires de paramètres** : l'aide en gris (couleur de marque au survol) au lieu du bleu-vert de Moodle, des titres de sections plus petits, des listes de date plus étroites, les éléments choisis d'un champ à complétion en étiquettes teintées de la marque.
- **Messagerie** : les messages en bulles (les miens teintés de la marque à droite, ceux des autres neutres à gauche), les jours en intertitres, la conversation survolée teintée de la marque, le champ de saisie arrondi ; sur téléphone, la conversation ouverte au-dessus de la liste des conversations.
- **Profil et préférences** : la personne dans un en-tête teinté de la marque, les liens des cartes en lignes de menu (toute la ligne est cliquable, une flèche à droite), « Modifier le profil » en petit bouton.
- **Sondage (choix)** : chaque réponse en carte cliquable, la réponse choisie marquée de la marque.
- **Graphiques** : les graphiques des pages (résultats d'un sondage, rapports…) dans des nuances de la couleur de marque, lisibles en clair comme en sombre, sauf si le site a défini ses propres couleurs de graphiques (`$CFG->chart_colorset`).
- **Base de données** : les fiches en cartes compactes, les noms des champs en petits titres gris, la recherche et le tri regroupés dans un encadré.
- **Wiki** : le sommaire en encadré marqué de la marque, le texte à une largeur lisible.

## Attestations IOMAD

Dans la fiche entreprise (Identité › Attestations), la case « Utiliser ce cadre pour les attestations de l'entreprise » remplace le cadre des attestations IOMAD de l'entreprise (Modifier l'entreprise › Certificat) par un cadre dans sa couleur de marque, et active « Utiliser un cadre ». À cocher de nouveau après un changement de couleur.

Rien n'en reste quand l'entreprise quitte Épure : son ancien cadre et son réglage « Utiliser un cadre » sont mis de côté, et reviennent dès que l'entreprise, ou le site pour une entreprise sans thème propre, n'utilise plus Épure, avant toute création d'attestation. Avec IOMAD 5.1, qui a déplacé le suivi des achèvements dans `local_iomad`, c'est aussi le cas sur ses nouvelles pages et dans les tâches planifiées (voir [IOMAD](IOMAD.md)).

## Application Moodle

Réglage « Application Moodle aux couleurs de la marque » (onglet Mobile, partie Applications) : l'application Moodle prend la couleur de marque, l'en-tête et la police du thème, en mode clair et sombre ; avec IOMAD, ceux de l'entreprise de l'utilisateur, et ceux du site sur l'écran de connexion. Le thème règle la feuille de style de l'application (Administration du site › Application mobile › Apparence mobile › CSS) et lui donne une nouvelle adresse à chaque changement d'apparence, pour que les applications la téléchargent de nouveau ; désactivé, il la retire. Un utilisateur qui voit le site avec un autre thème reçoit une feuille vide.

Le même onglet propose une [application web installable](Navigation-et-recherche.md), ajoutée à l'écran d'accueil depuis le navigateur.
