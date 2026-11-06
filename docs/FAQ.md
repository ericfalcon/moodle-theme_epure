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
