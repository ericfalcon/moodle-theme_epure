## Organisation du code

- **Styles** : `scss/epure.scss` importe, dans l'ordre de compilation, les fichiers de `scss/epure/` (un par sujet : en-tête, page de cours, activités, mode sombre…). Le mode sombre vient après les règles qu'il remplace.
- **IOMAD** : tout ce qu'Épure utilise d'IOMAD passe par la classe `theme_epure\iomad` : détection, API (entreprise de l'utilisateur, capacités, catégories), contexte d'entreprise, noms des tables et adresses des pages. IOMAD 5.1 a renommé ses tables (`company` devient `local_iomad_companies`…) et ses classes ; cette classe connaît les deux versions, si bien qu'un changement d'IOMAD se traite à un seul endroit.
- **Ordre du cours** : la classe `theme_epure\course_structure` donne les activités d'un cours dans l'ordre de la page du cours. Moodle range les sections des sous-sections (`mod_subsection`, sections « déléguées » à une activité) après toutes les sections du cours, et `course_modinfo::get_cms()` liste leurs activités à la fin ; la classe les remet à la place de la sous-section. La prochaine activité, la position dans le cours et les activités précédente et suivante passent par elle.
- **Cache** : la progression, la prochaine activité et la prochaine échéance de chaque cours d'un apprenant, et les événements de sa semaine, sont gardées 5 minutes dans le cache MUC `learnerprogress` (`db/caches.php`). Elles sont oubliées aussitôt quand l'apprenant termine une activité ou un cours, remet un devoir ou une tentative de test, et, pour tous les apprenants du cours, quand ses activités, ses sections ou son achèvement changent (`db/events.php`).
- **Application web** : la classe `theme_epure\web_app` fournit le manifeste, l'icône, la page hors ligne et le service worker, servis par les scripts de `webapp/`.
- **Erreurs** : les erreurs inattendues (thème d'une entreprise, échéances, attestations…) sont signalées en mode débogage développeur, sans interrompre la page.

## Compatibilité testée

L'intégration continue teste Moodle 4.5 à 5.3 (PostgreSQL, et MariaDB pour 4.5 et 5.3) et IOMAD 4.5, 5.1 et 5.2. Les tests Behat couvrent les formats de cours thématique, hebdomadaire (avec une sous-section), activité unique et informel, avec l'audit axe-core.

Behat tourne aussi sur IOMAD 4.5, 5.1 et 5.2. Deux contextes Behat d'IOMAD, qui empêchent Behat de démarrer, sont retirés du clone d'IOMAD de l'intégration continue : celui d'`auth_iomadsaml2` (signature incompatible sur IOMAD 4.5) et celui de `tool_iomadpolicy` (il redéfinit une étape de `tool_policy`). Un scénario vérifie qu'un apprenant d'une entreprise voit la couleur et le logo de son entreprise, qu'un utilisateur sans entreprise garde la couleur du site, et que la page « Mes cours » d'IOMAD est la page par rôle d'Épure, avec l'audit axe-core. Les scénarios IOMAD (étiquette `@theme_epure_iomad`) tournent sur les tâches IOMAD, les autres sur les tâches Moodle.

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

## Tests

Les variables de la palette sont exposées en propriétés CSS (`--epure-brand`, `--epure-brand-text`, `--epure-on-brand`, etc.) pour les SCSS personnalisés.

Tests :

```
vendor/bin/phpunit --testsuite theme_epure_testsuite
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @theme_epure
```

Les tests Behat passent l'audit axe-core de Moodle (« the page should meet accessibility standards ») sur le tableau de bord, le panneau des préférences, la déclaration d'accessibilité, la page de cours, les formats de cours et, sur IOMAD, les pages d'un apprenant d'une entreprise (page d'arrivée après la connexion, page de cours, « Mes cours »).
