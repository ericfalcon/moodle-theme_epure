Thème moderne, sobre et accessible pour **Moodle 4.5 LTS à 5.3** et **IOMAD 4.5 à 5.2**, enfant de Boost. Peu de réglages, mais chacun change beaucoup de choses : l'administrateur choisit une identité, Épure calcule le reste. Une **prise en main** guide les premiers réglages, pour la plateforme comme pour chaque entreprise IOMAD.

**Libre et gratuit** : toutes les fonctions, vérification des documents Office et PDF comprise, sont sous licence GNU GPL v3 ou ultérieure. Pas de version payante, rien de réservé à une licence.

### Une couleur, une palette accessible

Choisissez une couleur de marque (ou cliquez sur une couleur de votre logo) et, si vous le voulez, une couleur d'accent : Épure en tire toute la palette et garde le texte à 4,5:1 au moins, en clair et en sombre, quelles que soient les couleurs. L'en-tête peut être blanc, de la couleur de marque ou en dégradé ; un en-tête peint par un SCSS personnalisé garde un texte lisible.

### L'accessibilité intégrée

- Des préférences d'affichage pour chaque utilisateur, visiteurs compris (« Aa », Alt + A) : texte jusqu'à 200 %, Atkinson Hyperlegible, OpenDyslexic ou Lexend, espacement du texte, lignes plus courtes, contraste renforcé, liens soulignés, animations réduites, images figées, règle ou masque de lecture, lecture à voix haute avec les voix de l'appareil (rien ne quitte le navigateur), affichage clair, sombre, jaune sur noir ou crème. Des profils en un clic : Dyslexie, Basse vision, Concentration, Sans clignotement, avec une fiche « Quel profil choisir ? » à imprimer. Gardées dans le profil ; le bouton indique quand l'affichage est celui de l'utilisateur.
- Des pages qui s'adaptent jusqu'à 320 px, même avec un texte à 200 % ; un focus toujours visible et jamais caché sous une barre fixe ; des cibles de 24 px.
- Des raccourcis clavier (« ? ») et des touches de navigation entre activités, sections et pages principales, désactivées par défaut.
- Une déclaration d'accessibilité remplie pas à pas, sur le modèle du pays du site (RGAA, EN 301 549 ou WCAG 2.1 AA), mentionnée sur chaque page ; avec IOMAD, chaque entreprise peut publier la sienne.
- Un vérificateur de contenu pour les formateurs, pendant la saisie : images sans texte alternatif, liens qui ne disent rien, tableaux sans en-têtes, niveaux de titres sautés, couleurs qui ne contrastent pas. Rien n'est jamais bloqué.
- Un guide « Accessibilité du cours » (désactivé par défaut) : toutes les activités de Moodle et les 47 modules d'activité d'autres plugins les plus installés sont lus ; les documents Word, PowerPoint, Excel, LibreOffice et PDF sont vérifiés ; chaque erreur avec sa gravité (bloquante, sérieuse, mineure) et un lien vers son emplacement ; un score du cours sur 100 % et ce qu'il faut corriger d'abord ; textes alternatifs, titres vides, textes qui défilent et d'autres erreurs corrigés depuis le guide ; bilan exporté en CSV, Excel, ODS ou PDF.
- L'accessibilité de tous les cours sur un tableau de bord de gestion : score moyen, cours avec une erreur bloquante, catégories les plus faibles, semaine après semaine sur 12 semaines, téléchargeable pour une démarche qualité ou un audit annuel ; le score de chaque cours dans « Mes cours » pour ses formateurs. Calculé en arrière-plan par le cron.
- Des défauts de Moodle lui-même réparés là où un thème le peut, dont plusieurs relevés par un audit RGAA d'une plateforme Moodle : le mois du calendrier avec son titre et ses en-têtes de colonnes, la touche Espace sur les liens faits boutons, « (obligatoire) » dans le libellé des champs obligatoires, un nom pour chaque navigation, le piège au clavier du test, le mode sombre du test et du carnet de notes. Ce qu'un thème ne peut pas corriger est listé pour la déclaration et signalé à Moodle.
- Testé à chaque modification : un pré-audit automatisé (axe-core, WCAG 2.2 AA) de 52 pages de Moodle et 24 pages d'IOMAD, en clair, en sombre, en jaune sur noir, en crème, sur téléphone et avec un texte à 200 %, et un test de régression visuelle.

