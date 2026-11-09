Voir aussi le [rapport de pré-audit](Pr%C3%A9-audit-d%E2%80%99accessibilit%C3%A9.md).

Le thème vise les WCAG 2.2 niveau AA, le RGAA 4.1.2 et l'EN 301 549 : contrastes calculés (le texte sur la couleur de marque à 4,5:1 au moins, quelle qu'elle soit, au survol compris), focus clavier toujours visible, lien d'évitement, cibles d'au moins 24 × 24 px, liens soulignés dans le texte, respect du réglage « réduire les animations ».

**Préférences d'affichage.** Le bouton **Aa** de l'en-tête (raccourci Alt + A ; il est ignoré pendant la saisie dans un champ ou l'éditeur de texte, où cette combinaison peut écrire un caractère selon le système et la disposition du clavier : « å » avec Option + A sur Mac, « æ » ou « á » avec AltGr + A sur certaines dispositions) ouvre un panneau où chaque utilisateur connecté choisit :

- la taille du texte : petite, normale, grande ou très grande (90, 100, 115 ou 130 %) ;
- une police de lecture : celle du site, Atkinson Hyperlegible ou OpenDyslexic ;
- un texte plus espacé (valeurs du critère WCAG 1.4.12), des lignes courtes (70 caractères au plus, WCAG 1.4.8), un contraste renforcé, des liens soulignés, des animations réduites.

Le changement est immédiat, puis enregistré dans les préférences de son profil Moodle, une requête à la fois, pour que le dernier choix soit celui gardé même quand plusieurs sont changés très vite : il vaut sur toutes les pages et tous ses appareils, dès le premier affichage. Ces préférences sont déclarées à l'API de confidentialité et exportées avec les données de l'utilisateur.

**Affichage par défaut.** Les visiteurs non connectés, les invités et les utilisateurs qui n'ont pas choisi leur affichage ont celui du site. Il se règle dans l'onglet **Accessibilité** des réglages du thème, partie Préférences d'affichage : taille du texte, police de lecture, texte plus espacé, lignes courtes, contraste renforcé, liens soulignés, animations réduites. Une préférence choisie par l'utilisateur l'emporte sur celle du site.

**Bouton « Aa ».** Il se désactive dans le même onglet : les utilisateurs ne peuvent plus changer leur affichage, et les préférences déjà choisies continuent de s'appliquer.

**Déclaration d'accessibilité.** L'onglet **Accessibilité** des réglages du thème la remplit sur le modèle du pays par défaut du site (France : RGAA, loi de 2005, Défenseur des droits ; reste de l'Union européenne et EEE : norme EN 301 549 et directive (UE) 2016/2102 ; ailleurs : WCAG 2.1 niveau AA) : état de conformité, entité, taux de conformité, auditeur et date de l'audit, non-conformités, dérogations, contenus non soumis, contact (par défaut, le courriel du support). Tant qu'aucun état n'est choisi, rien n'est publié. Une fois l'état choisi :

- la déclaration est publiée à l'adresse `/theme/epure/accessibility.php`, lisible sans compte. Elle liste aussi les mesures d'accessibilité prises par le thème (contrastes, clavier, structure, agrandissement, préférences d'affichage, tests automatiques), et les voies de recours de son modèle (en France, le Défenseur des droits ; dans le reste de l'Union européenne, l'organisme de contrôle du pays ; ailleurs, aucune fixée par le thème) ; une adresse électronique saisie comme contact devient un lien ;
- la mention « Accessibilité : partiellement conforme » (selon l'état) apparaît en bas de chaque page, et un lien dans le pied de page.

Le pays du site n'indique pas à lui seul le droit qui s'applique à l'organisme : vérifiez que le modèle lui convient. **Avec IOMAD**, la déclaration vaut pour toute la plateforme, toutes entreprises confondues (ses réglages sont ceux du site) : une entreprise responsable de son propre service publie sa propre déclaration.

**Pas à pas.** Les mêmes réglages se remplissent dans un formulaire guidé (Administration du site › Présentation › Thèmes › « Déclaration d'accessibilité, pas à pas », aussi lié depuis l'onglet Accessibilité et, pour les administrateurs, depuis la déclaration elle-même) : qui publie la déclaration, l'audit, l'état de conformité, les contenus non accessibles, le contact. Chaque étape dit ce que demande le modèle du pays, et le modèle lui-même avec un lien vers les paramètres de localisation ; l'étape de l'état rappelle le taux saisi et la règle du modèle (RGAA : totalement conforme à 100 %, partiellement à partir de 50 %, non conforme en dessous ou sans audit ; ailleurs : totalement si tous les critères sont respectés, partiellement si la plupart le sont, non conforme sinon ou sans audit), mais le thème ne calcule jamais l'état : l'audit le donne. La déclaration a un bouton pour l'imprimer, ou l'enregistrer en PDF.

Un thème ne rend pas une plateforme conforme à lui seul : les contenus des cours comptent aussi, et un audit manuel reste nécessaire avant de déclarer la conformité.

## Accessibilité des contenus

Pendant la saisie, une ligne sous chaque éditeur indique ce qui rend le contenu plus difficile à utiliser : images sans texte alternatif, images servant de lien sans texte alternatif, liens sans texte ou du type « cliquez ici », tableaux sans en-têtes, niveaux de titres sautés, texte long sans aucun titre ; son bouton « Montrer » sélectionne chaque élément concerné dans l'éditeur. À l'enregistrement du formulaire, une fenêtre les liste de nouveau champ par champ, avec trois choix : « Corriger », « Ouvrir le vérificateur d'accessibilité » (celui de l'éditeur TinyMCE, intégré à Moodle) et « Enregistrer quand même ». Rien n'est jamais bloqué, et aucun compte ni aucune inscription ne sont nécessaires.

Le vérificateur accompagne le guide « Accessibilité du cours » (onglet Accessibilité, désactivé par défaut ; une entreprise IOMAD peut faire un autre choix) : là où le guide est désactivé, il l'est aussi. Tous les organismes ne sont pas tenus aux WCAG 2.1 AA (RGAA en France, EN 301 549 en Europe).

## Pour les développeurs de plugins d'activité

Le guide « Accessibilité du cours » lit, pour chaque activité, sa description et ses fichiers. Il lit aussi le contenu propre des modules de Moodle (pages, devoirs, tests, livres, leçons, ateliers, glossaires, feedbacks, bases de données). Il connaît aussi les modules d'autres plugins les plus installés (Questionnaire, Unilabel, Content Pages, Interactive Video, Booking, Scheduler, Diary, Face-to-face, Game, HotPot, Realtime Quiz, Video Time, Learning map, Lightbox Gallery, Mindmap…) : il lit leurs tables telles qu'elles sont publiées, sans exécuter leur code, et la liste complète est dans `theme_epure\content_sources::KNOWN`. Le contenu d'un autre module n'est lu que si le plugin le donne ; sinon, le guide demande à l'enseignant de le vérifier, et le réglage « Accessibilité du cours » liste ces modules. Un plugin qui donne son contenu est lu ainsi, même s'il est connu.

Pour que le guide lise votre module, ajoutez à son `lib.php` la fonction suivante. Elle renvoie les textes écrits par les enseignants (jamais les contributions des apprenants), chacun avec son format, le nom de son emplacement et l'adresse où le corriger :

```php
/**
 * Textes de l'activité lus par le guide « Accessibilité du cours » du thème Épure.
 *
 * @param cm_info $cm Activité.
 * @return array[] Chaque texte : text, format (FORMAT_HTML, FORMAT_MARKDOWN…), name, url.
 */
function mod_monmodule_epure_accessibility_texts(cm_info $cm): array {
    global $DB;
    $pages = $DB->get_records('monmodule_pages', ['monmoduleid' => $cm->instance], 'sortorder');
    $texts = [];
    foreach ($pages as $page) {
        $texts[] = [
            'text' => $page->content,
            'format' => $page->contentformat,
            'name' => format_string($page->title),
            'url' => (new moodle_url('/mod/monmodule/editpage.php', ['id' => $cm->id, 'page' => $page->id]))->out(false),
        ];
    }
    return $texts;
}
```

Le texte brut n'est pas lu ; le Markdown l'est, une fois converti en HTML. Le guide garde ce qu'il a lu d'un cours pendant une heure, et l'oublie dès qu'une activité crée, modifie ou supprime quelque chose : tout événement d'un module (`mod_*`) qui écrit (`crud` c, u ou d) avec le cours. Votre module n'a rien à faire s'il déclenche ses événements ; sinon, appelez `\theme_epure\course_accessibility::forget($courseid)` quand la classe existe.

