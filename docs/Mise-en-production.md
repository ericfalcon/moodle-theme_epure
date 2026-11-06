# Mettre Épure en production

Ce guide s'adresse à l'administrateur qui installe Épure sur une plateforme en service, ou qui le met à jour. Pour le détail des réglages, voir [Réglages du thème](R%C3%A9glages-du-th%C3%A8me.md). Pour l'installation pas à pas, voir [Installation](Installation.md).

## Avant de commencer

**Versions prises en charge**, testées à chaque modification par l'intégration continue :

| | Versions |
|---|---|
| Moodle | 4.5 LTS, 5.0, 5.1, 5.2, 5.3 |
| IOMAD | 4.5, 5.1, 5.2 |
| PHP | 8.1 à 8.4, selon la version de Moodle |
| Base de données | PostgreSQL, MariaDB |

**Dépendances** : le thème Boost (fourni avec Moodle) et l'outil de personnalisation des langues `tool_customlang` (fourni avec Moodle), qui sert au vocabulaire.

**Téléchargement** : prenez le zip d'une version publiée dans les [releases](https://github.com/ericfalcon/moodle-theme_epure/releases), jamais le code d'une branche en cours. Le zip contient le dossier `epure` à placer dans `theme/`.

## Checklist de mise en production

1. **Essayez d'abord sur une copie** de la plateforme (préproduction), avec une copie récente de la base et des données.
2. **Sauvegardez** la base de données, le dossier `moodledata` et le dossier `theme/epure` actuel, s'il y en a un.
3. **Passez le site en mode maintenance** (Administration du site › Serveur › Mode maintenance), ou en ligne de commande : `php admin/cli/maintenance.php --enable`.
4. **Installez le dossier** `theme/epure`. Pour une mise à jour, remplacez l'ancien dossier entier, sans fusionner les fichiers.
5. **Lancez la mise à jour de la base** : Administration du site › Notifications, ou `php admin/cli/upgrade.php`. Moodle propose alors les nouveaux réglages, avec leurs valeurs par défaut.
6. **Purgez les caches** : Administration du site › Développement › Purger tous les caches, ou `php admin/cli/purge_caches.php`.
7. **Réglez le thème** dans l'ordre ci-dessous, puis choisissez Épure comme thème du site (Présentation › Thèmes).
8. **Appliquez de nouveau le vocabulaire**, si vous l'utilisez (Présentation › Thèmes › Épure : vocabulaire). Avec IOMAD, vérifiez que `config.php` ne définit pas d'autre gestionnaire de chaînes (`$CFG->customstringmanager`).
9. **Vérifiez les parcours critiques** avec un compte de chaque rôle : connexion, tableau de bord, un cours, une activité, un devoir, un test, en clair et en sombre, sur ordinateur et sur téléphone.
10. **Quittez le mode maintenance.**

