## Prérequis

- Moodle 4.5 LTS, 5.0, 5.1, 5.2 ou 5.3, PHP 8.1 à 8.4.
- Base de données : PostgreSQL, MariaDB ou MySQL, dans les versions exigées par votre version de Moodle (par exemple pour Moodle 5.3 : PostgreSQL 17, MariaDB 11.4, MySQL 8.4). Le thème n'utilise que l'API base de données de Moodle.
- Tests automatiques : Moodle 4.5 à 5.3 sur PostgreSQL, Moodle 4.5 et 5.3 sur MariaDB 11.4. MySQL n'est pas testé séparément.
- IOMAD est facultatif : il est détecté automatiquement.

## Installer

1. Téléchargez le fichier `theme_epure-x.y.z.zip` de la dernière version, dans la liste « Assets » de la page [Releases](https://github.com/ericfalcon/moodle-theme_epure/releases). N'utilisez pas les archives « Source code » que GitHub ajoute à chaque version : elles contiennent tout le dépôt, et Moodle ne les reconnaît pas comme un thème (« Impossible de détecter le type de plugin »).
2. Deux possibilités :
   - **depuis Moodle** : Administration du site › Plugins › Installer des plugins, déposez le ZIP, puis suivez les étapes ;
   - **sur le serveur** : décompressez le ZIP dans le dossier `theme` de Moodle (`public/theme` à partir de Moodle 5.1), puis ouvrez la page Administration du site › Notifications.
3. Lancez la mise à jour de la base de données. Pendant les étapes, Épure affiche « Moodle travaille… » pour éviter un second clic.
4. Choisissez Épure dans Administration du site › Présentation › Thèmes.
5. Réglez au minimum la **couleur de marque** et le **logo** (Administration du site › Présentation › Thèmes › Épure).

## Mettre à jour

Remplacez le dossier `epure` par la nouvelle version (ou déposez le nouveau ZIP depuis Moodle), puis lancez la mise à jour de la base de données. Les réglages sont conservés.

Après une mise à jour de Moodle ou d'un paquet de langue, appliquez à nouveau le [vocabulaire](Vocabulaire.md) pour couvrir les nouvelles chaînes.

## Ancien plugin « Outils Épure »

Si vous aviez installé `local_epure` (versions 0.1 à 0.3) : mettez d'abord le thème à jour, qui reprend ses réglages de vocabulaire, puis désinstallez ce plugin (Administration du site › Plugins › Vue d'ensemble des plugins).

## Désinstaller

Si l'[application web installable](Navigation-et-recherche.md) a été activée, désactivez-la d'abord et laissez aux utilisateurs le temps de revenir sur le site : une fois le thème désinstallé, les navigateurs gardent son dernier service worker, qui n'affiche la page « Vous êtes hors ligne » que sans réseau.

Choisissez un autre thème, puis désinstallez Épure depuis la vue d'ensemble des plugins. Les chaînes de vocabulaire écrites par le thème sont retirées ; celles que vous aviez personnalisées vous-même sont conservées.
