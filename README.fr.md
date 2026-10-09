# Épure (theme_epure)

Thème moderne, sobre et accessible pour **Moodle 4.5 LTS et 5.x**, basé sur Boost.

## Installation

1. Copiez le dossier `epure` dans le dossier `theme` de votre Moodle (`public/theme` à partir de Moodle 5.1).
2. Connectez-vous en administrateur et lancez la mise à jour de la base de données.
3. Choisissez Épure dans Administration du site › Apparence › Thèmes.

## Réglages

Administration du site › Apparence › Thèmes › Épure.

Les réglages sont rangés par sujet en onglets : Identité, Navigation, Cours, Accessibilité, Apparence, Mobile, Page de connexion, Pied de page et Réglages avancés. La version 0.26.0 les a déplacés dans ces onglets sans les renommer : les valeurs déjà enregistrées sont conservées.

### Identité

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

L'onglet se termine par les **e-mails** aux couleurs de la marque (voir E-mails aux couleurs de la marque) et un lien vers la page « Épure : vocabulaire ».

### Navigation

| Réglage | Effet |
|---|---|
| Barre principale | Le menu principal de Moodle et les éléments du menu personnalisé, réglés dans Présentation › Réglages thème avancés (l'onglet y mène). |
| Recherche rapide | La fenêtre de recherche de l'en-tête, Ctrl+K (voir Recherche rapide). |
| Fil d'Ariane | Le chemin de la page au-dessus de son titre : affiché, affiché sur les grands écrans seulement, ou masqué. |
| Menu utilisateur | Des liens s'ajoutent dans Présentation › Réglages thème avancés (Éléments du menu utilisateur) ; l'onglet y mène. |

### Cours

| Réglage | Effet |
|---|---|
| Mes cours par rôle | Voir Page Mes cours. |
| Catalogue des cours | Voir Catalogue des cours. |
| Bannière des cours | Voir Page de cours. |
| Progression des sections | Sur la page du cours, combien de ses activités l'apprenant a terminées dans chaque section et sous-section. |
| Aperçu de l'apprenant sur le tableau de bord | Voir Tableau de bord de l'apprenant. |
| Icônes d'activités | Dans la couleur de marque (par défaut), dans les couleurs de Moodle, une par type d'activité (évaluation, contenu, communication…), ou masquées pour des pages de cours plus sobres (le sélecteur d'activités les garde). Avec IOMAD, chaque entreprise peut faire un autre choix. |
| Pages d'activité | La bande en haut et les activités précédente et suivante (voir Pages d'activité). |

### Accessibilité

Les **préférences d'affichage** : le bouton « Aa » de l'en-tête peut être désactivé, et l'affichage par défaut du site se règle pour les visiteurs et pour les utilisateurs qui n'ont pas choisi le leur (voir Accessibilité). Puis la **déclaration d'accessibilité**.

### Apparence

| Réglage | Effet |
|---|---|
| Police | Polices fournies : IBM Plex Sans, Inter, Source Sans 3, Figtree, Lato, et pour la lisibilité Atkinson Hyperlegible, Lexend, OpenDyslexic. Ou votre propre police téléversée. |
| Police téléversée | Nom de la police et fichiers `.woff2` ou `.woff` (normal obligatoire, gras facultatif). Vérifiez que la licence de la police autorise l'usage sur un site web. |
| Arrondis | Peu arrondis (coins presque droits), moyennement arrondis (par défaut) ou très arrondis : cartes, boutons, champs, pastilles, et sections et activités des pages de cours, chaque niveau un peu moins arrondi que celui qui le contient (section, puis activités et sous-sections, puis activités d'une sous-section). Avec « Peu arrondis », les pastilles deviennent des rectangles aux coins adoucis. |
| Densité | Confortable, ou compacte : moins d'espace autour et entre les éléments, pour plus de contenu à l'écran. |
| Mode sombre | Jamais, automatique comme l'appareil, ou toujours. |

Toutes les polices sont servies par votre propre site. Le thème ne fait **aucun appel à Google Fonts** ni à un autre service externe.

### Mobile

| Réglage | Effet |
|---|---|
| Barre de navigation mobile | Voir Barre de navigation mobile. |
| Blocs sur téléphone | Dans leur tiroir, ouvert par un bouton sur le côté de l'écran, ou masqués, pour des pages réduites à leur contenu. |
| Tableau de bord sur téléphone | La vue d'ensemble de l'apprenant et les blocs, ou la vue d'ensemble seule. |
| Application Moodle aux couleurs de la marque | Voir Application Moodle. |
| Application web installable | Désactivée par défaut ; avec son nom et son icône (voir Application web installable). |

### Page Mes cours

Les cours que vous **animez** et ceux que vous **suivez** sont présentés en deux sections (« Formations que j'anime », « Formations que je suis », avec vos mots de vocabulaire). Avec un seul rôle, la page affiche une liste unique.

| Carte d'un cours suivi | Carte d'un cours animé |
|---|---|
| Progression, prochaine activité à faire, prochaine échéance, bouton Commencer / Continuer / Revoir, mention Terminé | Bandeau et badge de rôle, participants, apprenants actifs cette semaine, devoirs à corriger, accès directs Participants, Notes, Paramètres |

Une recherche (insensible aux accents) et des filtres En cours, À venir, Passés complètent la page. Les cours favoris viennent en premier, puis les plus récemment consultés ; les cours que vous avez masqués restent masqués. Un enseignant est reconnu à sa capacité de voir toutes les notes du cours (`moodle/grade:viewall`). Avec IOMAD, qui remplace le bloc de Moodle par le sien (« Mes cours » avec les onglets disponibles, en cours, terminés), la même présentation s'applique, avec en plus une section « Formations disponibles » et le bouton de téléchargement des certificats d'IOMAD. Réglage « Mes cours par rôle » (onglet Cours) : décochez pour retrouver le bloc de Moodle ou d'IOMAD.

### Page de connexion

| Réglage | Effet |
|---|---|
| Disposition | Écran partagé : un visuel de la couleur de marque à côté du formulaire de Moodle (sur téléphone, seul le formulaire s'affiche). Ou formulaire centré. |
| Accroche et texte d'accompagnement | Affichés sur le visuel. Sans accroche, une phrase par défaut est utilisée. |
| Image de fond | Facultative. Un voile de la couleur de marque est posé dessus, avec l'opacité nécessaire pour que le texte reste lisible (contraste AA) quelle que soit l'image. |

Le formulaire lui-même reste celui de Moodle : il suit chaque version (4.5 à 5.3) et les méthodes d'authentification configurées.

### Avec IOMAD

Épure applique l'apparence que vous définissez pour chaque entreprise dans IOMAD (Tableau de bord IOMAD › Modifier l'entreprise › Apparence) :

| Réglage IOMAD de l'entreprise | Effet dans Épure |
|---|---|
| Couleur des titres (à défaut, couleur des liens) | Devient la couleur de marque de l'entreprise : la palette accessible est recalculée pour elle (en-tête, boutons, liens, contrastes AA). |
| Logo de l'entreprise | Remplace le logo d'Épure pour les utilisateurs de l'entreprise, dans l'en-tête des téléphones aussi. |
| CSS personnalisé | Ajouté aux pages des utilisateurs de l'entreprise. |

Ces réglages s'appliquent aux utilisateurs rattachés à l'entreprise, et à l'administrateur qui a sélectionné l'entreprise dans le tableau de bord IOMAD. Seuls les codes couleur hexadécimaux sont pris en compte. La couleur principale (fond de page) d'IOMAD n'est pas appliquée, pour préserver la lisibilité.

**Tableau de bord IOMAD** : le tableau de bord d'IOMAD garde ses actions, ses onglets et ses droits, mais Épure en change la présentation :

- l'entreprise sélectionnée en en-tête, avec son logo, un accès direct à sa catégorie de cours et à ses sous-catégories (page de gestion pour qui gère les cours, liste des cours sinon), lien repris en tête de l'onglet Cours, et le sélecteur d'entreprise à côté ;
- les chiffres clés de l'entreprise : utilisateurs (et actifs cette semaine), cours, licences utilisées sur celles attribuées, achèvements des 30 derniers jours ; chacun mène à la page d'IOMAD correspondante ;
- des onglets sobres, soulignés de la couleur de marque, qui défilent sur mobile ;
- les actions en cartes, **regroupées par intention** dans chaque onglet : Créer, Paramétrer, Gérer, Importer et exporter, Suivre ;
- la palette de l'entreprise à la place des couleurs fixes d'IOMAD, et des onglets accessibles aux lecteurs d'écran.

**Gérer les cours** (paramètres IOMAD des cours) : le tableau d'IOMAD gagne une colonne **Catégorie**, après celle du cours, avec le chemin complet de sa catégorie (par exemple « Clinique des Tilleuls / Soins infirmiers »).

**Tout se règle dans la fiche de l'entreprise** (Tableau de bord IOMAD › Créer une entreprise ou Modifier l'entreprise › Apparence). Épure range cette partie en étapes numérotées, organisées comme les onglets des réglages du thème, et y regroupe les champs d'IOMAD :

1. **Thème** : le thème de l'entreprise.
2. **Identité** : *Logos* (logo, logo compact et favicon d'IOMAD, puis le logo pour l'en-tête en couleur), puis *Couleurs* (couleur de marque, couleur de l'en-tête, couleurs des icônes d'activités), puis *Attestations*. Les logos viennent avant les couleurs, car leurs couleurs sont proposées pour la couleur de marque.
3. **Apparence** : police, mode sombre.
4. **Pages et navigation** : *Navigation* (barre de navigation mobile), *Pages* (bannière de cours, aperçu de l'apprenant).
5. **Pied de page**.
6. **Vocabulaire**.
7. **Réglages avancés** : CSS personnalisé et menu personnalisé d'IOMAD.

Dans les réglages du thème, les réglages de l'étape 4 sont rangés dans les onglets Cours (bannière, aperçu de l'apprenant) et Mobile (barre de navigation mobile).

Si l'entreprise choisit un autre thème qu'Épure, il ne reste rien d'Épure : le formulaire d'IOMAD s'affiche tel qu'IOMAD le présente (ordre, champs, remarque), et les utilisateurs de l'entreprise voient ce thème tel qu'il est sans Épure (mots d'origine du paquet de langue, e-mails de Moodle). Les réglages d'Épure de l'entreprise sont conservés et s'appliquent de nouveau si elle revient à Épure.

Une entreprise peut choisir Épure quel que soit le thème du site : même si le site utilise Iomad, les réglages d'Épure apparaissent dans le formulaire dès qu'Épure est choisi pour l'entreprise.

Le changement de thème s'applique dès la page suivante aux utilisateurs de l'entreprise déjà connectés (Moodle gardait sinon l'ancien thème jusqu'à leur prochaine connexion). Les pages prennent le thème de l'entreprise sélectionnée, y compris pour un administrateur : en choisissant une entreprise dans IOMAD, il voit les pages comme ses utilisateurs, dans son thème ; sans entreprise sélectionnée, c'est le thème du site.

Les réglages propres à Épure :

| Réglage de l'entreprise | Effet |
|---|---|
| Couleur de marque | Code couleur libre, sélecteur de couleur, ou couleurs du logo de l'entreprise (pastilles et pipette, y compris pour un logo tout juste téléversé). Vide : la couleur du titre d'IOMAD, sinon celle du site. La palette accessible est recalculée. Elle remplace aussi la couleur d'accent du site. |
| Couleur d'accent | Barres de progression et sections terminées. Vide : la couleur de marque de l'entreprise, sinon la couleur d'accent du site. |
| Couleur de l'en-tête | Comme le site, blanc, ou couleur de marque. |
| Arrondis | Comme le site, peu, moyennement ou très arrondis. Sur Moodle 4.5, les coins compilés dans Bootstrap 4 (champs, boutons de Moodle) gardent ceux du site ; ceux d'Épure et, à partir de Moodle 5.0, ceux de Bootstrap suivent l'entreprise. |
| E-mails aux couleurs de la marque | Comme le site, activé ou désactivé, selon l'entreprise du destinataire. |
| Bannière des cours | Comme le site, affichée ou masquée, pour les utilisateurs de l'entreprise. |
| Aperçu de l'apprenant sur le tableau de bord | Comme le site, affiché ou masqué. |
| Barre de navigation mobile | Comme le site, affichée ou masquée. |
| Mode sombre | Comme le site, jamais, automatique selon l'appareil, ou toujours. |
| Navigation | Comme le site, ou un autre choix pour le fil d'Ariane, la recherche rapide, les pages d'activité, les blocs et le tableau de bord sur téléphone. |
| Pages | Comme le site, ou un autre choix pour le catalogue des cours, la progression des sections et « Mes cours » par rôle. |
| Page de connexion | Disposition, accroche et texte d'accompagnement de sa page de connexion (son adresse propre ou son lien `login/index.php?id=…&code=…`), qui prend aussi ses couleurs, son logo et son nom. Champs vides : ceux du site. |
| Pied de page | Texte, mentions légales, données personnelles, contact, autres liens ; un champ vide reprend la valeur du site, affichée en grisé. |
| Logo pour l'en-tête en couleur | Facultatif : une version du logo lisible sur la couleur de marque de l'entreprise, souvent blanche sur fond transparent. |
| Police | Comme le site, l'une des 8 polices fournies, la police téléversée du site, ou une police téléversée pour l'entreprise (nom, fichiers normal et gras en woff2 ou woff ; vérifiez que sa licence autorise l'usage sur un site web). Sans fichier normal, la police du site est gardée. |

Les champs natifs d'IOMAD (logos, CSS et menu personnalisés) sont rangés dans ces étapes ; Épure se déclare thème IOMAD pour qu'IOMAD les affiche. Les couleurs d'IOMAD (titre, principale, lien), qui ne servent qu'aux thèmes IOMAD, sont masquées tant que l'entreprise utilise Épure ; une couleur du titre déjà enregistrée est reprise comme couleur de marque. Enfin, la section **Vocabulaire** règle les mots de l'entreprise, c'est-à-dire ses mots pour « entreprise » et « département », en français et en anglais (« client » et « équipe » pour l'une, « agence » et « service » pour une autre). Épure se déclare thème IOMAD pour qu'IOMAD affiche ces champs. Les mots pour toutes les entreprises se choisissent sur la page Épure : vocabulaire ; une entreprise sans mots propres utilise ceux-là.

Les paquets de langue étant communs à tout le site, ces mots sont appliqués au chargement des chaînes par un gestionnaire de chaînes que le thème active à chaque page (le mécanisme `$CFG->customstringmanager` de Moodle), avec un cache par entreprise. **Aucune modification de `config.php` n'est nécessaire.** Il n'est activé que si des mots sont choisis ; si `config.php` définit déjà un autre gestionnaire de chaînes, celui-ci est conservé et la page Épure : vocabulaire l'indique.

### Vocabulaire

Administration du site › Présentation › Thèmes › Épure : vocabulaire (lien aussi dans l'onglet Identité du thème).

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

## Pied de page

En bas de chaque page, page de connexion comprise : le nom du site, un texte libre (par exemple l'adresse de l'établissement), puis les liens **Mentions légales**, **Données personnelles**, **Accessibilité : …** (une fois la déclaration publiée) et **Contact**, et des liens libres. Il se règle dans l'onglet **Pied de page** des réglages du thème ; avec IOMAD, chaque entreprise peut avoir ses propres texte et liens (fiche de l'entreprise › Apparence). Laissés vides, les liens « Données personnelles » et « Contact » reprennent ceux de Moodle quand il en a (politiques du site, formulaire du support).

## Chiffres clés

Sur un Moodle sans IOMAD, le tableau de bord des gestionnaires de la plateforme commence par ses chiffres clés : utilisateurs (et actifs cette semaine), cours (et cours visibles), inscriptions actives, achèvements des 30 derniers jours. Avec IOMAD, ce sont ceux de l'entreprise sélectionnée, en tête du tableau de bord IOMAD.

## Installation et mises à jour : « Moodle travaille… »

Sur les pages d'installation et de mise à jour (mise à jour de Moodle, nouveaux réglages, plugins, installation depuis un fichier ZIP, vérification de l'environnement), un clic sur « Continuer », « Installer le plugin » ou « Mettre à jour la base de données maintenant » affiche, après une demi-seconde, une fenêtre « Moodle travaille… Ne fermez pas et ne rechargez pas cette page ». Elle évite un second clic et est annoncée aux lecteurs d'écran. Le script est écrit dans la page, sans le chargeur JavaScript de Moodle, pour fonctionner aussi pendant une mise à jour.

## Activités aux couleurs de la marque

- **H5P** : H5P affiche ses contenus dans un cadre à part, que les styles du thème n'atteignent pas. Épure leur ajoute une feuille de style dans la couleur de marque (celle de l'entreprise avec IOMAD) : boutons, réponses choisies, barres de score et de progression, pour les types de contenus récents (variables de thème de H5P) et plus anciens (Course Presentation, Interactive Video…). Le vert et le rouge des réponses justes et fausses sont gardés. Les contenus qui viennent d'ailleurs (paquets SCORM, outils externes, PDF, vidéos intégrées) gardent leurs propres couleurs.
- **Tests** : la question sur une teinte légère de la marque, les commentaires dans un cadre neutre, et une navigation du test plus lisible (numéro au centre, page en cours dans la couleur de marque, questions répondues teintées, réponses justes et fausses marquées en bas de leur bouton).
- **Leçons** : les réponses les unes sous les autres, en cartes, celle choisie mise en valeur, et la barre de progression dans la couleur de marque.
- **Boutons radio et cases à cocher**, partout, dans la couleur de marque.
- **Forums** : les messages en cartes, le premier marqué dans la couleur de marque, les réponses sur une ligne qui montre le fil. **Glossaires** : l'index en boutons, les entrées en cartes. **Devoirs** : l'état de la remise en carte. **SCORM** : les informations en encadrés, et dans le lecteur un sommaire avec l'élément en cours dans la couleur de marque. **Livres et pages** : le chapitre en cours, les flèches des chapitres et les citations dans la couleur de marque, le texte à une largeur lisible.
- **Badges** : les badges de l'utilisateur en cartes ; la page d'un badge avec son image sur une teinte de la marque.
- **Rapports** : la ligne sous le pointeur mise en valeur dans le carnet de notes, le rapport d'achèvement et les rapports IOMAD.
- **Attestations IOMAD** : dans la fiche entreprise (Identité › Attestations), un cadre dans la couleur de marque de l'entreprise peut remplacer le cadre de ses attestations. Quand l'entreprise, ou le site pour une entreprise sans thème propre, n'utilise plus Épure, son ancien cadre et son réglage « Utiliser un cadre » reviennent avant toute création d'attestation.

### Application Moodle

Avec le réglage « Application Moodle aux couleurs de la marque » (onglet Mobile), l'application Moodle prend la couleur de marque, l'en-tête et la police du thème, en mode clair et sombre, et avec IOMAD ceux de l'entreprise de l'utilisateur (ceux du site sur l'écran de connexion). Le thème règle la feuille de style de l'application (Administration du site › Application mobile › Apparence mobile › CSS) et lui donne une nouvelle adresse à chaque changement d'apparence ; désactivé, il la retire. Les utilisateurs qui voient le site avec un autre thème reçoivent une feuille vide.

## Application web installable

Avec le réglage « Application web installable » (onglet Mobile, désactivé par défaut), la plateforme peut être ajoutée à l'écran d'accueil des téléphones et des ordinateurs depuis le navigateur, comme une application : son icône, son nom, sa couleur, sans barre d'adresse. Sans passer par les magasins d'applications ni par l'application Moodle.

- Le thème ajoute un manifeste d'application web (nom du site, nom court tiré du réglage « Nom de l'application web » ou du nom abrégé du site, couleur de marque, icônes de 192 et 512 pixels), une icône pour iOS et la couleur de la barre du navigateur. Avec IOMAD, la couleur est celle de l'entreprise de l'utilisateur.
- L'icône est celle déposée (« Icône de l'application web », un PNG carré d'au moins 512 × 512 pixels) ; sans elle, le thème dessine l'initiale du site sur la couleur de marque.
- Un service worker, servi par `theme/epure/webapp/sw.php` pour tout le site, ne garde qu'une page « Vous êtes hors ligne », affichée quand une page ne peut pas se charger. Rien d'autre n'est mis en cache : les pages de Moodle viennent toujours du réseau.
- Quand l'application web est désactivée, ou pour un utilisateur qui ne voit plus Épure, le service worker se retire avec son cache. Les pages d'Épure le retirent tout de suite quand le réglage est désactivé ; avec un autre thème, le navigateur reçoit un service worker qui se retire de lui-même à sa vérification suivante (au plus une heure d'utilisation, ou la vérification quotidienne du navigateur).

Une fois le thème désinstallé, ses fichiers n'existent plus et les navigateurs gardent le dernier service worker, qui n'affiche la page hors ligne que sans réseau : désactivez l'application web et laissez les utilisateurs revenir sur le site avant de désinstaller Épure.

## Mode sombre

Le réglage **Mode sombre** (onglet Apparence du thème) vaut *Jamais*, *Automatique, selon l'appareil* ou *Toujours*. Avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. Chaque utilisateur peut aussi choisir son affichage dans ses préférences (bouton « Aa ») : comme le site, clair, sombre, ou selon son appareil.

Les couleurs sombres sont calculées à partir de la couleur de marque, avec les mêmes contrastes AA ; l'en-tête aux couleurs de la marque garde sa couleur. Les pages de Moodle et d'IOMAD (tableaux de bord, cours, formulaires, menus, tableaux) passent en sombre ; l'éditeur de texte garde l'apparence de son propre thème.

## Pages d'erreur, visites guidées et impression

- **Pages d'erreur** : au lieu de l'encadré rouge de Moodle, une carte aux couleurs de la marque dit en clair ce qui s'est passé (*Page introuvable*, *Accès refusé*, *Contenu inaccessible*, *Session expirée*, ou *Une erreur s'est produite*), garde le message de Moodle en dessous et mène au tableau de bord, ou à la page de connexion. La page « introuvable » de Moodle (`error/index.php`) prend la même carte.
- **Visites guidées** : leurs étapes prennent les couleurs du thème, en clair comme en sombre, et l'élément montré est entouré de la couleur de marque. Les visites livrées avec Moodle sont réservées par Moodle au thème Boost (filtre *Thème* de chaque visite) : pour les montrer avec Épure, ajoutez-le à ce filtre dans Administration › Apparence › Visites guidées.
- **Pages imprimées et PDF** (impression du navigateur) : en tête, le logo et le nom du site, ou de l'entreprise IOMAD, sur un filet de la couleur de marque ; puis le titre de la page et son contenu, sans les menus, panneaux et boutons de l'écran. Toujours en clair, même quand l'écran est en mode sombre.

## Tableau de bord de l'apprenant

Pour un utilisateur qui suit des cours, le tableau de bord commence par un aperçu, au-dessus des blocs de Moodle :

- **Reprendre où vous en étiez** : le dernier cours visité et pas encore terminé, avec son image, sa progression, sa prochaine activité et un bouton pour continuer ;
- **En cours** : les autres cours en cours, avec leur progression, et un lien vers « Mes cours » ;
- **À venir** : les prochaines échéances de tous ses cours (devoirs à rendre, tests qui ferment…) ;
- **Terminé** : les cours terminés, avec leur date, et un lien vers ses attestations quand la plateforme en délivre (IOMAD, Certificate, Custom certificate).

L'aperçu se désactive dans l'onglet Cours du thème (« Aperçu de l'apprenant sur le tableau de bord ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. Sur téléphone, le tableau de bord peut ne montrer que l'aperçu, sans les blocs (onglet Mobile, « Tableau de bord sur téléphone »).

## Page de cours

En tête de chaque cours, une **bannière** reprend l'image du cours (ou un motif généré), sa catégorie et son titre, avec le fil d'Ariane et les actions habituelles de Moodle. Ensuite, selon le rôle :

- **apprenant** : sa progression, la prochaine activité à faire, la prochaine échéance, et un bouton **Continuer** qui y mène ;
- **enseignant** : les participants, les apprenants actifs cette semaine, les devoirs à corriger, et des accès directs (participants, notes, paramètres) ;
- **visiteur** (invité, utilisateur non inscrit) : l'image, la catégorie et le titre seulement.

Pour l'apprenant, le titre de chaque section indique combien de ses activités il a terminées (« 2/5 », coche une fois la section terminée). La bannière se désactive dans l'onglet Cours du thème (« Bannière des cours ») ; avec IOMAD, chaque entreprise peut faire un autre choix dans sa fiche. La progression des sections reste affichée sans la bannière, et se désactive à part (« Progression des sections »). Les barres de progression et les sections terminées prennent la couleur d'accent, si elle est réglée (onglet Identité).

**Sous-sections** (Moodle 4.5 et suivants) : une section compte les activités de ses sous-sections dans sa progression, et chaque sous-section a la sienne. Les activités d'une sous-section viennent à la place de la sous-section dans l'ordre du cours, pour la prochaine activité (bannière, Mes cours, tableau de bord), la position dans le cours et les activités précédente et suivante. Sur la page du cours, les sous-sections se distinguent par un fond teinté et une bande de la couleur de marque, en clair comme en sombre.

## E-mails aux couleurs de la marque

Les e-mails HTML du site (notifications, messages, forums, devoirs, e-mails d'IOMAD…) reçoivent :

- le **logo** en tête (sinon le nom du site) et un **filet de la couleur de marque** ;
- le message dans une carte, ses liens dans la couleur de marque ;
- un **pied** avec le nom du site, le texte du pied de page et un lien « Gérer mes notifications ».

Avec IOMAD, ce sont le logo, la couleur, le nom et le texte du pied de page de **l'entreprise du destinataire**. Le gabarit utilise des tableaux et des styles en ligne, que lisent les logiciels de messagerie, et s'adapte aux écrans étroits. Épure remplace pour cela le gabarit `core/email_html` de Moodle, y compris pour les e-mails envoyés par les tâches planifiées. Les e-mails se désactivent dans l'onglet Identité du thème (« E-mails aux couleurs de la marque ») : ils reprennent alors la présentation de Moodle.

## Recherche rapide (Ctrl+K)

Un bouton **Rechercher** dans l'en-tête, ou **Ctrl+K** (**⌘K** sur Mac) depuis n'importe quelle page, ouvre une fenêtre de recherche. Elle trouve, sans tenir compte des majuscules ni des accents :

- les **pages des menus** : navigation principale, menu utilisateur, onglets du cours ou de la page (donc le tableau de bord IOMAD, l'administration, le calendrier, les notes… selon les droits de chacun) ;
- **mes cours** et les **activités** de mes cours ;
- les autres cours du **catalogue**, avec un lien vers la recherche complète ;
- pour les administrateurs, les **pages de l'administration** dont le nom ou un réglage correspond, avec un lien vers la recherche de l'administration.

Les flèches ↑ ↓ choisissent un résultat, Entrée l'ouvre (Ctrl+Entrée dans un nouvel onglet), Échap ferme. La fenêtre est une boîte de dialogue accessible (liste de choix annoncée aux lecteurs d'écran, nombre de résultats). La recherche se désactive dans l'onglet Navigation du thème (« Recherche rapide »).

La recherche rapide remplace le bouton de recherche de Moodle dans l'en-tête. Quand la recherche globale de Moodle est activée, les résultats se terminent par « Rechercher … dans tout le site », qui l'ouvre.

## Barre de navigation mobile

Sur téléphone, une barre fixée en bas de l'écran, à portée de pouce, mène au **tableau de bord** (ou à l'accueil du site si le tableau de bord est désactivé), à **mes cours**, au **catalogue**, aux **messages** (avec le nombre de conversations non lues) et au **profil** ; l'entrée de la page en cours est mise en évidence. Les boutons flottants de Moodle (aide, sommaire du cours) remontent au-dessus d'elle ; sur les pages qui ont leur propre barre d'actions en bas (notation, par exemple), elle s'efface. Elle se désactive dans l'onglet Mobile du thème (« Barre de navigation mobile ») et, avec IOMAD, pour chaque entreprise dans sa fiche.

## Catalogue des cours

La page des cours (`/course/index.php`) devient un **catalogue** : les sous-catégories en pastilles avec leur nombre de cours, puis les cours de la catégorie et de ses sous-catégories en **cartes** (image, catégorie, nom, résumé, enseignants). Chaque carte indique si l'utilisateur est **inscrit**, ou comment entrer dans le cours (**inscription libre**, **accès invité**). La barre de Moodle reste en haut (menu des catégories, recherche, actions de gestion). Les résultats de la recherche de cours s'affichent avec les mêmes cartes.

Avant l'inscription, la page d'inscription d'un cours le **présente** : bannière avec l'image et un bouton vers les options d'inscription, résumé, **programme** (sections et nombre d'activités), enseignants, dates, contenu par type d'activité et champs personnalisés du cours. Les sections et activités cachées n'y figurent pas.

Avec IOMAD, le catalogue ne montre que les catégories et les cours qu'IOMAD autorise à l'utilisateur. Le catalogue et la présentation se désactivent dans l'onglet Cours du thème (« Catalogue des cours »).

## Pages d'activité

Dans Moodle 4 et 5, avec le sommaire latéral, les liens vers l'activité précédente et suivante ont disparu. Épure les rétablit sur chaque page d'activité :

- **en haut**, une bande avec le cours (lien de retour), la position de l'activité (« Activité 3 sur 12 »), la progression de l'apprenant et un bouton **Mode lecture** ;
- **en bas**, l'activité précédente et la suivante en cartes (icône, nom, section, ou « Section › Sous-section » pour une activité d'une sous-section) ; après la dernière, une carte ramène au cours. Les étiquettes et les activités invisibles pour l'utilisateur sont ignorées.

