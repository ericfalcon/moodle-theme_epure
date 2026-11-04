Pré-audit d'Épure 0.56.2 (31 octobre 2026). Il s'agit d'un **contrôle automatisé et de vérifications au clavier**, pas d'un audit RGAA complet : il prépare l'audit manuel, qui reste nécessaire avant de déclarer la conformité. Il remplace le pré-audit de la 0.21.0 (5 octobre 2026), qui ne valait plus pour la version courante.

## Ce qui est contrôlé, et où

Le pré-audit fait partie des tests du thème : chaque modification le refait, à l'identique, sur toutes les versions de l'intégration continue.

| | Moodle | IOMAD |
|---|---|---|
| Versions | 4.5, 5.0, 5.1, 5.2, 5.3 | 4.5, 5.1, 5.2 |
| PHP | 8.1 (4.5), 8.3, 8.4 (5.3) | 8.3 |
| Bases de données | PostgreSQL, et MariaDB pour 4.5 et 5.3 | PostgreSQL |
| Pages | 40 | les 40 pages de Moodle et 24 pages d'IOMAD |
| Contrôles par version | 160 | 416 |

Chaque page est contrôlée dans **quatre affichages** : clair et sombre sur ordinateur (1300 px), clair sur téléphone (390 et 320 px). Chaque contrôle :

- passe l'audit **axe-core** de Moodle, règles WCAG 2.0, 2.1 et 2.2, niveaux A et AA ;
- sur téléphone, vérifie que la page **ne défile pas de côté** (WCAG 1.4.10). Seuls le carnet de notes et l'aperçu de l'achèvement d'IOMAD, des tableaux à deux dimensions, peuvent défiler de côté.

**Pages de Moodle (40).**

