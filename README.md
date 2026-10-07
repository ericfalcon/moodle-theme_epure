# Épure (theme_epure)

A modern, clean and accessible theme for **Moodle 4.5 LTS and 5.x**, based on Boost. Documentation in French: [README.fr.md](README.fr.md).

## Installation

1. Copy the `epure` folder into the `theme` folder of your Moodle site (`public/theme` from Moodle 5.1 onwards).
2. Log in as an administrator and run the database upgrade.
3. Select Épure in Site administration › Appearance › Themes.

## Settings

Site administration › Appearance › Themes › Épure.

### General settings

| Setting | Effect |
|---|---|
| Brand colour | The whole palette is derived from it: hover states, tinted backgrounds, link colour. Colours are adjusted automatically to meet the WCAG 2.2 AA contrast requirements, and the resulting contrast is shown below the setting. |
| Font | Bundled fonts: IBM Plex Sans, Inter, Source Sans 3, Figtree, Lato, and, for readability, Atkinson Hyperlegible, Lexend and OpenDyslexic. Or upload your own font. |
| Uploaded font | Font name and `.woff2` or `.woff` files (regular required, bold optional). Check that the font's licence allows its use on a website. |
| Corner style | Sharp, soft or round: cards, buttons and form fields. |

Below the brand colour, the theme suggests the **colours of the logo** you have saved, including accent colours that take up little space (thin lettering, a small emblem): click a swatch, or click directly on a point of the logo (eyedropper), then save. Only hex codes are accepted, since the accessible palette is calculated from them.

All fonts are served by your own site. The theme makes **no calls to Google Fonts** or to any other external service.

### My courses page

The courses you **teach** and the courses you **take** are shown in two sections ("Programmes I teach", "Programmes I take", using your vocabulary). Users with only one role see a single list.

| Card for a course you take | Card for a course you teach |
|---|---|
| Progress, next activity to do, next deadline, Start / Continue / Review button, Completed label | Banner and role badge, participants, learners active this week, assignments to grade, shortcuts to Participants, Grades, Settings |

A search box (accent-insensitive) and In progress, Future and Past filters complete the page. Starred courses come first, followed by the most recently accessed; courses you have hidden stay hidden. A teacher is identified by the capability to view all grades in the course (`moodle/grade:viewall`). With IOMAD, which replaces Moodle's block with its own ("My courses" with available, in progress and completed tabs), the same layout applies, with an extra "Programmes available" section and IOMAD's certificate download button. The "My courses by role" setting (General settings): untick it to restore the Moodle or IOMAD block.

### Header

| Setting | Effect |
|---|---|
| Header colour | White with an underline in the brand colour, or filled with the brand colour. Text and icons take whichever colour is most legible. |
| Logo | Shown in the header and on the login page. Transparent backgrounds are supported (SVG, PNG, WebP). Without a logo, the ones from Appearance › Logos are used. |
| Logo for the brand-coloured header | Optional: a version that is legible on the brand colour, often white on a transparent background. |

### Login page

| Setting | Effect |
|---|---|
| Layout | Split screen: a brand-coloured visual next to Moodle's login form (on phones, only the form is shown). Or a centred form. |
| Headline and supporting text | Shown on the visual. Without a headline, a default sentence is used. |
| Background image | Optional. A brand-coloured overlay is placed over it, with the opacity needed to keep the text legible (AA contrast) whatever the image. |

The form itself is still Moodle's own: it follows each version (4.5 to 5.3) and the configured authentication methods.

### With IOMAD

Épure applies the appearance you define for each company in IOMAD (IOMAD dashboard › Edit company › Appearance):

| Company IOMAD setting | Effect in Épure |
|---|---|
| Heading colour (or, if not set, link colour) | Becomes the company's brand colour: the accessible palette is recalculated for it (header, buttons, links, AA contrast). |
| Company logo | Replaces the Épure logo for the company's users. |
| Custom CSS | Added to the pages of the company's users. |

These settings apply to users assigned to the company, and to an administrator who has selected the company in the IOMAD dashboard. Only hex colour codes are taken into account. IOMAD's main colour (page background) is not applied, to preserve legibility.

**IOMAD dashboard**: the IOMAD dashboard keeps its actions, tabs and permissions, but Épure changes its presentation:

