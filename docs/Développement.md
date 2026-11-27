## Organisation du code

- **Styles** : `scss/epure.scss` importe, dans l'ordre de compilation, les fichiers de `scss/epure/`, un par sujet (en-tête, page de cours, activités, mode sombre…). La palette, les formes et les états sont exposés en propriétés CSS `--epure-*`, à utiliser dans un SCSS personnalisé ou un plugin : leur référence est dans [docs/Variables-CSS.md](Variables-CSS.md).
- **IOMAD** : tout ce qu'Épure utilise d'IOMAD passe par `theme_epure\iomad` (détection, API, contexte d'entreprise, noms des tables, adresses des pages). IOMAD 5.1 a renommé ses tables et ses classes ; cette classe connaît les deux.
- **Ordre du cours** : `theme_epure\course_structure` donne les activités d'un cours dans l'ordre de la page du cours, celles d'une sous-section à la place de la sous-section (Moodle les range à la fin du cours) ; la prochaine activité, la position et les activités précédente et suivante le suivent.
- **Cache** : la progression, la prochaine activité et la prochaine échéance de chaque cours d'un apprenant, et les événements de sa semaine, sont gardées 5 minutes dans le cache `learnerprogress`, et oubliées aussitôt quand une activité ou un cours est terminé, un devoir ou une tentative de test remis, ou que les activités ou l'achèvement du cours changent.
- **Compatibilité** : l'intégration continue teste Moodle 4.5 à 5.3 et IOMAD 4.5, 5.0, 5.1 et 5.2 ; les tests Behat couvrent les formats de cours thématique, hebdomadaire (avec une sous-section), activité unique et informel, avec axe-core, et tournent aussi sur IOMAD (un apprenant d'une entreprise voit sa couleur et son logo, un utilisateur sans entreprise la couleur du site, « Mes cours » d'IOMAD est la page par rôle d'Épure). Les scénarios IOMAD portent l'étiquette `@theme_epure_iomad`.
- **Performances** (Moodle 4.5, apprenant, face à Boost) : feuille de style +9 %, JavaScript inchangé, HTML d'environ +10 à 15 %, aucun décalage de mise en page ; même temps serveur, sauf sur le tableau de bord, où la vue d'ensemble de l'apprenant calcule la progression de chaque cours (+130 ms avant le cache de 0.26.0 ; une fois en cache, la vue d'ensemble prend environ 5 ms au lieu de 60 à 80 ms). Détails dans la documentation ([Développement](D%C3%A9veloppement.md)).

Les variables de la palette sont exposées en propriétés CSS (`--epure-brand`, `--epure-brand-text`, `--epure-on-brand`, etc.) pour les SCSS personnalisés.

Tests :

```
vendor/bin/phpunit --testsuite theme_epure_testsuite
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @theme_epure
```

Les tests Behat passent l'audit axe-core de Moodle (« the page should meet accessibility standards ») sur le tableau de bord, les préférences d'affichage, la déclaration d'accessibilité et son formulaire pas à pas, la page de cours, les pages d'activité, le catalogue, la recherche rapide, les formulaires longs, la fenêtre « Ajouter un bloc », Ma synthèse et le réglage des émoticônes. Le pré-audit de la version courante (Moodle 4.5, Moodle 5.3 et IOMAD, par rôle, en clair, en sombre et sur téléphone, contrôles au clavier) est dans la documentation ([Pré-audit d'accessibilité](Pr%C3%A9-audit-d%E2%80%99accessibilit%C3%A9.md)).

## Compatibilité testée

L'intégration continue teste Moodle 4.5 à 5.3 (PostgreSQL, et MariaDB pour 4.5 et 5.3) et IOMAD 4.5, 5.0, 5.1 et 5.2. Elle teste aussi la version de développement de Moodle (6.0, branche `main`), sans bloquer une fusion, et tourne chaque samedi même sans modification du thème, après les publications hebdomadaires de Moodle : ce que la 6.0 change se voit la semaine même, pour que la version qui la prend en charge soit prête à sa sortie. Les tests Behat couvrent les formats de cours thématique, hebdomadaire (avec une sous-section), activité unique et informel, avec l'audit axe-core.

Les formats de cours d'autres plugins les plus installés sont testés sur les versions de Moodle que prend en charge leur branche : Grid et Collapsed Topics (4.5), Collapsed Topics, Flexible Sections, Tiles et Designer (5.2), Flexible Sections et Multitopic (5.3), Section tabs, Onetopic et Buttons (4.5, 5.2, 5.3). La page d'un cours de chaque format, côté apprenant, passe l'audit axe-core dans tous les affichages (`tests/behat/course_formats_plugins.feature`) ; le scénario est sauté là où le format n'est pas installé.

Pour contribuer : le [guide de contribution](../CONTRIBUTING.md) ; les défauts de Moodle que répare Épure, et leurs tickets : [Réparations de Moodle](R%C3%A9parations-de-Moodle.md).

Behat tourne aussi sur IOMAD 4.5, 5.0, 5.1 et 5.2. Deux contextes Behat d'IOMAD, qui empêchent Behat de démarrer, sont retirés du clone d'IOMAD de l'intégration continue : celui d'`auth_iomadsaml2` (signature incompatible sur IOMAD 4.5) et celui de `tool_iomadpolicy` (il redéfinit une étape de `tool_policy`). Un scénario vérifie qu'un apprenant d'une entreprise voit la couleur et le logo de son entreprise, qu'un utilisateur sans entreprise garde la couleur du site, et que la page « Mes cours » d'IOMAD est la page par rôle d'Épure, avec l'audit axe-core. Les scénarios IOMAD (étiquette `@theme_epure_iomad`) tournent sur les tâches IOMAD, les autres sur les tâches Moodle.

## Performances

Mesures du 8 octobre 2026 sur Moodle 4.5, en apprenant, comparées au thème Boost (moyenne de 3 chargements, caches chauds, serveur de développement PHP : les durées sont indicatives, les écarts comptent).

| Page | Temps serveur Boost → Épure | HTML | Éléments DOM | Requêtes | LCP Boost → Épure |
|---|---|---|---|---|---|
| Tableau de bord | 212 → 344 ms | +14 % | +11 % | 29 → 32 | 1093 → 423 ms |
| Page de cours | 163 → 168 ms | +11 % | +12 % | 27 → 30 | 855 → 233 ms |
| Discussion de forum | 169 → 171 ms | +9 % | +16 % | 19 → 26 | 245 → 224 ms |
| Test (accueil) | 200 → 217 ms | +10 % | +16 % | 19 → 26 | 285 → 295 ms |

- Feuille de style : 173 Ko contre 158 Ko pour Boost (+9 %). JavaScript : identique (les modules d'Épure sont chargés à la demande).
- Aucun décalage de mise en page (CLS de 0 à 0,001).
- Le surcoût du tableau de bord venait de la vue d'ensemble de l'apprenant (progression de chaque cours, prochaine activité, échéances) : environ 80 ms pour quelques cours, en proportion du nombre de cours suivis. Depuis 0.26.0, ces calculs sont gardés en cache (voir Organisation du code) : mesurée en local sur Moodle 4.5 avec 3 cours, la vue d'ensemble passe d'environ 60 à 80 ms à environ 5 ms une fois en cache ; elle ne coûte plus que quelques millisecondes. Le tableau ci-dessus date d'avant ce cache. La vue d'ensemble se désactive dans l'onglet Cours.
- Les requêtes en plus sont les polices du thème et ses images, mises en cache par le navigateur.