- **Visiteur** : connexion, déclaration d'accessibilité.
- **Apprenant** :
  - accueil : tableau de bord, mes cours, catalogue, page d'inscription, recherche ;
  - le cours et ses activités : page de cours, page, devoir, forum, test, livre, leçon, glossaire ;
  - le suivi : calendrier, notes (vue d'ensemble et rapport du cours), badges ;
  - le compte : profil, préférences, messagerie ;
  - les pages d'Épure : déclaration d'accessibilité, Ma synthèse.
- **Formateur** : mes cours, page de cours, participants, carnet de notes, évaluation d'un devoir, paramètres du cours et d'une activité.
- **Administrateur** :
  - tableau de bord et recherche de l'administration ;
  - réglages : Apparence, réglages du thème, réglages HTML ;
  - gestion : utilisateurs, gestion des cours ;
  - pages d'Épure : déclaration pas à pas, modèle d'attestation.

**Pages d'IOMAD (24).**

- **Apprenant d'une entreprise** : tableau de bord, mes cours, page de cours et activité, profil, préférences, Ma synthèse.
- **Gestionnaire d'entreprise** : tableau de bord IOMAD, utilisateurs, création d'un utilisateur, départements, inscriptions, licences, et les rapports (utilisateurs, connexions, aperçu de l'achèvement).
- **Administrateur** : tableau de bord IOMAD, entreprises, gestionnaires, fiche de l'entreprise, rapports des entreprises, des licences et des e-mails.

**Clavier**, contrôlé sur Moodle 4.5, Moodle 5.3 et IOMAD 4.5 :

- le lien d'évitement est en premier et mène au contenu ;
- Alt + A ouvre le panneau Aa et y place le focus ; il ne s'active pas pendant la saisie dans un champ ;
- Échap ferme le panneau et rend le focus au bouton ;
- Ctrl+K ouvre la recherche rapide, Échap la ferme ;
- le focus est visible sur les 60 premiers éléments de la page ;
- les préférences changées très vite sont gardées au rechargement.

Tous ces contrôles clavier sont conformes.

## Résultats

Sur la version 0.56.2, **les 11 configurations de l'intégration continue passent tous les contrôles**, avec les mêmes pages partout. En local, le même pré-audit a été passé, page par page, sur Moodle 4.5, 5.0, 5.1 et 5.3 et sur IOMAD 4.5 et 5.1.

Pendant le pré-audit, chaque défaut trouvé a été corrigé dans le thème, puis contrôlé de nouveau partout.

## Défauts trouvés et corrigés

| Défaut | Critère | Où | Correction |
|---|---|---|---|
| Texte sur la couleur de marque à 4,3:1 pour certaines couleurs, et sous 4,5:1 au survol pour beaucoup | 1.4.3 | Boutons, en-tête, badges (toutes versions) | Couleur de remplissage ajustée au plus juste ; tests qui balaient les couleurs (0.56.1) |
| Page en cours dans l'index du cours en blanc sur la couleur de marque | 1.4.3 | Index du cours | Texte de la couleur lisible sur la marque |
| Activités masquées ou verrouillées pâles au survol | 1.4.3 | Index du cours | Gris plus foncé |
| Retards, notes réussies et échouées, dates des activités, jours du calendrier, « Tout replier », lien « Connexion » illisibles en sombre | 1.4.3 | Mode sombre | Couleurs du mode sombre du thème |
| Champs de la page de connexion blancs en sombre | 1.4.3 | Moodle 5.2 et 5.3 | Fond sombre des champs |
| Système de design de Moodle 5.3 (onglets, interrupteur du mode édition) en couleurs claires en sombre | 1.4.3 | Moodle 5.3 | Variables `--mds-…` reliées aux couleurs sombres du thème |
| Aperçu de l'achèvement d'IOMAD blanc en sombre | 1.4.3 | IOMAD | Fond sombre du tableau |
| 155 champs sans nom | 1.3.1, 4.1.2 | Réglage des émoticônes | Chaque champ nommé par l'en-tête de sa colonne |
| Rôle `combobox` refusé (attribut `list` invalide) | 4.1.2 | Champs à suggestions (toutes versions) | Attribut retiré |
| Élément d'arbre d'une sous-section sans groupe parent | 1.3.1, 4.1.2 | Index du cours, Moodle 5.0 | Corrigé comme dans Moodle 5.1 |
| `aria-hidden="1"`, `aria-controls` vers des éléments absents, liste des départements sans nom | 4.1.2 | IOMAD | Script de réparation du thème |
| Page plus large que le téléphone | 1.4.10 | Rangées, listes de choix, champs, boutons, groupes de champs, barre d'actions de Moodle 5.3, en-tête à 320 px, mots longs, jours du week-end, Ma synthèse | Tout reste dans la largeur de l'écran ; les grands tableaux défilent dans leur cadre |
| Exemples des réglages qui défilent dans un cadre inaccessible au clavier | 2.1.1 | Aide des réglages | Les exemples passent à la ligne |
| Cibles de moins de 24 px | 2.5.8 | Cases à cocher de l'administration, noms des catégories, boutons d'action des tableaux (IOMAD), « Télécharger dans des dossiers », réinitialisation des tableaux | 24 px, ou 20 px espacés |
| Lien distingué seulement par sa couleur | 1.4.1 | Encadrés d'information | Lien souligné |
| Bouton « ? » qui couvre un lien du pied de page | 2.4.11 | À partir de 1200 px | Espace sous le pied de page (0.56.1) |
| Alt + A qui empêche d'écrire « å » sur Mac | 2.1.4 | Panneau Aa | Raccourci ignoré pendant la saisie |
| Préférences changées vite : un ancien choix pouvait être enregistré en dernier | — | Panneau Aa | Enregistrements faits l'un après l'autre |

## Limites connues

- **Barres fixes.** Quand une barre collée en bas de l'écran (enregistrement d'un formulaire long, « Continuer ») passe sur un champ, axe signale ce champ comme masqué. Pour l'utilisateur, la page défile et le champ reçu au clavier est toujours dégagé (script `focus_clear`).
- **Défauts d'IOMAD hors du thème.** La liste des gestionnaires d'une entreprise sans étiquette, la page des rapports des entreprises sans titre et la liste d'autocomplétion vide sans option viennent du code d'IOMAD ou de Moodle, et apparaissent selon les données de la plateforme. Ils sont signalés pour correction en amont.
- **Pages en erreur.** Les pages qui renvoient une erreur, faute de droits par exemple, ne sont pas contrôlées.

## Ce que le pré-audit ne couvre pas

Un outil automatique ne contrôle qu'une partie des critères. Restent à vérifier par un auditeur, sur un échantillon de pages représentatif de votre plateforme :

- la pertinence des alternatives des images et des intitulés de liens et de boutons ;
- la hiérarchie des titres et la structure des contenus de cours ;
- la restitution par les lecteurs d'écran (NVDA, JAWS, VoiceOver) des composants interactifs : menus, tiroirs, éditeur de texte, tests, carnet de notes ;
- les contenus des cours (documents, vidéos, activités tierces), qui ne dépendent pas du thème ;
- les plugins tiers installés sur la plateforme ;
- les critères RGAA propres aux médias, aux formulaires complexes et aux délais (sessions, tests chronométrés).

Les résultats de l'audit manuel se reportent dans la déclaration d'accessibilité (onglet Accessibilité des réglages du thème).

## Reproduire le pré-audit

Les scénarios sont dans le thème : `tests/behat/preaudit.feature` pour Moodle, `tests/behat/preaudit_iomad.feature` pour IOMAD. L'étape « the following pages should meet accessibility standards in the four displays » passe chaque page dans les quatre affichages et liste tous les défauts avant d'échouer.

```
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @theme_epure_preaudit
```
