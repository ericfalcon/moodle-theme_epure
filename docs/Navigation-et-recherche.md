## Recherche rapide (Ctrl+K)

Un bouton **Rechercher** dans l'en-tête, ou **Ctrl+K** (**⌘K** sur Mac) depuis n'importe quelle page, ouvre une fenêtre de recherche. Elle trouve, sans tenir compte des majuscules ni des accents :

- les **pages des menus** : navigation principale, menu utilisateur, onglets du cours ou de la page (donc le tableau de bord IOMAD, l'administration, le calendrier, les notes… selon les droits de chacun) ;
- **mes cours** et les **activités** de mes cours ;
- les autres cours du **catalogue**, avec un lien vers la recherche complète ;
- pour les administrateurs, les **pages de l'administration** dont le nom ou un réglage correspond, avec un lien vers la recherche de l'administration.

Les flèches ↑ ↓ choisissent un résultat, Entrée l'ouvre (Ctrl+Entrée dans un nouvel onglet), Échap ferme. La fenêtre est une boîte de dialogue accessible (liste de choix annoncée aux lecteurs d'écran, nombre de résultats). La recherche se désactive dans l'onglet Navigation du thème (« Recherche rapide »).

La recherche rapide remplace le bouton de recherche de Moodle dans l'en-tête. Quand la recherche globale de Moodle est activée, les résultats se terminent par « Rechercher … dans tout le site », qui l'ouvre.

## Fil d'Ariane et menus

Le réglage « Fil d'Ariane » (onglet Navigation) affiche le chemin de la page au-dessus de son titre (cours › section › activité), seulement sur les grands écrans, ou jamais. Le menu principal et le menu utilisateur restent ceux de Moodle : leurs éléments s'ajoutent dans Présentation › Réglages thème avancés (Éléments du menu personnalisé, Éléments du menu utilisateur), où mène l'onglet Navigation.

## Barre de navigation mobile

Sur téléphone, une barre fixée en bas de l'écran, à portée de pouce, mène au **tableau de bord** (ou à l'accueil du site si le tableau de bord est désactivé), à **mes cours**, au **catalogue**, aux **messages** (avec le nombre de conversations non lues) et au **profil** ; l'entrée de la page en cours est mise en évidence. Les boutons flottants de Moodle (aide, sommaire du cours) remontent au-dessus d'elle ; sur les pages qui ont leur propre barre d'actions en bas (notation, par exemple), elle s'efface. Elle se désactive dans l'onglet Mobile du thème (« Barre de navigation mobile ») et, avec IOMAD, pour chaque entreprise dans sa fiche.

## Téléphones

Moodle n'affiche pas de logo dans l'en-tête des téléphones. Le réglage « Logo pour téléphone » (onglet Identité) y place une version petite ou carrée du logo, à côté du bouton de menu ; avec IOMAD, le logo de l'entreprise y est affiché.

Dans l'onglet Mobile, les blocs restent dans leur tiroir, ouvert par un bouton sur le côté de l'écran, ou sont masqués sur téléphone (« Blocs sur téléphone ») ; le tableau de bord d'un apprenant peut ne montrer que la vue d'ensemble du thème, sans les blocs (« Tableau de bord sur téléphone »).

## Application web installable

Avec le réglage « Application web installable » (onglet Mobile, désactivé par défaut), la plateforme peut être ajoutée à l'écran d'accueil des téléphones et des ordinateurs depuis le navigateur, comme une application : son icône, son nom, sa couleur, sans barre d'adresse. Sans passer par les magasins d'applications ni par l'application Moodle.

- Le thème ajoute un manifeste d'application web (nom du site, nom court tiré du réglage « Nom de l'application web » ou du nom abrégé du site, couleur de marque, icônes de 192 et 512 pixels), une icône pour iOS et la couleur de la barre du navigateur. Avec IOMAD, la couleur est celle de l'entreprise de l'utilisateur.
- L'icône est celle déposée (« Icône de l'application web », un PNG carré d'au moins 512 × 512 pixels, son contenu dans les 80 % du centre) ; sans elle, le thème dessine l'initiale du site sur la couleur de marque.
- Un service worker, servi par `theme/epure/webapp/sw.php` pour tout le site, ne garde qu'une page « Vous êtes hors ligne », affichée quand une page ne peut pas se charger. Rien d'autre n'est mis en cache : les pages de Moodle viennent toujours du réseau.
- Quand l'application web est désactivée, ou pour un utilisateur qui ne voit plus Épure, le service worker se retire avec son cache. Les pages d'Épure le retirent tout de suite quand le réglage est désactivé ; avec un autre thème, le navigateur reçoit un service worker qui se retire de lui-même à sa vérification suivante (au plus une heure d'utilisation, ou la vérification quotidienne du navigateur).

Une fois le thème désinstallé, ses fichiers n'existent plus et les navigateurs gardent le dernier service worker, qui n'affiche la page hors ligne que sans réseau : désactivez l'application web et laissez les utilisateurs revenir sur le site avant de désinstaller Épure.

L'[application Moodle](Activit%C3%A9s-aux-couleurs-de-la-marque.md) peut aussi prendre les couleurs de la marque (onglet Mobile).
