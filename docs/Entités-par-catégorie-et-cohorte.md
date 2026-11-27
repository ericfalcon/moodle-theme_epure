Sur un Moodle sans IOMAD, une université ou un groupe a souvent plusieurs entités (composantes, écoles, filiales), chacune avec ses couleurs. Moodle le fait avec des **thèmes de catégorie** et des **thèmes de cohorte**, mais il faut alors une copie du thème par entité (`boost_iae`, `boost_lettres`…), à maintenir une par une. Depuis la 1.53.0, Épure le fait **sans copie** : un seul Épure pour tout le site, et une **apparence par entité**, rangée dans ses réglages. C'est ce qu'Épure fait déjà pour les entreprises IOMAD.

## Ce qu'est une entité

Une entité est une **catégorie de cours** qui a son apparence :

- couleur de marque et couleur d'accent : Épure en calcule toute la palette, avec les mêmes contrastes que pour le site (4,5:1 pour les textes), en clair comme en sombre ;
- logo, et sa version pour l'en-tête à la couleur de marque ;
- couleur de l'en-tête, police (celles du thème ou celle du site), mode sombre, couleurs des icônes d'activités, arrondis ;
- pages et navigation : bannière des cours, aperçu de l'apprenant, barre de navigation mobile, catalogue, recherche rapide, pages d'activité, progression des sections, Mes cours par rôle, guide « Accessibilité du cours », fil d'Ariane, blocs et tableau de bord sur téléphone, vue simplifiée ;
- pied de page : texte, mentions légales, données personnelles, contact, liens ;
- page de connexion : sa propre adresse, sa disposition, son accroche et son texte (voir plus bas) ;
- CSS de l'entité : ajouté à ses pages après les styles du thème, comme le CSS d'une entreprise IOMAD ; seuls les administrateurs du site peuvent le modifier, car un CSS peut masquer ou changer n'importe quoi dans la page ;
- priorité : départage un utilisateur des cohortes de plusieurs entités (voir plus bas) ;
- vocabulaire : ses mots pour les cours, les étudiants et les enseignants (« parcours », « apprenti », « formateur »…), en français et en anglais, comme une entreprise IOMAD (voir [Vocabulaire](Vocabulaire.md)).

Chaque champ laissé « Comme le site » garde le réglage du site. Le SCSS personnalisé (onglet Avancé) reste celui du site : il est compilé une fois pour tous ; le CSS de l'entité le complète. Sous les couleurs, un **aperçu en direct** montre l'en-tête, une carte, un bouton, un lien et une barre de progression, en clair et en sombre, pendant la saisie, avant l'enregistrement.

**Les e-mails** envoyés aux membres des cohortes d'une entité prennent sa couleur, son logo, son nom et son pied de page.

## Qui la voit

Épure suit l'ordre dans lequel Moodle choisit un thème (`$CFG->themeorder` : cours, catégorie, session, utilisateur, cohorte, site) :

1. **Dans un cours ou une catégorie** : l'entité est la catégorie du cours, ou la plus proche de ses catégories parentes qui a une apparence. Une sous-catégorie qui a la sienne la garde. C'est le comportement d'un thème de catégorie, pour tous les utilisateurs du cours.
2. **Hors des cours** (tableau de bord, Mes cours, profil, messagerie, calendrier), et dans un cours qui n'est d'aucune entité : l'entité des **cohortes** de l'utilisateur. C'est le comportement d'un thème de cohorte. Mènent à une entité :
   - les cohortes choisies dans son apparence (« Cohortes de l'entité ») ;
   - les cohortes dont le thème est Épure, dans le contexte de sa catégorie ou d'une de ses sous-catégories.
3. Un utilisateur des cohortes de **plusieurs entités** voit celle qu'il a choisie dans **« Mon entité »** (ses préférences, lien affiché seulement s'il a le choix) ; sinon celle qui a la plus haute **priorité**, si aucune autre n'a la même ; sinon le site, comme Moodle avec deux thèmes de cohorte. Un double cursus ou un salarié de deux filiales choisit ainsi son entité ; dans les cours d'une entité, l'apparence reste celle du cours.
4. Un utilisateur d'aucune entité voit le site, ou l'entité dont il a ouvert la page de connexion.

