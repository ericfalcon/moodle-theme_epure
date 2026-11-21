# Tableau de bord de gestion

Un tableau de bord pour piloter la plateforme : son activité, ses cours les plus suivis, leur achèvement, et l'accessibilité de tous les cours. Il se trouve dans Administration du site › Rapports › Tableau de bord de gestion, pour ceux qui voient les rapports du site. Un gestionnaire du site qui ne configure pas la plateforme y arrive par le chiffre clé « Cours achevés » de son tableau de bord. Aucun réglage, aucune table propre pour l'activité : tout est lu dans les tables de Moodle à chaque affichage.

## Activité des 30 derniers jours

- **Utilisateurs connectés**, **nouveaux utilisateurs**, **nouvelles inscriptions** et **cours achevés** sur les 30 derniers jours, chacun comparé aux 30 jours précédents, en toutes lettres (« 40 % de plus que la période précédente (30) ») avec une flèche. Les connexions viennent du journal standard ; s'il est désactivé, du dernier accès des utilisateurs, sans la période précédente, et la page le dit.
- **Cours les plus suivis** : inscrits, venus ces 30 derniers jours, achevés.
- **Achèvement des cours** : où en sont les apprenants dans les cours qui suivent leur achèvement (terminé, en cours, pas commencé), en toutes lettres avec une barre qui le répète.

## Accessibilité des cours

Là où le guide « Accessibilité du cours » est activé, la partie « Accessibilité des cours » donne le score moyen des cours, combien ont un problème bloquant, leurs problèmes par gravité, les cours aux scores les plus bas (chacun lié à son guide), les catégories aux moyennes les plus basses, et le score semaine après semaine sur les 12 dernières semaines, écrit et répété par une barre, avec les points gagnés ou perdus. Une liste la restreint à une catégorie et ses sous-catégories. La liste complète se télécharge (CSV, Excel, ODS, PDF…), chaque cours avec sa catégorie, son score, ses problèmes par gravité et la date de sa lecture : pour le dossier d'une démarche qualité ou d'un audit annuel.

Les scores comptent tout ce que lit le guide, y compris, depuis la 1.49, le contenu des paquets SCORM (Storyline, Rise, pages HTML) : un cours avec un module Storyline aux images sans texte alternatif voit son score baisser, même si le reste du cours est correct.

Les scores sont calculés en arrière-plan par le cron, jamais pendant l'affichage d'une page :

- un cours modifié (une activité, une section, un fichier, une correction depuis son guide) est relu par une tâche ad hoc (« Score d’accessibilité d’un cours modifié, pour le tableau de bord de gestion ») à la fin du créneau de cinq minutes suivant : plusieurs modifications à la suite ne sont lues qu'une fois ;
- chaque nuit, la tâche planifiée « Scores d’accessibilité des cours, pour le tableau de bord de gestion » lit les cours pas encore lus cette semaine, comme leur guide (depuis son cache quand le cours n'a pas changé), 10 minutes au plus par nuit, les autres la nuit suivante.

**État du calcul.** Pour les administrateurs du site seulement (pas sur le tableau de bord d'une entreprise IOMAD), la partie « État du calcul » dit combien de cours ont un score à jour cette semaine, combien de calculs attendent le cron, et quand la tâche de nuit a tourné pour la dernière fois. Une alerte s'affiche quand le cron n'a pas tourné depuis plus d'un jour, quand la tâche de nuit est désactivée ou en échec, ou quand des calculs échouent, avec un lien vers les tâches planifiées.

Un score par cours et par semaine est gardé un an (table `theme_epure_a11yscore`, sans donnée sur les utilisateurs) ; les scores d'un cours supprimé partent avec lui. Le guide désactivé partout, rien n'est lu et la partie n'est pas affichée. Le détail du guide et du score est dans [Accessibilité](Accessibilit%C3%A9.md#accessibilit%C3%A9-des-cours).

## Avec IOMAD

Chaque entreprise a son tableau de bord de gestion : les utilisateurs de l'entreprise dans ses cours (connexions, nouveaux utilisateurs, inscriptions, cours achevés), ses cours les plus suivis par eux et leur achèvement, et l'accessibilité de ses cours quand son guide est activé. Ses gestionnaires y arrivent par un lien sous les chiffres clés du tableau de bord IOMAD (« Tableau de bord de gestion de l’entreprise ») et par l'onglet « Rapports » du tableau de bord IOMAD. Ceux qui voient toutes les entreprises choisissent l'entreprise, ou toute la plateforme, dans une liste en haut de la page ; le gestionnaire d'une entreprise ne voit que la sienne.
