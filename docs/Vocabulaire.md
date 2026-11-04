Administration du site › Présentation › Thèmes › Épure : vocabulaire (lien aussi dans l'onglet Identité du thème).

Choisissez, pour le français et l'anglais, les mots qui désignent :

| Objet | Mots proposés en français | Mots proposés en anglais |
|---|---|---|
| Cours | cours, formation, parcours, module, autre | course, program, programme, module, class, learning path, autre |
| Étudiants | étudiant, apprenant, stagiaire, participant, collaborateur, autre | student, learner, trainee, participant, employee, autre |
| Enseignants | enseignant, formateur, tuteur, intervenant, professeur, coach, autre | teacher, trainer, tutor, instructor, facilitator, coach, autre |

Le vocabulaire s'applique à **tout Moodle** : menus, tableau de bord, listes de cours, participants, rôles, rapports, notifications, ainsi qu'aux pages des plugins et d'IOMAD.

En français, le plugin ne se contente pas de remplacer un mot : les articles, l'élision et les accords suivent le mot choisi. « Tous les cours » devient « Toutes les formations », « Ce cours est masqué » « Cette formation est masquée », « l'étudiant » « le stagiaire ». Pour un mot de votre choix, indiquez son singulier, son pluriel et son genre.

Fonctionnement :

- les chaînes sont écrites avec l'outil natif **Personnalisation de la langue** de Moodle (paquets `fr_local`, `en_local`) : aucun fichier de Moodle ni `config.php` n'est modifié ;
- les chaînes que vous avez personnalisées vous-même dans cet outil sont conservées, jamais écrasées ;
- chaque chaîne réécrite reste modifiable dans l'outil de personnalisation ;
- « Rétablir la formulation de Moodle » retire uniquement les chaînes écrites par le thème, tout comme la désinstallation du thème ;
- les mots restent en place quel que soit le thème utilisé par un cours ou un utilisateur ;
- la première application prend une vingtaine de secondes par langue (Moodle charge le paquet de langue dans l'outil), les suivantes quelques secondes.

Après chaque mise à jour de Moodle ou d'un paquet de langue, appliquez à nouveau le vocabulaire pour couvrir les nouvelles chaînes ; avec IOMAD, vérifiez aussi que `config.php` ne définit pas un autre gestionnaire de chaînes (`$CFG->customstringmanager`), qui couperait les mots des entreprises. Cette liste s'affiche sur la page du vocabulaire et dans l'onglet Identité des réglages du thème.

## Langues

Le vocabulaire s'applique en français et en anglais, dont le thème connaît la grammaire : en français, les articles, l'élision et les accords suivent le mot choisi ; en anglais, le singulier et le pluriel. Les autres langues installées gardent la formulation de Moodle.

Le même mécanisme conviendrait à l'espagnol, à l'italien ou au portugais, qui demandent surtout le genre et, pour l'italien, l'élision. L'allemand demanderait davantage : déclinaisons (« der Kurs », « des Kurses ») et mots composés (« Kursübersicht »). Chaque nouvelle langue demande un relecteur natif et un jeu de phrases de test ; voir la [feuille de route](../ROADMAP.md).

Avec IOMAD, la même page propose les mots « entreprise » et « département » pour toutes les entreprises ; chaque entreprise peut choisir les siens dans sa fiche, ainsi que ses mots pour « cours », « étudiant » et « enseignant » (voir la section IOMAD).
