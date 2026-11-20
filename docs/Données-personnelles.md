# Données personnelles

Voir aussi [Accessibilité](Accessibilit%C3%A9.md).

La page « Épure et les données personnelles » (`/theme/epure/dataprotection.php`, ouverte sans connexion, à remettre au délégué à la protection des données) dit ce que le thème garde sur les utilisateurs, et qui répond à chaque obligation du RGPD sur la plateforme. Elle est liée depuis Administration du site › Utilisateurs › Confidentialité et politiques, et depuis la carte « Présentation » de l'accueil de l'administration. Comme la page « Ce que couvre Épure », elle suit le pays du site : RGPD avec les références de la CNIL en France, RGPD dans l'Union européenne, principes généraux ailleurs ; les deux autres sont à un lien. C'est une aide à la conformité, jamais un avis juridique : l'organisme reste responsable du traitement.

## Ce que garde le thème

| Donnée | Où et combien de temps | Ce que chacun peut faire |
|---|---|---|
| Préférences d'affichage « Aa », mode lecture, raccourcis clavier, vue de « Mes cours », nouveautés lues | Préférences du profil Moodle, tant que le compte existe | Modifiées dans « Aa » ; exportées et supprimées par l'outil de confidentialité de Moodle |
| Préférences d'affichage d'un visiteur non connecté | Cookie `theme_epure_a11y` du navigateur, un an ; rien sur le serveur | Modifiées dans « Aa », ou supprimées avec les cookies |
| Raccourcis clavier d'un visiteur non connecté | Stockage local du navigateur ; rien sur le serveur | Modifiés dans l'aide « ? » |
| Points confirmés dans le guide « Accessibilité du cours » (qui, quand) | Table `theme_epure_a11ycheck`, tant que le cours existe ; sauvegardés avec leur auteur seulement si les utilisateurs le sont | Exportés et supprimés ; supprimés avec le compte |
| Documents « Ma synthèse » et leur code de vérification | Table `theme_epure_summary`, pendant la durée choisie par l'apprenant ; ensuite le code et les dates seulement | Révoqués ou supprimés par l'apprenant ; exportés et supprimés ; supprimés avec le compte |
| Progression d'un apprenant, adresse d'un visiteur de la page de vérification | Caches de Moodle : cinq minutes ; dix minutes pour l'adresse, hachée | Rien : ils disparaissent seuls |
| Scores d'accessibilité, chiffres du tableau de bord de gestion | Par cours et par semaine, un an ; jamais par personne | Rien sur les utilisateurs |

Aucun appel à un service extérieur : polices, icônes et scripts sont servis par la plateforme, et la lecture à voix haute n'utilise que les voix installées sur l'appareil (le texte ne quitte pas le navigateur). Aucun traceur, aucune mesure d'audience. Le seul cookie garde un choix demandé par le visiteur et ne contient aucun identifiant : il ne demande pas de consentement (lignes directrices de la CNIL).

## Qui répond à quoi

- **Le thème** : minimisation, aucun tiers, cookie de confort, accès, effacement et portabilité (API de confidentialité de Moodle), durées limitées, maîtrise des documents par l'apprenant, sécurité de la vérification (codes aléatoires, tentatives limitées), sauvegardes sans les utilisateurs.
- **Moodle** : demandes des personnes (« Requêtes de données »), politiques et consentement, durée des journaux, registre des données et suppression des données expirées.
- **L'organisme** : registre des activités de traitement, base légale et information, hébergement et sous-traitants, délégué à la protection des données et violations, analyse d'impact.
- **Les plugins ajoutés** : chacun déclare ses données dans le registre de confidentialité des plugins de Moodle.

## Extrait pour le registre

La page se termine par un extrait à recopier dans le registre des activités de traitement (article 30) : traitement, finalités, personnes concernées, données, destinataires, transferts (aucun du fait du thème), durées et mesures de sécurité. L'organisme le complète de ses propres informations : responsable du traitement, base légale, hébergeur.
