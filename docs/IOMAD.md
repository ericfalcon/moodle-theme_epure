Épure détecte IOMAD automatiquement : aucun autre plugin n’est à installer.

## Avec IOMAD

Épure applique l'apparence que vous définissez pour chaque entreprise dans IOMAD (Tableau de bord IOMAD › Modifier l'entreprise › Apparence) :

| Réglage IOMAD de l'entreprise | Effet dans Épure |
|---|---|
| Couleur des titres (à défaut, couleur des liens) | Devient la couleur de marque de l'entreprise : la palette accessible est recalculée pour elle (en-tête, boutons, liens, contrastes AA). |
| Logo de l'entreprise | Remplace le logo d'Épure pour les utilisateurs de l'entreprise, dans l'en-tête des téléphones aussi. |
| CSS personnalisé | Ajouté aux pages des utilisateurs de l'entreprise. |

Ces réglages s'appliquent aux utilisateurs rattachés à l'entreprise, et à l'administrateur qui a sélectionné l'entreprise dans le tableau de bord IOMAD. Seuls les codes couleur hexadécimaux sont pris en compte. La couleur principale (fond de page) d'IOMAD n'est pas appliquée, pour préserver la lisibilité.

**Tableau de bord IOMAD** : le tableau de bord d'IOMAD garde ses actions, ses onglets et ses droits, mais Épure en change la présentation :

- l'entreprise sélectionnée en en-tête, avec son logo, un accès direct à sa catégorie de cours et à ses sous-catégories (page de gestion pour qui gère les cours, liste des cours sinon), lien repris en tête de l'onglet Cours, et le sélecteur d'entreprise à côté ;
- les chiffres clés de l'entreprise : utilisateurs (et actifs cette semaine), cours, licences utilisées sur celles attribuées, achèvements des 30 derniers jours ; chacun mène à la page d'IOMAD correspondante ;
- des onglets sobres, soulignés de la couleur de marque, qui défilent sur mobile ;
- les actions en cartes, **regroupées par intention** dans chaque onglet : Créer, Paramétrer, Gérer, Importer et exporter, Suivre ;
- la palette de l'entreprise à la place des couleurs fixes d'IOMAD, et des onglets accessibles aux lecteurs d'écran.

**Gérer les cours** (paramètres IOMAD des cours) : le tableau d'IOMAD gagne une colonne **Catégorie**, après celle du cours, avec le chemin complet de sa catégorie (par exemple « Clinique des Tilleuls / Soins infirmiers »).

**Tout se règle dans la fiche de l'entreprise** (Tableau de bord IOMAD › Créer une entreprise ou Modifier l'entreprise › Apparence). Épure range cette partie en étapes numérotées, organisées comme les onglets des réglages du thème, et y regroupe les champs d'IOMAD :

1. **Thème** : le thème de l'entreprise.
2. **Identité** : *Logos* (logo, logo compact et favicon d'IOMAD, puis le logo pour l'en-tête en couleur), puis *Couleurs* (couleur de marque, couleur de l'en-tête, couleurs des icônes d'activités), puis *Attestations*. Les logos viennent avant les couleurs, car leurs couleurs sont proposées pour la couleur de marque.
3. **Apparence** : police, mode sombre.
4. **Pages et navigation** : *Navigation* (barre de navigation mobile), *Pages* (bannière de cours, aperçu de l'apprenant).
5. **Pied de page**.
6. **Vocabulaire**.
7. **Réglages avancés** : CSS personnalisé et menu personnalisé d'IOMAD.

Dans les réglages du thème, les réglages de l'étape 4 sont rangés dans les onglets Cours (bannière, aperçu de l'apprenant) et Mobile (barre de navigation mobile).