- the selected company in the header, with its logo, a direct link to its course category and its subcategories (the management page for those who manage courses, the course list otherwise), a link repeated at the top of the Courses tab, and the company selector alongside;
- the company's key figures: users (and those active this week), courses, licences used out of those allocated, completions over the last 30 days; each one leads to the corresponding IOMAD page;
- understated tabs, underlined in the brand colour, that scroll on mobile;
- actions as cards, **grouped by intent** within each tab: Create, Configure, Manage, Import and export, Follow up;
- the company's palette instead of IOMAD's fixed colours, and tabs that are accessible to screen readers.

**Manage courses** (IOMAD course settings): the IOMAD table gains a **Category** column, after the course column, with the full path of its category (for example "Clinique des Tilleuls / Soins infirmiers").

**Everything is set in the company's profile** (IOMAD dashboard › Create company or Edit company › Appearance). At the top of that part, the **Épure appearance** section offers:

| Company setting | Effect |
|---|---|
| Brand colour | Any colour code, a colour picker, or the colours of the company's logo (swatches and eyedropper, including for a logo that has just been uploaded). Empty: IOMAD's heading colour, otherwise the site's colour. The accessible palette is recalculated. |
| Header colour | As the site, white, or brand colour. |
| Course banner | As the site, shown or hidden, for the company's users. |
| Learner overview on the dashboard | As the site, shown or hidden. |
| Mobile navigation bar | As the site, shown or hidden. |
| Dark mode | As the site, never, automatic based on the device, or always. |
| Footer | Text, legal notice, privacy, contact, other links; an empty field uses the site's value, shown greyed out. |
| Logo for the brand-coloured header | Optional: a version of the logo that is legible on the company's brand colour, often white on a transparent background. |
| Font | As the site, or one of the bundled fonts. |

IOMAD's native fields (logo, compact logo, custom CSS) remain below; Épure declares itself an IOMAD theme so that IOMAD displays them. IOMAD's colours (heading, main, link), which are only used by IOMAD themes, are hidden as long as the company uses Épure; a heading colour that has already been saved is carried over as the brand colour. Finally, the **Vocabulary** section sets the company's words, that is, its words for "company" and "department", in French and in English ("client" and "team" for one company, "agency" and "service" for another). Épure declares itself an IOMAD theme so that IOMAD displays these fields. The words for all companies are chosen on the "Épure: vocabulary" page; a company without its own words uses those.

