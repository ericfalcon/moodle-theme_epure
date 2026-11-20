## Épure fonctionne-t-il sans IOMAD ?

Oui. Sans IOMAD, les fonctions propres aux entreprises n'apparaissent pas ; tout le reste fonctionne de la même façon.

## Faut-il modifier `config.php` ?

Non. Le vocabulaire par entreprise IOMAD utilise le gestionnaire de chaînes de Moodle, activé par le thème à chaque page. Si `config.php` définit déjà un autre gestionnaire de chaînes, il est conservé et la page Épure : vocabulaire l'indique.

## Le thème appelle-t-il des services externes ?

Non. Les polices sont servies par votre propre site ; aucun appel à Google Fonts ni à un autre service.

## Comment revenir à l'affichage de Moodle pour une fonction ?

Chaque ajout se désactive dans les réglages du thème : Mes cours par rôle, bannière des cours, progression des sections, aperçu de l'apprenant, pages d'activité et catalogue des cours (onglet Cours), recherche rapide (onglet Navigation), barre de navigation mobile (onglet Mobile), e-mails aux couleurs de la marque (onglet Identité), bouton « Aa » (onglet Accessibilité), pied de page (onglet Pied de page). Avec IOMAD, plusieurs d'entre eux se règlent aussi par entreprise.

## Un utilisateur a choisi « Clair », mais l'entreprise est en mode sombre

C'est voulu : le choix de l'utilisateur (bouton Aa) l'emporte sur celui de l'entreprise et du site.

## Pourquoi certains textes de l'éditeur restent-ils clairs en mode sombre ?

L'éditeur de texte (TinyMCE) et certains contenus intégrés (par exemple depuis moodle.org) gardent leur propre apparence.

## Épure rend-il ma plateforme conforme au RGAA ou aux WCAG ?

Il y contribue, sans suffire : les contenus des cours comptent aussi, et un audit manuel reste nécessaire avant de déclarer la conformité. Le RGAA (France) reprend les WCAG 2.1 niveau AA, comme la norme européenne EN 301 549 et les lois de la plupart des pays (Suisse, Royaume-Uni, Canada, États-Unis…) : ce qu'Épure fait pour l'un vaut pour les autres. Voir le [rapport de pré-audit](Pr%C3%A9-audit-d%E2%80%99accessibilit%C3%A9.md).

## Les e-mails ne sont pas aux couleurs de la marque

Vérifiez que l'utilisateur reçoit les e-mails au format HTML (préférences du profil) et que le réglage « E-mails aux couleurs de la marque » est coché. Les logiciels de messagerie qui bloquent les images n'affichent le logo qu'après autorisation.

## Le PDF de « Ma synthèse » n'est pas balisé pour les lecteurs d'écran

Le PDF balisé (PDF/UA) demande PHP 8.2 ou plus : la bibliothèque tc-lib-pdf, fournie avec le thème, ne fonctionne pas avec PHP 8.1. Avec PHP 8.1 (possible avec Moodle 4.5), le PDF est produit par la bibliothèque de Moodle, non balisé, et la page « Ma synthèse » reste la version accessible. La page le dit.

## Faut-il demander le consentement pour un cookie d'Épure ?

Non. Le seul cookie du thème, `theme_epure_a11y`, garde les préférences d'affichage « Aa » d'un visiteur non connecté, dans son navigateur (un an). Il n'est posé que quand le visiteur choisit une préférence : c'est le cookie d'un service qu'il a demandé, exempté de consentement (selon la CNIL). Citez-le dans la mention des cookies du site.

## Les points d'accessibilité confirmés suivent-ils le cours sauvegardé ?

Oui, depuis la 1.44.0. Les points confirmés par les enseignants dans le guide « Accessibilité du cours » sont sauvegardés avec le cours, et reviennent quand il est restauré, dupliqué ou importé, chacun avec son activité dans le nouveau cours. Le nom de l'enseignant qui les a confirmés n'est gardé que si les utilisateurs sont sauvegardés ; sinon, le guide indique « Utilisateur inconnu ». Les scores du cours sont recalculés après la restauration.

## Épure corrige-t-il les défauts d'accessibilité de Moodle ?

En partie : ceux qu'un thème peut réparer dans la page (titre et en-têtes du tableau du calendrier, touche Espace sur les liens faits boutons, « (obligatoire) » dans le libellé des champs obligatoires, noms des navigations, piège au clavier des questions à choix unique…). Ce qu'il ne peut pas réparer est listé dans le [rapport de pré-audit](Pr%C3%A9-audit-d%E2%80%99accessibilit%C3%A9.md), à reprendre dans votre déclaration d'accessibilité, et signalé à Moodle.