**Le vocabulaire suit l'utilisateur, pas la page** : les mots de l'entité s'appliquent aux membres de ses cohortes, partout dans Moodle, y compris dans les cours d'autres catégories. Un visiteur de ses cours qui n'est pas dans ses cohortes, et un utilisateur des cohortes de deux entités, gardent les mots du site. Changer les mots selon la page mélangerait ceux d'une même page : Moodle charge une partie des textes avant de connaître la page, et le navigateur garde les textes des scripts pour toutes les pages. Les mots des langues que le site n'a pas installées sont gardés, prêts quand elles le seront.

Moodle n'a pas d'inscription à une catégorie : les membres d'une entité sont ceux de ses cohortes, souvent celles qui les inscrivent à ses cours (méthode « Synchronisation des cohortes »), alimentées par l'annuaire ou le système d'information de scolarité.

## Créer une entité

1. Ouvrez la catégorie (Administration du site › Cours › Gérer les cours et catégories, ou la liste des cours de la catégorie).
2. Dans ses paramètres, choisissez **« Apparence de l'entité »**. Il faut pouvoir gérer la catégorie (`moodle/category:manage`) : un gestionnaire de catégorie règle l'apparence de la sienne.
3. Choisissez ses couleurs, son logo, ses cohortes, ses pages et son pied de page, puis enregistrez. Ses cours la prennent tout de suite.

« Supprimer l'apparence de l'entité », en bas du formulaire, rend la catégorie au site ; la catégorie et ses cours ne sont pas touchés. Une catégorie supprimée emporte son apparence.

Le geste habituel des administrateurs marche aussi : **mettre « Épure » comme thème de la catégorie** (champ « Imposer le thème »). La catégorie apparaît alors dans la page « Entités », à compléter. Sur un site dont le thème est un autre que Épure (Boost par exemple), c'est ce qui fait que ses cours s'affichent avec Épure ; il faut alors « Autoriser les thèmes de catégorie » dans Moodle. De même, une cohorte dont le thème est Épure montre Épure à ses membres hors des cours si « Autoriser les thèmes de cohorte » est activé. Si une catégorie ou une cohorte a Épure comme thème alors que le réglage de Moodle qui le permet est désactivé, Épure le signale au lieu de l'ignorer en silence.

## La page de connexion d'une entité

Chaque entité a sa page de connexion : `/login/index.php?entity=<identifiant de la catégorie>`, donnée dans son apparence. Mise sur le portail de la composante, elle montre avant la connexion l'apparence de l'entité, son logo, son nom, sa disposition, son accroche et son texte. L'entité est gardée pour la session, comme l'adresse d'une entreprise IOMAD : après la connexion, un utilisateur qui n'est d'aucune entité la garde ; un membre des cohortes d'une entité retrouve la sienne.

## La page « Entités »

Administration du site › Présentation › Thèmes › Entités :

- **les entités du site** : leur catégorie, leur couleur, les cohortes qui y mènent, un lien vers leur apparence ;
- **les catégories sous Épure sans apparence** : à compléter ;
- **les catégories sous un autre thème** : l'aide à la migration.

## Passer des thèmes de catégorie à Épure

Les catégories qui gardent un autre thème (souvent une copie de Boost pour leurs couleurs) continuent de fonctionner : Moodle applique leur thème dans leurs cours, et Épure n'y est pas. Pour en passer une à Épure :

1. Dans la page « Entités », section « Catégories sous un autre thème », choisissez **« Créer l'entité … à partir de son thème »** : sa couleur de marque est reprise de son thème, quand il en a une (réglage `brandcolor`, comme Boost et la plupart de ses copies).
2. Ajoutez son logo et ses cohortes, enregistrez.
3. Videz le champ « Imposer le thème » de la catégorie : ses cours prennent Épure et l'apparence de l'entité.
4. Quand plus aucune catégorie ne l'utilise, la copie du thème peut être désinstallée.

Les membres de ses cohortes voient l'apparence de l'entité hors des cours dès l'étape 2.

## Avec IOMAD

Avec IOMAD, les **entreprises** sont les entités : leur apparence se règle dans leur fiche (voir [IOMAD](IOMAD.md)). Les entités par catégorie ne sont pas proposées sur un site IOMAD.
