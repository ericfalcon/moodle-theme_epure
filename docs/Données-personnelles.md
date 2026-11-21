# Données personnelles

Voir aussi [Accessibilité](Accessibilit%C3%A9.md).

La page « Épure et les données personnelles » (`/theme/epure/dataprotection.php`, ouverte sans connexion, à remettre au délégué à la protection des données) dit ce que le thème garde sur les utilisateurs, et qui répond à chaque obligation du RGPD sur la plateforme. Elle est liée depuis Administration du site › Présentation › Thèmes, avec les autres pages d'Épure, et depuis la carte « Présentation » de l'accueil de l'administration. Comme la page « Ce que couvre Épure », elle suit le pays du site : RGPD avec les références de la CNIL en France, RGPD dans l'Union européenne, principes généraux ailleurs ; les deux autres sont à un lien. Le pays détermine les références présentées, pas à lui seul la législation applicable : un organisme hors de l'Union européenne peut relever du RGPD (article 3), et la page le dit. C'est une aide à la conformité, jamais un avis juridique : l'organisme reste responsable du traitement.

La page décrit ce que fait le code, pas une conformité : les durées sont des règles techniques, dont l'organisme apprécie si elles sont justifiées par ses finalités.

## Ce que garde le thème

| Donnée | Où et combien de temps | Ce que chacun peut faire |
|---|---|---|
| Préférences d'affichage « Aa », mode lecture, raccourcis clavier, vue de « Mes cours », nouveautés lues | Préférences du profil Moodle, tant que le compte existe | Modifiées dans « Aa » ; exportées et supprimées par l'outil de confidentialité de Moodle |
| Préférences d'affichage d'un visiteur non connecté | Cookie `theme_epure_a11y` du navigateur, un an ; aucune préférence enregistrée sur le serveur pour un visiteur non connecté | Modifiées dans « Aa », ou supprimées avec les cookies |
| Raccourcis clavier d'un visiteur non connecté | Conservé uniquement dans le navigateur (stockage local) | Modifiés dans l'aide « ? » |
| Points confirmés dans le guide « Accessibilité du cours » (qui, quand) | Table `theme_epure_a11ycheck`, tant que le cours existe ; sauvegardés avec leur auteur seulement si les utilisateurs le sont | Exportés et supprimés ; supprimés avec le compte |
| Documents « Ma synthèse » et leur code de vérification | Table `theme_epure_summary`. Révocation : contenu effacé aussitôt ; expiration : contenu effacé par la tâche de nuit ; restent le code, les dates et une empreinte du contenu, jusqu'à la suppression par l'apprenant ou du compte. Le PDF n'est jamais stocké : il est refait à chaque téléchargement | Révoqués ou supprimés par l'apprenant ; exportés et supprimés ; supprimés avec le compte |
| Progression d'un apprenant, adresse d'un visiteur de la page de vérification | Caches de Moodle : cinq minutes ; dix minutes pour l'adresse, hachée | Rien : ils disparaissent seuls |
| Scores d'accessibilité, chiffres du tableau de bord de gestion | Par cours et par semaine, un an ; jamais par personne | Rien sur les utilisateurs |

Le thème n'appelle aucun service extérieur : polices, icônes et scripts sont servis par la plateforme. La lecture à voix haute n'utilise que les voix que le navigateur signale comme locales (`localService`) ; les voix d'un service en ligne sont exclues, et sans voix locale pour la langue, rien n'est lu. Aucun traceur, aucune mesure d'audience. Le seul cookie garde un choix demandé par le visiteur et ne contient aucun identifiant : il relève en principe de l'exemption de consentement (lignes directrices de la CNIL), ce que l'organisme confirme dans son information sur les cookies. Les journaux de Moodle et de l'hébergeur sont à part.

## Qui répond à quoi

- **Le thème** : minimisation, aucun tiers, cookie de confort, export et effacement de ses données par l'API de confidentialité de Moodle (l'organisme traite les demandes selon les droits applicables : la portabilité ne s'applique pas à tous les traitements), durées limitées, maîtrise des documents par l'apprenant, sécurité de la vérification (codes aléatoires, tentatives limitées), sauvegardes sans les utilisateurs.
- **Moodle** : demandes des personnes (« Requêtes de données »), politiques et consentement, durée des journaux, registre des données et suppression des données expirées.
- **L'organisme** : registre des activités de traitement, base légale et information, hébergement et sous-traitants, délégué à la protection des données et violations, analyse d'impact.
- **Les plugins ajoutés** : chacun déclare ses données dans le registre de confidentialité des plugins de Moodle.

## Extrait technique pour le registre

La page se termine par un extrait technique à intégrer au registre des activités de traitement (article 30) et à compléter : traitement, finalités des fonctions, personnes concernées, données, accès prévus par le thème, flux extérieurs (aucun initié par le thème), règles techniques de conservation, mesures de sécurité. L'organisme complète avec son délégué à la protection des données ce qui ne se déduit pas du thème : responsable du traitement, base légale, destinataires, hébergeur et sous-traitants, justification des durées.
