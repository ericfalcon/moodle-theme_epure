A modern, clean and accessible theme for **Moodle 4.5 LTS to 5.3** and **IOMAD 4.5 to 5.2**, child of Boost. Few settings, each of which changes a lot: the administrator chooses an identity, Épure computes the rest. A **getting started** guides the first settings, for the platform as for each IOMAD company.

**Free and open source**: every feature, the checking of Office and PDF documents included, is under the GNU GPL v3 or later. No paid version, nothing held back behind a licence.

### One colour, an accessible palette

Choose a brand colour (or click a colour of your logo) and an optional accent colour: Épure derives the whole palette and keeps text at 4.5:1 at least, in light and dark mode, whatever the colours. The header can be white, in the brand colour or a gradient; a header painted by custom SCSS keeps readable text.

### Accessibility built in

- Display preferences for every user, visitors included (« Aa », Alt + A): text up to 200 %, Atkinson Hyperlegible, OpenDyslexic or Lexend, text spacing, shorter lines, high contrast, underlined links, reduced motion, still images, a reading ruler or mask, reading aloud with the voices of the device (nothing leaves the browser), light, dark, yellow on black or cream. Profiles in one click: Dyslexia, Low vision, Concentration, No flashing, with a guide « Which profile to choose? » to print. Kept in the profile; the button shows when the display is the user's own.
- Pages that reflow down to 320 px, even with text at 200 %; focus always visible and never under a fixed bar; 24 px targets.
- Keyboard shortcuts (« ? ») and keys of navigation between activities, sections and main pages, off by default.
- An accessibility statement filled in step by step, on the model of the country of the site (RGAA, EN 301 549 or WCAG 2.1 AA), mentioned on every page; with IOMAD, each company can publish its own.
- A content checker for teachers, as they write: images without text alternative, links that say nothing, tables without headers, skipped heading levels, colours that do not contrast. Nothing is ever blocked.
- A guide « Accessibility of the course » (off by default): every Moodle activity and the 47 most installed activity plugins are read; Word, PowerPoint, Excel, LibreOffice and PDF documents are checked; each mistake with its severity (blocking, serious, minor) and a link to its place; a score of the course out of 100 % and what to fix first; text alternatives, empty titles, moving texts and more corrected from the guide; report exported as CSV, Excel, ODS or PDF.
- The accessibility of all the courses on a management dashboard: average score, courses with blocking mistakes, lowest categories, week by week over 12 weeks, downloadable for a quality process or an annual audit; the score of each course in « My courses » for its teachers. Computed in the background by the cron.
- Defects of Moodle itself repaired where a theme can, several found by an RGAA audit of a Moodle platform: the calendar month with its caption and column headers, the key Space on links made buttons, « (required) » in the label of required fields, a name for each navigation, the quiz keyboard trap, the dark mode of the quiz and gradebook. What a theme cannot fix is listed for the statement and reported to Moodle.
- Tested on every change: automated pre-audit (axe-core, WCAG 2.2 AA) of 52 Moodle and 24 IOMAD pages in light, dark, yellow on black, cream, on phones and with text at 200 %, and a visual regression test.

### For learners

- A dashboard: where to resume, what is due this week, what is completed.
- A page « To do »: activities still to do in all their courses, by deadline, filtered by course and period.
- « My courses » by role, a catalogue with course pages, progress per section that follows each activity marked as done, previous and next activity, a reading mode.
- New activities marked « New » since the last visit.
- A preview of the files without leaving the course: PDF, images, videos, sounds and text, and the text of Word, PowerPoint, Excel and LibreOffice documents, readable with a screen reader. Opening a file counts as viewing it.
- « My summary »: a printable page or a PDF of their courses, progress, grades and certificates, tagged for the screen readers (PDF/UA, checked with veraPDF; with PHP 8.2 and later), with a code the recipient verifies on the platform; the learner can revoke it.
- A bottom navigation bar on phones and an installable web app.

### For teachers and administrators

- Your own words in all Moodle: « programme » or « learning path » for courses, « learner » or « trainee » for students, « trainer » or « tutor » for teachers, with the grammar of French and English. Written with Moodle's language customisation: no file changed, undone in one click.
- « My courses » as a table for teachers: learners, active this week, to follow up, completion, to grade, next deadline, accessibility score; exportable.
- A management dashboard (also per IOMAD company): activity of the last 30 days, courses most followed, completion, accessibility of the courses.
- Quick search (Ctrl+K / ⌘K) across menus, courses, activities and administration pages.
- Settings in tabs, a clearer administration home, long forms with their sections listed, the save buttons in view and their state told (« Changes not saved »).
- After an update, what is new in Épure on the administration home.
- A course header for teachers with participants, active learners and work to grade.
- Branded e-mails and a certificate template for tool_certificate and Course certificate.

### With IOMAD

Each company has its own colours, logo, font, vocabulary and accessibility statement, on its login page, dashboard and courses, its own management dashboard and its own getting started for its managers.

### Compatibility

- Moodle 4.5 to 5.3, IOMAD 4.5 to 5.2, PHP 8.1 to 8.4, PostgreSQL and MariaDB, tested in continuous integration (PHPUnit, Behat); a job follows Moodle 6.0.
- Privacy API for every stored data. No external service; fonts bundled (SIL Open Font License); the tagged PDF made with tc-lib-pdf (LGPL), bundled.

An automated pre-audit does not replace a manual audit. Épure is independent, not affiliated with Moodle HQ or IOMAD.

Documentation (English and French): <https://github.com/ericfalcon/moodle-theme_epure>