Le **mode lecture** masque les panneaux latéraux et centre le contenu ; il est gardé dans le profil de l'utilisateur, et la touche Échap le quitte. Le bouton **Marquer comme terminé** est agrandi et prend la couleur de la marque. Ces ajouts se désactivent dans l'onglet Cours du thème (« Pages d'activité »).

## Accessibilité

Le thème vise les WCAG 2.2 niveau AA, le RGAA 4.1.2 et l'EN 301 549 : contrastes calculés, focus clavier toujours visible, lien d'évitement, cibles d'au moins 24 × 24 px, liens soulignés dans le texte, respect du réglage « réduire les animations ».

**Préférences d'affichage.** Le bouton **Aa** de l'en-tête (raccourci Alt + A) ouvre un panneau où chaque utilisateur connecté choisit :

- la taille du texte : petite, normale, grande ou très grande (90, 100, 115 ou 130 %) ;
- une police de lecture : celle du site, Atkinson Hyperlegible ou OpenDyslexic ;
- un texte plus espacé (valeurs du critère WCAG 1.4.12), un contraste renforcé, des liens soulignés, des animations réduites.

Le changement est immédiat, puis enregistré dans les préférences de son profil Moodle : il vaut sur toutes les pages et tous ses appareils, dès le premier affichage. Les visiteurs non connectés et les invités ont l'affichage par défaut du site, comme les utilisateurs qui n'ont pas choisi le leur : dans l'onglet Accessibilité du thème, l'administrateur règle par défaut la taille du texte, la police de lecture, le texte plus espacé, le contraste renforcé, les liens soulignés et les animations réduites. Le bouton « Aa » se désactive dans le même onglet ; les préférences déjà choisies continuent de s'appliquer. Ces préférences sont déclarées à l'API de confidentialité et exportées avec les données de l'utilisateur.

**Déclaration d'accessibilité.** L'onglet **Accessibilité** des réglages du thème la remplit au format français (RGAA) : état de conformité, entité, taux de conformité, auditeur et date de l'audit, non-conformités, dérogations, contenus non soumis, contact (par défaut, le courriel du support). Tant qu'aucun état n'est choisi, rien n'est publié. Une fois l'état choisi :

- la déclaration est publiée à l'adresse `/theme/epure/accessibility.php`, lisible sans compte. Elle liste aussi les mesures d'accessibilité prises par le thème (contrastes, clavier, structure, agrandissement, préférences d'affichage, tests automatiques), et les voies de recours auprès du Défenseur des droits ; une adresse électronique saisie comme contact devient un lien ;
- la mention « Accessibilité : partiellement conforme » (selon l'état) apparaît en bas de chaque page, et un lien dans le pied de page.

Un thème ne rend pas une plateforme conforme à lui seul : les contenus des cours comptent aussi, et un audit manuel reste nécessaire avant de déclarer la conformité.

## Développement

- **Styles** : `scss/epure.scss` importe, dans l'ordre de compilation, les fichiers de `scss/epure/`, un par sujet (en-tête, page de cours, activités, mode sombre…).
- **IOMAD** : tout ce qu'Épure utilise d'IOMAD passe par `theme_epure\iomad` (détection, API, contexte d'entreprise, noms des tables, adresses des pages). IOMAD 5.1 a renommé ses tables et ses classes ; cette classe connaît les deux.
- **Ordre du cours** : `theme_epure\course_structure` donne les activités d'un cours dans l'ordre de la page du cours, celles d'une sous-section à la place de la sous-section (Moodle les range à la fin du cours) ; la prochaine activité, la position et les activités précédente et suivante le suivent.
- **Cache** : la progression, la prochaine activité et la prochaine échéance de chaque cours d'un apprenant, et ses prochaines échéances, sont gardées 5 minutes dans le cache `learnerprogress`, et oubliées aussitôt quand une activité ou un cours est terminé, un devoir ou une tentative de test remis, ou que les activités ou l'achèvement du cours changent.
- **Compatibilité** : l'intégration continue teste Moodle 4.5 à 5.3 et IOMAD 4.5, 5.1 et 5.2 ; les tests Behat couvrent les formats de cours thématique, hebdomadaire (avec une sous-section), activité unique et informel, avec axe-core, et tournent aussi sur IOMAD (un apprenant d'une entreprise voit sa couleur et son logo, un utilisateur sans entreprise la couleur du site, « Mes cours » d'IOMAD est la page par rôle d'Épure). Les scénarios IOMAD portent l'étiquette `@theme_epure_iomad`.
- **Performances** (Moodle 4.5, apprenant, face à Boost) : feuille de style +9 %, JavaScript inchangé, HTML d'environ +10 à 15 %, aucun décalage de mise en page ; même temps serveur, sauf sur le tableau de bord, où la vue d'ensemble de l'apprenant calcule la progression de chaque cours (+130 ms avant le cache de 0.26.0 ; une fois en cache, la vue d'ensemble prend environ 5 ms au lieu de 60 à 80 ms). Détails dans le wiki (Développement).

Les variables de la palette sont exposées en propriétés CSS (`--epure-brand`, `--epure-brand-text`, `--epure-on-brand`, etc.) pour les SCSS personnalisés.

Tests :

```
vendor/bin/phpunit --testsuite theme_epure_testsuite
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @theme_epure
```

Les tests Behat passent l'audit axe-core de Moodle (« the page should meet accessibility standards ») sur le tableau de bord, le panneau des préférences et la déclaration d'accessibilité.

## Licence

GNU GPL v3 ou ultérieure. Les polices fournies sont sous SIL Open Font License 1.1 (voir `fonts/` et `thirdpartylibs.xml`).