Si l'entreprise choisit un autre thème qu'Épure, il ne reste rien d'Épure : le formulaire d'IOMAD s'affiche tel qu'IOMAD le présente (ordre, champs, remarque), et les utilisateurs de l'entreprise voient ce thème tel qu'il est sans Épure (mots d'origine du paquet de langue, e-mails de Moodle). Les réglages d'Épure de l'entreprise sont conservés et s'appliquent de nouveau si elle revient à Épure.

Une entreprise peut choisir Épure quel que soit le thème du site : même si le site utilise Iomad, les réglages d'Épure apparaissent dans le formulaire dès qu'Épure est choisi pour l'entreprise.

Le changement de thème s'applique dès la page suivante aux utilisateurs de l'entreprise déjà connectés (Moodle gardait sinon l'ancien thème jusqu'à leur prochaine connexion). Les pages prennent le thème de l'entreprise sélectionnée, y compris pour un administrateur : en choisissant une entreprise dans IOMAD, il voit les pages comme ses utilisateurs, dans son thème ; sans entreprise sélectionnée, c'est le thème du site.

Les réglages propres à Épure :

| Réglage de l'entreprise | Effet |
|---|---|
| Couleur de marque | Code couleur libre, sélecteur de couleur, ou couleurs du logo de l'entreprise (pastilles et pipette, y compris pour un logo tout juste téléversé). Vide : la couleur du titre d'IOMAD, sinon celle du site. La palette accessible est recalculée. Elle remplace aussi la couleur d'accent du site. |
| Couleur d'accent | Barres de progression et sections terminées. Vide : la couleur de marque de l'entreprise, sinon la couleur d'accent du site. |
| Couleur de l'en-tête | Comme le site, blanc, ou couleur de marque. |
| Arrondis | Comme le site, peu, moyennement ou très arrondis. Sur Moodle 4.5, les coins compilés dans Bootstrap 4 (champs, boutons de Moodle) gardent ceux du site ; ceux d'Épure et, à partir de Moodle 5.0, ceux de Bootstrap suivent l'entreprise. |
| E-mails aux couleurs de la marque | Comme le site, activé ou désactivé, selon l'entreprise du destinataire. |
| Bannière des cours | Comme le site, affichée ou masquée, pour les utilisateurs de l'entreprise. |
| Aperçu de l'apprenant sur le tableau de bord | Comme le site, affiché ou masqué. |
| Barre de navigation mobile | Comme le site, affichée ou masquée. |
| Mode sombre | Comme le site, jamais, automatique selon l'appareil, ou toujours. |
| Navigation | Comme le site, ou un autre choix pour le fil d'Ariane, la recherche rapide, les pages d'activité, les blocs et le tableau de bord sur téléphone. |
| Pages | Comme le site, ou un autre choix pour le catalogue des cours, la progression des sections et « Mes cours » par rôle. |
| Page de connexion | Disposition, accroche et texte d'accompagnement de sa page de connexion (son adresse propre ou son lien `login/index.php?id=…&code=…`), qui prend aussi ses couleurs, son logo et son nom. Champs vides : ceux du site. |
| Pied de page | Texte, mentions légales, données personnelles, contact, autres liens ; un champ vide reprend la valeur du site, affichée en grisé. |
| Logo pour l'en-tête en couleur | Facultatif : une version du logo lisible sur la couleur de marque de l'entreprise, souvent blanche sur fond transparent. |
| Police | Comme le site, l'une des 8 polices fournies, la police téléversée du site, ou une police téléversée pour l'entreprise (nom, fichiers normal et gras en woff2 ou woff ; vérifiez que sa licence autorise l'usage sur un site web). Sans fichier normal, la police du site est gardée. |

Les champs natifs d'IOMAD (logos, CSS et menu personnalisés) sont rangés dans ces étapes ; Épure se déclare thème IOMAD pour qu'IOMAD les affiche. Les couleurs d'IOMAD (titre, principale, lien), qui ne servent qu'aux thèmes IOMAD, sont masquées tant que l'entreprise utilise Épure ; une couleur du titre déjà enregistrée est reprise comme couleur de marque. Enfin, la section **Vocabulaire** règle les mots de l'entreprise, en français et en anglais : ses mots pour « cours », « étudiant » et « enseignant » (« parcours » et « stagiaire » pour l'une, « module » et « collaborateur » pour une autre), et pour « entreprise » et « département » (« client » et « équipe », « agence » et « service »). Épure se déclare thème IOMAD pour qu'IOMAD affiche ces champs. Les mots de la plateforme se choisissent sur la page Épure : vocabulaire ; une entreprise sans mots propres utilise ceux-là.

Les paquets de langue étant communs à tout le site, ces mots sont appliqués au chargement des chaînes par un gestionnaire de chaînes que le thème active à chaque page (le mécanisme `$CFG->customstringmanager` de Moodle), avec un cache par entreprise. **Aucune modification de `config.php` n'est nécessaire.** Il n'est activé que si des mots sont choisis ; si `config.php` définit déjà un autre gestionnaire de chaînes, celui-ci est conservé et la page Épure : vocabulaire l'indique.

## Chiffres clés

Sur un Moodle sans IOMAD, le tableau de bord des gestionnaires de la plateforme commence par ses chiffres clés : utilisateurs (et actifs cette semaine), cours (et cours visibles), inscriptions actives, achèvements des 30 derniers jours. Avec IOMAD, ce sont ceux de l'entreprise sélectionnée, en tête du tableau de bord IOMAD.

## Versions d'IOMAD

Épure fonctionne avec IOMAD 4.5, 5.0, 5.1 et 5.2. IOMAD 5.1 a renommé ses tables et ses classes, et déplacé le suivi des achèvements de `local_iomad_track` dans `local_iomad` : le cadre des attestations d'une entreprise qui a quitté Épure lui est rendu sur les pages d'IOMAD qui créent des attestations dans les deux versions (`local/iomad`, `local/iomad_track`, `local/report_completion`, `local/report_users`, `admin/tool/redocerts`, `mod/iomadcertificate`, blocs « Mes cours »), et dans les scripts en ligne de commande (tâches planifiées).

L'intégration continue teste IOMAD 4.5, 5.0, 5.1 et 5.2, avec les tests Behat : un apprenant d'une entreprise voit la couleur et le logo de son entreprise, un utilisateur sans entreprise garde la couleur du site, et la page « Mes cours » d'IOMAD est la page par rôle d'Épure, avec l'audit axe-core. Voir [Développement](D%C3%A9veloppement.md).