Since language packs are shared by the whole site, these words are applied when strings are loaded, by a string manager that the theme enables on each page (Moodle's `$CFG->customstringmanager` mechanism), with a cache per company. **No change to `config.php` is needed.** It is only enabled if words have been chosen; if `config.php` already defines another string manager, that one is kept and the "Épure: vocabulary" page says so.

### Vocabulary

Site administration › Appearance › Themes › Épure: vocabulary (also linked from the theme's General settings tab).

Choose, for French and English, the words used for:

| Item | Words offered in French | Words offered in English |
|---|---|---|
| Courses | cours, formation, parcours, module, other | course, program, programme, module, class, learning path, other |
| Students | étudiant, apprenant, stagiaire, participant, collaborateur, other | student, learner, trainee, participant, employee, other |
| Teachers | enseignant, formateur, tuteur, intervenant, professeur, coach, other | teacher, trainer, tutor, instructor, facilitator, coach, other |

The vocabulary applies to **the whole of Moodle**: menus, Dashboard, course lists, participants, roles, reports, notifications, as well as plugin and IOMAD pages.

In French, the plugin does more than swap one word for another: articles, elision and agreement follow the chosen word. "Tous les cours" becomes "Toutes les formations", "Ce cours est masqué" becomes "Cette formation est masquée", and "l'étudiant" becomes "le stagiaire". For a word of your own, enter its singular, its plural and its gender.

How it works:

- the strings are written with Moodle's native **Language customisation** tool (`fr_local` and `en_local` packs): no Moodle file and no `config.php` is modified;
- strings you have customised yourself in that tool are kept, never overwritten;
- every rewritten string can still be edited in the customisation tool;
- "Restore Moodle's wording" removes only the strings written by the theme, as does uninstalling the theme;
- the words stay in place whatever theme a course or user uses;
- the first run takes about twenty seconds per language (Moodle loads the language pack into the tool), later runs a few seconds.

After installing a new Moodle version or language pack, apply the vocabulary again to cover the new strings.

With IOMAD, the same page offers the words "company" and "department" for all companies; each company can choose its own in its profile (see the IOMAD section).

### Advanced settings

Initial SCSS (to override variables) and SCSS added at the end of the stylesheet.

## Footer

At the bottom of every page, including the login page: the site name, free text (for example the organisation's address), then the **Legal notice**, **Privacy**, **Accessibility: …** (once the statement is published) and **Contact** links, and custom links. It is set in the **Footer** tab of the theme settings; with IOMAD, each company can have its own text and links (company profile › Appearance). When left empty, the "Privacy" and "Contact" links use Moodle's own when it has them (site policies, contact site support form).

## Key figures

On a Moodle site without IOMAD, the Dashboard of platform managers starts with its key figures: users (and those active this week), courses (and visible courses), active enrolments, completions over the last 30 days. With IOMAD, these are the figures of the selected company, at the top of the IOMAD dashboard.

## Installation and upgrades: "Moodle is working…"

On installation and upgrade pages (Moodle upgrade, new settings, plugins, installation from a ZIP file, environment check), clicking "Continue", "Install plugin" or "Upgrade Moodle database now" displays, after half a second, a "Moodle is working… Do not close or reload this page" window. It prevents a second click and is announced to screen readers. The script is written into the page, without Moodle's JavaScript loader, so that it also works during an upgrade.

## Dark mode

The **Dark mode** setting (the theme's General settings) can be *Never*, *Automatic, as the device* or *Always*. With IOMAD, each company can make a different choice in its profile. Each user can also choose their display in their preferences ("Aa" button): as the site, light, dark, or as their device.

Dark colours are calculated from the brand colour, with the same AA contrast; a brand-coloured header keeps its colour. Moodle and IOMAD pages (dashboards, courses, forms, menus, tables) switch to dark; the text editor keeps the appearance of its own theme.

## Learner dashboard

For a user who takes courses, the Dashboard starts with an overview, above Moodle's blocks:

- **Pick up where you left off**: the last course visited and not yet completed, with its image, progress, next activity and a button to continue;
- **In progress**: the other courses in progress, with their progress, and a link to "My courses";
- **Coming up**: the next deadlines across all their courses (assignments due, quizzes closing…);
- **Completed**: completed courses, with their date, and a link to the user's certificates when the platform issues them (IOMAD, Certificate, Custom certificate).

The overview can be turned off in the theme's General settings ("Learner overview on the dashboard"); with IOMAD, each company can make a different choice in its profile.

## Course page

At the top of each course, a **banner** shows the course image (or a generated pattern), its category and its title, with the breadcrumb and Moodle's usual actions. Then, depending on the role:

- **learner**: their progress, the next activity to do, the next deadline, and a **Continue** button that leads there;
- **teacher**: participants, learners active this week, assignments to grade, and shortcuts (participants, grades, settings);
- **visitor** (guest, non-enrolled user): the image, category and title only.

For learners, the title of each section shows how many of its activities they have completed ("2/5", with a tick once the section is complete). The banner can be turned off in the theme's General settings ("Course banner"); with IOMAD, each company can make a different choice in its profile.

## Branded e-mails

The site's HTML e-mails (notifications, messages, forums, assignments, IOMAD e-mails…) get:

- the **logo** at the top (otherwise the site name) and a **rule in the brand colour**;
- the message in a card, with its links in the brand colour;
- a **footer** with the site name, the footer text and a "Manage my notifications" link.

With IOMAD, the logo, colour, name and footer text are those of **the recipient's company**. The template uses tables and inline styles, which e-mail clients can read, and adapts to narrow screens. To do this, Épure overrides Moodle's `core/email_html` template, including for e-mails sent by scheduled tasks. Branded e-mails can be turned off in the theme's General settings ("E-mails in the colours of the brand"): e-mails then go back to Moodle's layout.

## Quick search (Ctrl+K)

A **Search** button in the header, or **Ctrl+K** (**⌘K** on Mac) from any page, opens a search window. Ignoring case and accents, it finds:

- **menu pages**: primary navigation, user menu, course or page tabs (and therefore the IOMAD dashboard, Site administration, the calendar, grades… depending on each user's permissions);
- **my courses** and the **activities** in my courses;
- other courses in the **catalogue**, with a link to the full search;
- for administrators, **administration pages** whose name or a setting matches, with a link to the administration search.

The ↑ ↓ arrows select a result, Enter opens it (Ctrl+Enter in a new tab), Esc closes the window. The window is an accessible dialog (list of options announced to screen readers, number of results). Search can be turned off in the theme's General settings ("Quick search").

## Mobile navigation bar

On phones, a bar fixed to the bottom of the screen, within thumb's reach, leads to the **Dashboard** (or to the site home if the Dashboard is disabled), **My courses**, the **catalogue**, **messages** (with the number of unread conversations) and the **profile**; the entry for the current page is highlighted. Moodle's floating buttons (help, course index) move up above it; on pages that have their own action bar at the bottom (grading, for example), it steps aside. It can be turned off in the theme's General settings ("Mobile navigation bar") and, with IOMAD, for each company in its profile.

## Course catalogue

The courses page (`/course/index.php`) becomes a **catalogue**: subcategories as chips with their number of courses, then the courses of the category and its subcategories as **cards** (image, category, name, summary, teachers). Each card shows whether the user is **enrolled**, or how to get into the course (**open enrolment**, **guest access**). Moodle's bar stays at the top (category menu, search, management actions). Course search results are shown with the same cards.

Before enrolment, a course's enrolment page **introduces** it: a banner with the image and a button to the enrolment options, the summary, the **outline** (sections and number of activities), teachers, dates, contents by activity type and the course's custom fields. Hidden sections and activities are not listed.

With IOMAD, the catalogue only shows the categories and courses that IOMAD allows the user to see. The catalogue and the course introduction can be turned off in the theme's General settings ("Course catalogue").

## Activity pages

In Moodle 4 and 5, with the side course index, the links to the previous and next activity disappeared. Épure brings them back on every activity page:

- **at the top**, a strip with the course (back link), the activity's position ("Activity 3 of 12"), the learner's progress and a **Reading mode** button;
- **at the bottom**, the previous and next activities as cards (icon, name, section); after the last one, a card leads back to the course. Text and media areas and activities that the user cannot see are skipped.

**Reading mode** hides the side panels and centres the content; it is saved in the user's profile, and the Esc key exits it. The **Mark as done** button is enlarged and takes the brand colour. These additions can be turned off in the theme's General settings ("Activity pages").

## Accessibility

The theme targets WCAG 2.2 level AA, RGAA 4.1.2 and EN 301 549: calculated contrast, keyboard focus always visible, a skip link, targets of at least 24 × 24 px, underlined links within text, and respect for the "reduce motion" setting.

**Display preferences.** The **Aa** button in the header (shortcut Alt + A) opens a panel in which each logged-in user chooses:

- the text size: small, normal, large or very large (90, 100, 115 or 130%);
- a reading font: the site's font, Atkinson Hyperlegible or OpenDyslexic;
- more text spacing (the values from WCAG success criterion 1.4.12), enhanced contrast, underlined links, reduced motion.

The change takes effect immediately and is then saved in the preferences of the user's Moodle profile: it applies on every page and on all their devices, from the very first page load. Visitors who are not logged in and guests get the default display. These preferences are declared to the Privacy API and exported with the user's data.

**Accessibility statement.** The **Accessibility** tab of the theme settings fills it in using the French (RGAA) format: compliance status, entity, compliance rate, auditor and audit date, non-compliant content, exemptions, content not subject to the requirements, contact (by default, the support e-mail address). Until a status is chosen, nothing is published. Once the status is chosen:

- the statement is published at `/theme/epure/accessibility.php` and can be read without an account. It also lists the accessibility measures taken by the theme (contrast, keyboard, structure, zoom, display preferences, automated tests) and the ways to seek redress from the Défenseur des droits (the French rights ombudsman); an e-mail address entered as the contact becomes a link;
- the notice "Accessibility: partially compliant" (depending on the status) appears at the bottom of every page, along with a link in the footer.

A theme alone cannot make a platform compliant: course content matters too, and a manual audit is still required before declaring compliance.

## Development

The palette variables are exposed as CSS custom properties (`--epure-brand`, `--epure-brand-text`, `--epure-on-brand`, etc.) for custom SCSS.

Tests:

```
vendor/bin/phpunit --testsuite theme_epure_testsuite
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @theme_epure
```

The Behat tests pass Moodle's axe-core audit ("the page should meet accessibility standards") on the Dashboard, the preferences panel and the accessibility statement.

## Licence

GNU GPL v3 or later. The bundled fonts are under the SIL Open Font License 1.1 (see `fonts/` and `thirdpartylibs.xml`).