Lisez aussi les **notes de mise à jour** de chaque version entre la vôtre et la nouvelle, dans le [README](../README.md#upgrade-notes) ou le [CHANGES](../CHANGES.md). Certaines versions ajoutent un réglage désactivé par défaut, une table ou une page ouverte sans connexion.

## Ordre recommandé des réglages

Les onglets des réglages du thème suivent l'ordre conseillé : chacun s'appuie sur les précédents.

1. **Identité** : couleur de marque (toute la palette en est déduite), logos, e-mails aux couleurs de la marque.
2. **Navigation** : menus, recherche rapide, menu utilisateur.
3. **Cours** : cartes, catalogue, bannière et progression des cours, « Ma synthèse », icônes des activités.
4. **Accessibilité** : préférences d'affichage par défaut, guide « Accessibilité du cours » (désactivé par défaut, pour les organismes tenus au RGAA), déclaration d'accessibilité.
5. **Apparence** : typographie, arrondis, mode sombre.
6. **Mobile** : barre de navigation, application web installable.
7. **Page de connexion**, puis **Pied de page**.
8. **Réglages avancés** : SCSS personnalisé, en dernier (voir [Variables CSS](Variables-CSS.md)).

Avec IOMAD, réglez d'abord le site, puis l'apparence de chaque entreprise (fiche de l'entreprise › Apparence) : ce qu'une entreprise ne précise pas reprend la valeur du site.

**Reproduire la configuration sur une autre plateforme** : l'outil **Préréglages d'administration du site** de Moodle (*Site admin presets*, dans l'administration du site) exporte et importe tous les réglages, ceux d'Épure compris. Les fichiers (logos, polices, images de connexion) ne suivent pas : déposez-les de nouveau.

## Revenir en arrière

Moodle ne sait pas revenir à une version antérieure d'un plugin : remettre l'ancien dossier `theme/epure` ne suffit pas, car la base a déjà été mise à jour.

- **Problème d'affichage sans gravité** : choisissez temporairement Boost comme thème du site (Présentation › Thèmes). Les réglages d'Épure sont conservés pour plus tard.
- **Retour complet à la version précédente** : remettez en place la sauvegarde de la base de données **et** l'ancien dossier `theme/epure`, puis purgez les caches.
- **Désinstallation** (Administration du site › Plugins › Vue d'ensemble des plugins) : elle retire les réglages et les tables d'Épure (chaînes du vocabulaire, documents « Ma synthèse », confirmations du guide d'accessibilité), rend à Moodle ses chaînes d'origine à la place du vocabulaire, et retire les visites guidées d'Épure ainsi que la feuille de style de l'application Moodle qu'il avait posée.

## Plugins tiers

| Plugin | Version | Moodle | Statut |
|---|---|---|---|
| Course certificate (`mod_coursecertificate`) | branches du fork du mainteneur | 4.5 à 5.2, IOMAD 4.5 à 5.2 | Testé par l'intégration continue à chaque modification, avec le modèle de certificat d'Épure. |
| Tiles (`format_tiles`) | 5.0.0.23 | 5.0 | Couleurs corrigées en mode sombre (1.0.1), vérifié à la main. |
| Edwiser Course Formats (`format_remuiformat`) | 4.1.22 | 4.5 | Couleurs corrigées en mode sombre (1.0.1), vérifié à la main, dispositions carte et liste. |
| Trail (`format_trail`) | 5.0.0.0 | 4.5 | Couleurs corrigées en mode sombre (1.0.1), vérifié à la main. |
| Components for Learning (`tiny_c4l`) | 3.3.2 | 5.0 | Couleurs corrigées en mode sombre (1.0.1), vérifié à la main. |
| Grid (`format_grid`) | — | 4.5 | Vérifié à la main en clair et en sombre : aucune correction nécessaire. |
| Collapsed Topics (`format_topcoll`) | — | 4.5 | Vérifié à la main en clair et en sombre : aucune correction nécessaire. |
| OneTopic (`format_onetopic`) | — | 4.5 | Vérifié à la main en clair et en sombre : aucune correction nécessaire. |

Les formats de Moodle (Thématique, Hebdomadaire, Activité unique, Social), avec les sous-sections, sont testés par l'intégration continue. Un plugin qui impose ses propres couleurs (fonds blancs, textes gris) peut devenir illisible en mode sombre : vérifiez-le en sombre avant de l'ouvrir aux utilisateurs, et signalez-le pour qu'Épure le prenne en charge.

## Performance

Épure ajoute des calculs sur quelques pages : la vue d'ensemble de l'apprenant sur le tableau de bord, la progression des cours, le guide d'accessibilité. Les résultats sont gardés dans le **cache universel de Moodle (MUC)**, et les mesures sont dans [Développement](D%C3%A9veloppement.md).

**Caches d'Épure** (cache d'application, Administration du site › Plugins › Mise en cache › Configuration) :

| Cache | Contenu | Durée |
|---|---|---|
| `learnerprogress` | Progression, prochaine activité et échéances de chaque cours d'un apprenant, événements de sa semaine. | 5 minutes, oublié dès qu'une activité est terminée, un devoir remis ou le cours modifié. |
| `courseaccessibility` | Ce que le guide « Accessibilité du cours » a lu d'un cours. | 1 heure, oublié dès que le cours, une activité, une section, un chapitre de livre, une page de leçon ou une question change. |
| `pdftagged` | Si un PDF a une structure (balisé), par contenu de fichier. | Sans limite : un contenu ne change pas. |
| `documentcheck` | Ce que le guide a trouvé dans un document Word, PowerPoint, Excel, LibreOffice ou PDF, par contenu de fichier. | Sans limite : un contenu ne change pas. |
| `companystrings` | Avec IOMAD, les chaînes réécrites avec le vocabulaire de chaque entreprise. | Jusqu'à la purge des caches, ou au changement du vocabulaire de l'entreprise. |

**Recommandations :**

- **Un seul serveur** : le stockage par fichiers, configuration par défaut de Moodle, convient.
- **Plusieurs serveurs web** : placez le cache d'application sur **Redis** (ou Memcached), partagé par tous les serveurs. Les caches d'Épure suivent sans réglage particulier.
- **PHP** : activez **OPcache**, avec assez de mémoire pour tout le code de Moodle (`opcache.memory_consumption` de 256 Mo au moins).
- **Feuille de style** : Moodle compile le SCSS du thème une fois, après chaque purge des caches ou modification de réglage. La première page qui suit est donc plus lente. Purgez les caches en dehors des heures d'affluence.
- **Guide d'accessibilité** : il lit chaque cours à la première ouverture du guide ou du mode édition, puis garde le résultat. Les PDF et les documents bureautiques sont lus une seule fois par contenu, et ceux de plus de 30 Mo ne sont pas lus.
- **Vue d'ensemble de l'apprenant** : elle se désactive dans l'onglet Cours si le tableau de bord doit rester le plus léger possible.
