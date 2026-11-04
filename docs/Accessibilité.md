Voir aussi le [rapport de pré-audit](Pr%C3%A9-audit-d%E2%80%99accessibilit%C3%A9.md).

Le thème vise les WCAG 2.2 niveau AA, le RGAA 4.1.2 et l'EN 301 549 : contrastes calculés (le texte sur la couleur de marque à 4,5:1 au moins, quelle qu'elle soit, au survol compris), focus clavier toujours visible, lien d'évitement, cibles d'au moins 24 × 24 px, liens soulignés dans le texte, respect du réglage « réduire les animations ».

**Préférences d'affichage.** Le bouton **Aa** de l'en-tête (raccourci Alt + A ; il est ignoré pendant la saisie dans un champ ou l'éditeur de texte, où cette combinaison peut écrire un caractère selon le système et la disposition du clavier : « å » avec Option + A sur Mac, « æ » ou « á » avec AltGr + A sur certaines dispositions) ouvre un panneau où chaque utilisateur connecté choisit :

- la taille du texte : petite, normale, grande ou très grande (90, 100, 115 ou 130 %) ;
- une police de lecture : celle du site, Atkinson Hyperlegible ou OpenDyslexic ;
- un texte plus espacé (valeurs du critère WCAG 1.4.12), des lignes courtes (70 caractères au plus, WCAG 1.4.8), un contraste renforcé, des liens soulignés, des animations réduites.

Le changement est immédiat, puis enregistré dans les préférences de son profil Moodle, une requête à la fois, pour que le dernier choix soit celui gardé même quand plusieurs sont changés très vite : il vaut sur toutes les pages et tous ses appareils, dès le premier affichage. Ces préférences sont déclarées à l'API de confidentialité et exportées avec les données de l'utilisateur.

**Affichage par défaut.** Les visiteurs non connectés, les invités et les utilisateurs qui n'ont pas choisi leur affichage ont celui du site. Il se règle dans l'onglet **Accessibilité** des réglages du thème, partie Préférences d'affichage : taille du texte, police de lecture, texte plus espacé, lignes courtes, contraste renforcé, liens soulignés, animations réduites. Une préférence choisie par l'utilisateur l'emporte sur celle du site.

**Bouton « Aa ».** Il se désactive dans le même onglet : les utilisateurs ne peuvent plus changer leur affichage, et les préférences déjà choisies continuent de s'appliquer.

**Déclaration d'accessibilité.** L'onglet **Accessibilité** des réglages du thème la remplit au format français (RGAA) : état de conformité, entité, taux de conformité, auditeur et date de l'audit, non-conformités, dérogations, contenus non soumis, contact (par défaut, le courriel du support). Tant qu'aucun état n'est choisi, rien n'est publié. Une fois l'état choisi :

- la déclaration est publiée à l'adresse `/theme/epure/accessibility.php`, lisible sans compte. Elle liste aussi les mesures d'accessibilité prises par le thème (contrastes, clavier, structure, agrandissement, préférences d'affichage, tests automatiques), et les voies de recours auprès du Défenseur des droits ; une adresse électronique saisie comme contact devient un lien ;
- la mention « Accessibilité : partiellement conforme » (selon l'état) apparaît en bas de chaque page, et un lien dans le pied de page.

Un thème ne rend pas une plateforme conforme à lui seul : les contenus des cours comptent aussi, et un audit manuel reste nécessaire avant de déclarer la conformité.

## Accessibilité des contenus

Quand un formateur enregistre un formulaire, Épure relit le contenu de ses éditeurs et repère les erreurs courantes : images sans texte alternatif, tableaux sans en-têtes, liens du type « cliquez ici », niveaux de titres sautés. S'il en trouve, une fenêtre les liste champ par champ, avec trois choix : « Corriger », « Ouvrir le vérificateur d'accessibilité » (celui de l'éditeur TinyMCE, intégré à Moodle) et « Enregistrer quand même ». Rien n'est jamais bloqué, et aucun compte ni aucune inscription ne sont nécessaires.