### Pour les apprenants

- Un tableau de bord : où reprendre, ce qui est à faire cette semaine, ce qui est terminé.
- Une page « À faire » : les activités restant à faire dans tous leurs cours, par échéance, filtrées par cours et par période.
- « Mes cours » par rôle, un catalogue avec une page de présentation des cours, la progression par section qui suit chaque activité marquée comme terminée, l'activité précédente et suivante, un mode lecture.
- Les nouvelles activités marquées « Nouveau » depuis la dernière visite.
- Un aperçu des fichiers sans quitter le cours : PDF, images, vidéos, sons et textes, et le texte des documents Word, PowerPoint, Excel et LibreOffice, lisible avec un lecteur d'écran. Ouvrir un fichier compte comme le consulter.
- « Ma synthèse » : une page imprimable ou un PDF de leurs cours, progression, notes et attestations, balisé pour les lecteurs d'écran (PDF/UA, vérifié avec veraPDF ; avec PHP 8.2 et plus), avec un code que le destinataire vérifie sur la plateforme ; l'apprenant peut le révoquer.
- Une barre de navigation en bas de l'écran sur téléphone et une application web installable.

### Pour les formateurs et les administrateurs

- Vos propres mots dans tout Moodle : « parcours » ou « programme » pour les cours, « apprenant » ou « stagiaire » pour les étudiants, « formateur » ou « tuteur » pour les enseignants, avec la grammaire du français et de l'anglais. Écrits par la personnalisation de la langue de Moodle : aucun fichier modifié, annulé en un clic.
- « Mes cours » en tableau pour les formateurs : apprenants, actifs cette semaine, à relancer, achèvement, à corriger, prochaine échéance, score d'accessibilité ; exportable.
- Un tableau de bord de gestion (aussi pour chaque entreprise IOMAD) : l'activité des 30 derniers jours, les cours les plus suivis, l'achèvement, l'accessibilité des cours.
- Une recherche rapide (Ctrl+K / ⌘K) dans les menus, les cours, les activités et les pages d'administration.
- Des réglages en onglets, un accueil de l'administration plus clair, des formulaires longs avec la liste de leurs sections, les boutons d'enregistrement toujours visibles et leur état indiqué (« Modifications non enregistrées »).
- Après une mise à jour, les nouveautés d'Épure sur l'accueil de l'administration.
- Un en-tête de cours pour les formateurs : participants, apprenants actifs et travaux à corriger.
- Des e-mails aux couleurs de la marque et un modèle d'attestation pour tool_certificate et l'activité Attestation de formation.

### Avec IOMAD

Chaque entreprise a ses couleurs, son logo, sa police, son vocabulaire et sa déclaration d'accessibilité, sur sa page de connexion, son tableau de bord et ses cours, son propre tableau de bord de gestion et sa propre prise en main pour ses gestionnaires.

### Compatibilité

- Moodle 4.5 à 5.3, IOMAD 4.5 à 5.2, PHP 8.1 à 8.4, PostgreSQL et MariaDB, testés en intégration continue (PHPUnit, Behat) ; un job suit Moodle 6.0.
- API de confidentialité pour toutes les données gardées. Aucun service externe ; polices fournies (licence SIL Open Font) ; le PDF balisé fait avec tc-lib-pdf (LGPL), fourni.

Un pré-audit automatisé ne remplace pas un audit manuel. Épure est un projet indépendant, sans lien avec Moodle HQ ni IOMAD.

Documentation (anglais et français) : <https://github.com/ericfalcon/moodle-theme_epure>
