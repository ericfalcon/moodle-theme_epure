# Changes

## 1.5.0 — the documents read in the guide « Accessibility of the course », and a way back from the reminder

- The documents given in a course are read (RGAA 13.3), each with what it holds in the guide « Accessibility of the course » (« File « Instructions.docx »: images without a text alternative: 3 · no style of title · no title in the properties »), with a link to change where it is. Documents of Word (.docx, .docm), PowerPoint (.pptx, .pptm), Excel (.xlsx, .xlsm) and LibreOffice (.odt, .odp, .ods): the images and the charts without a text alternative (neither a description nor marked decorative), a long text without any style of title and the levels of titles skipped, the tables of data without a repeated header row, the slides without a title, the sheets left with their name by default (« Sheet1 », « Feuil1 »), the title in the properties. PDF files: without a language, made only of scanned images (without any font). Each message says how to fix it in Word, PowerPoint or LibreOffice. A document stays to confirm by a person for what a machine cannot see (the descriptions say what the images show, the order of reading, the contrasts); the old binary formats (.doc, .xls, .ppt) cannot be read and say so. The XML of a document is read without the network nor its entities; a file of more than 30 MB is not opened; what is found is kept by content of file (new cache documentcheck).
- Guide « Accessibility of the course »: an activity with nothing left no longer offers to change it, nor one whose mistakes each have the link of their place (a chapter, a page, a question, a file): its settings have nothing to change.
- Guide « Accessibility of the course »: the state of each part is coloured, red for the points to fix, orange for what is left to confirm, green for « Nothing left » (with its icon, contrasted in light and dark); the cause of each point to fix is in bold red, before how to fix it, and so are the mistakes of each place under « Where ». A button « Cancel » next to « Save the confirmations » goes back to the course without saving. In French, the places listed under « Where » no longer show « &nbsp; ».
- Reminder « Making my courses accessible »: a link back, to the guide of the course it was opened from, else to the dashboard (to the home page for a visitor). The point of the documents of an office suite is now found by Épure (★).
- PHPUnit tests: documents of Word, PowerPoint, Excel and LibreOffice made for the tests, with and without mistakes, PDF files, damaged and old files.

## 1.4.1 — the documents with macros in the guide « Accessibility of the course »

- The documents of Word, Excel and PowerPoint with macros (.docm, .xlsm, .pptm) and their templates, and the RTF files (text/rtf), were not taken for documents of an office suite: the guide said « Nothing left » instead of asking to confirm that they are accessible. Found on a course with an Excel file with macros. PHPUnit test of the types of documents.
- Course page in edit mode: the link « 62 points of accessibility » of an activity took the whole width of its line (in the column of the name of the activity); it is as wide as its text.

## 1.4.0 — accessibility as the content is written, the place of each mistake, images used as links and long texts without titles

- Under each editor of the forms (a chapter of a book, a page, the description of an activity…), a line tells as the content is written what makes it harder to use (« Accessibility: images used as links without a text alternative: 2 · long text without titles »), or that there is nothing to report, with a button « Show » that selects each element concerned in the editor TinyMCE, one after the other. The line is read again only when it changes, not at each key. With the plain text editor, the line without the button.
- Two new mistakes, in the check of the forms, the line under the editor and the guide « Accessibility of the course »: an image used as a link without a text alternative (often an image marked « decorative only » left in a link, as copied from Moodle Docs: a screen reader says « link » without where it leads; its image is no longer counted as an image too), and a long text (400 words) without any title. The links without text are named in the message of the links that say nothing.
- Guide « Accessibility of the course »: the mistakes of an activity of several texts are given where they are, chapter of a book, page of a lesson, question of a quiz, description or content, each with a link to change it. The question banks of Moodle 5 (never shown on the course page; their questions are read with the quizzes) are no longer listed. The bar « Save the confirmations » takes the whole width, and the page keeps its height free so that an element reached with the keyboard is never under it (WCAG 2.4.11).
- Reading mode of the activities: put aside in edit mode, as the side panels hold the tools to change the activity (the table of contents of a book, with its buttons to add a chapter); its button is hidden in edit mode.
- PHPUnit tests (images used as links, long texts, places of a book), Behat (the line under the editor).

## 1.3.0 — the help of the keyboard shortcuts, and the reference of the CSS custom properties `--epure-*`

- Help of the keyboard shortcuts: the key « ? » opens a window that lists the shortcuts of the page, by group: on every page (« ? », Ctrl + K for the quick search, Alt + A for the display preferences, Tab for the link « Skip to main content », Esc), in the quick search (the arrows, Enter, Ctrl + Enter) and in the text editor (Alt + F10 for its toolbar, Alt + 0 for its help). Only the shortcuts of what is on: without the quick search or the panel « Aa », theirs are not listed; a visitor has no editor. On a Mac, the keys show as ⌘ and ⌥. A native modal window (dialog), with its title focused at the opening and the focus given back at the closing; the shortcuts in tables (keys, action), one per group. axe-core.
- The key « ? » is a shortcut of one character (WCAG 2.1.4): it is ignored while typing in a field, a list or the editor, and when another window or menu is open; the window has a box to turn it off, kept in the preferences of the user (new preference theme_epure_shortcuts, declared to the privacy API) or in the browser for a visitor. The help still opens from the link « Keyboard shortcuts (?) » of the window « ? » of the footer, and from the button of the panel « Aa ».
- PHPUnit and Behat tests: the key, the window, turned off and kept from page to page.
- Documentation: the reference of the CSS custom properties `--epure-*` (palette of the brand and its pairs of contrast, surfaces, shapes, states, header), what makes them change (dark mode, IOMAD companies, display preferences) and how to use them in a custom SCSS, in docs/Variables-CSS.md (in French).

## 1.2.0 — the quick search for the visitors

- Quick search (Ctrl+K) for the visitors who are not logged in, and the guests, on a site open without logging in: the courses of the catalogue (with Moodle's course search, as the page of the search of the courses: never a hidden course), the categories they can see (with their parents), and the public pages: log in, create an account when new accounts are open, the courses, the accessibility statement once published. A public service of its own (theme_epure_quick_search_public, without session key, as the other public services of Moodle) answers only what a visitor sees; the service of the users with an account stays closed to them. Nothing for the visitors on a site where logging in is required ($CFG->forcelogin, on by default from Moodle 5.2 and with IOMAD). With IOMAD, the courses and the categories belong to companies and a visitor belongs to none: they search the public pages only. The field says « A course, a category, a page… ». A link of the menus of the page already found by the server is no longer listed twice. PHPUnit and Behat tests, axe-core.

## 1.1.0 — the guide « Accessibility of the course » for teachers, and the reminder « Making my courses accessible »

- Guide « Accessibility of the course » (theme/epure/courseaccessibility.php), for the teachers who can change a course: each part of the course is read. Found without a person, in the presentation of the course, the summaries of the sections and the texts of the activities (descriptions, pages, chapters of books, pages of lessons, questions of quizzes): the mistakes of the content checker (same rules, written again on the server), frames without a title, videos without a track of subtitles; the PDF files without structure (not tagged: the root of the tags looked for, also in the compressed streams of objects); the images of the H5P contents without a text alternative; the names of activities that say nothing (« Document 2 ») or that two activities share. To confirm by a teacher: the subtitles of the videos hosted elsewhere and of the video files (unless a file of subtitles is beside them), the transcript of the audios, the documents of an office suite, the PDF files that cannot be read, the extra time of the timed quizzes and of the assignments with a deadline (with a link to their user overrides), the instructions and the use of colours of the course. Only the files of the teachers are read, never those of the learners.
- The confirmations are shared by the teachers of the course, with who confirmed and when (new table theme_epure_a11ycheck); a file replaced is to confirm again; a teacher unchecks only what was shown confirmed, never what another confirmed meanwhile. Deleted with the activity, the course or the user; declared, exported and deleted with the privacy API.
- Where to find it: in the menu of the course (« More »); in edit mode, a line under the bar of the edit mode with the figures of the course and, beside each activity concerned, a link « 2 points of accessibility » to it in the guide (the course editor renders the activities again: the link comes back); in the window of the content checker, « See the accessibility of the whole course ». The learners see none of it.
- Printed, the guide is the report of the course, with its name and the date, without its buttons. The reminder « Making my courses accessible » (theme/epure/checklist.php, open without logging in) lists 16 points in six themes on one page to print, a star marking those the guide finds itself.
- A setting of the Accessibility tab turns the guide on, off by default: not all organisations must meet the criteria of the RGAA. An IOMAD company can choose otherwise.
- What is read of a course is kept in a cache (new cache courseaccessibility), forgotten when the course, an activity, a section, a chapter of a book, a page of a lesson or a question changes, within the hour otherwise; whether a PDF is tagged, by its content (new cache pdftagged).
- PHPUnit tests (the rules of the texts, the PDF plain and compressed, H5P, a whole course, the confirmations, the cache, the privacy API) and a Behat scenario with axe-core on the guide and the reminder.

## 1.0.2 — My summary as a PDF with a code of verification, quick search with the keyboard, tests of the e-mails and error pages, documentation in the repository

- « My summary » as a PDF with a code of verification: « Download the PDF » issues a document, the summary of that moment in the colours of the brand (of the IOMAD company of the learner), with a code of twelve letters and digits and a QR code. Each document is kept with what it showed (new table theme_epure_summary). The page of verification (theme/epure/verify.php, open without logging in, as the codes of Moodle's certificates) shows what the platform issued under a code, on which date; the case, spaces and hyphens of the code do not matter. The PDF is made with Moodle's PDF library (TCPDF), without any library added; it is not tagged for screen readers, so the page stays the accessible version and says so. The documents are declared, exported and deleted with the privacy API, and deleted with the user. PHPUnit and Behat tests, axe-core on the page of verification.
- Quick search (Ctrl+K) with the keyboard: the arrows moved two results at a time, and Enter could open the result twice. The script of Boost also moves through the options of every combobox of the page; the keys of the list are now handled by the quick search only. Tab stays in the window, from the field to the button Close and back, instead of leaving for the bar of the browser; the list of results is no longer a stop of Tab.
- Quick search with a screen reader: the first result is chosen for Enter without the screen readers moving to it at each letter typed (the pattern of the list with automatic selection of the ARIA practices); the arrows move them to the result chosen, marked as selected. « Searching… » is no longer read after each letter: the list is marked busy, then the number of results is read once. The message « no result » is out of the list of results, which holds only options. Behat scenario with the keyboard alone.
- With IOMAD, when config.php sets another string manager ($CFG->customstringmanager), the vocabulary of the companies is turned off: the theme settings now say so too (Identity tab), not only the page of the vocabulary.
- After each update of Moodle or of a language pack, what to do (apply the vocabulary again; with IOMAD, check config.php) is shown on the page of the vocabulary and in the Identity tab, and in the README (Upgrading).
- Tests: the e-mails in the colours of the brand (Moodle's template core/email_html, replaced) for a recipient in an IOMAD company (its colour, its escaped name, its footer), a company that turns the branding off or uses another theme, and from the command line (scheduled tasks); the error pages (card, title, Moodle's message, link back to the dashboard or the login page, page error/index.php, axe-core) in Behat.
- Documentation: the documentation in French (the former wiki) and the roadmap are now in this repository (docs/, ROADMAP.md), left out of the plugin archive; the README links to them instead of the former repository. Upgrade notes, version by version, in the README. The shortcut Alt + A is described for every system and keyboard layout; the languages of the vocabulary and their limits are documented.

## 1.0.1 — dark mode for the colours of the contents, the quiz navigation, C4L, the Tiles, Edwiser and Trail formats; focused links in the colours of the page; the company logo readable

- Colours written in the content itself (books, pages, labels, descriptions, forum posts…), often pasted from a word processor or a web page, in dark mode: chosen for a white page, black or dark text could not be read, and dark or vivid colours fell under 4.5:1. A colour of text takes the ink of the mode, a link the colour of the links; a passage on a background of its own keeps it, with the text of the light mode. Checked on a chapter of a book: no text under 4.5:1, in light and dark mode.
- Edwiser course formats (format_remuiformat) in dark mode: its cards, the activities of its general section, the sections of its list and its badges are painted white whatever the mode, with text colours of their own for a light background; the titles of the activities, light in dark mode, could not be read. They stay white, light islands where the palette of the theme goes back to its light values. Its labels without a card, its summary of the course and its link « Show more » take the colours of the mode. Checked on Moodle 4.5 with the card and list layouts, 4.1.22: no text under 4.5:1, in light and dark mode.
- Trail format (format_trail) in dark mode: the box of a section opened from the trail and the edit actions of its images were white whatever the mode, under the light text of the dark mode. They take the surface of the mode. Checked on Moodle 4.5 with Trail 5.0.0.0.
- Tiles format (format_tiles) in dark mode: the tiles, the sections in edit mode and the buttons of the format were painted white or light grey whatever the mode, under the light text of the dark mode. They take the surfaces of the dark mode. The headers of the sections in edit mode (Moodle's light tint of the primary colour, fixed at build time) take the tint of the brand of the mode. Checked on Moodle 5.0 with Tiles 5.0.0.23.
- Components for Learning (tiny_c4l) in dark mode: the boxes (key concept, tip, reminder, attention, example, reading context, expected feedback, card, learning outcomes, concept review, further reading, do and don't) keep their light colours whatever the mode, and their text, light in dark mode, could not be read. In dark mode they keep their colours, with the text and the links of the light mode.
- Review of a quiz in dark mode: Moodle paints the buttons of the navigation light green, yellow and red whatever the mode, and the numbers, light in dark mode, could not be read. The buttons right, partly right and wrong (or not answered) are now in the colours of the states, in light and dark mode. The icon of the state, cut by Moodle in a band at the top of the button, is shown whole in its corner.
- IOMAD: in the brand-coloured header, the logo of the company in the company switcher, made by IOMAD from its main logo for a white background, was dull or unreadable on the colour of the header. It is shown on a white backing, as on the login page (IOMAD 4.5 to 5.2).
- A focused link (also after a click) was tinted by Moodle with the colour of the site, lightened at build time: with an IOMAD company in another colour, a line of the colour of the site showed above the link, for example a turquoise line above the section chosen in the list of a long form. Focused links are now tinted in the colours of the page, those of the company when it has its own, in light and dark mode. The rows of the list of a long form keep their look once clicked; the outline of the theme still shows the focus reached with the keyboard.

## 1.0.0 — stable

- First stable version, from its own repository (moodle-theme_epure), the plugin at its root, for the directory of the plugins of Moodle. The same theme as 0.56.5: Moodle 4.5 to 5.3 and IOMAD 4.5 to 5.2, PHP 8.1 to 8.4, PostgreSQL and MariaDB.
- The history of the theme comes with it; the roadmap, the wiki and the mock-ups stay in the repository moodle-theme-iomad.

## 0.56.5 (RC) — the user menu under the pointer

- User menu: under the pointer or focused, an item was in the colour of the brand on the fill of the brand, unreadable (since 0.56.3). It is now in the colour of the text on the brand, with its arrow, in light and dark mode. The links of the blocks of the dashboard keep the colour of the brand under the pointer, underlined.

## 0.56.4 (RC) — matrix of non-regression of the accessibility

- Behat matrix of non-regression of the accessibility functions together: brand colours light, dark and of mid tones; all the display preferences on; the elements focused with the keyboard never under a fixed bar, on a computer and a phone; the panel « Aa » with Alt + A and Escape, the focus given back; the display preferences from page to page; a timed quiz; the colours of the IOMAD companies, light, dark and of mid tones; the menus and the windows (user menu, quick search) with a name, closed with Escape, the focus given back. Each page in the four displays.
- Found by the matrix: with OpenDyslexic and the text spacing of WCAG 1.4.12, the frame of the content of a page could scroll down by a little, a scroll the keyboard could not reach (WCAG 2.1.1). The frames of the contents now scroll across only, and their long words wrap.
- Found by the matrix: with a light brand colour (yellow), the section to continue in the course index read at 4.18:1 on the grey of Moodle 5.x under the pointer or on the active section. It keeps the tint of the brand in every state (4.5:1 at least).
- Template of certificate: a very long name of course (155 characters) went past the frame on both sides. The texts now wrap inside the frame, the name of the course on two lines at most (18 pt), the date of issue a little lower. Checked on the PDF of a certificate issued by the activity Course certificate, with a long and a short name of course.
- The activity Course certificate (mod_coursecertificate) is installed on the jobs of the continuous integration whose version it supports (Moodle and IOMAD 4.5 to 5.2), from the fork of the maintainer; its page, for a learner and a teacher, with the template of Épure, in the four displays.
- Found by the matrix: on phones, with the text at 115 or 130 % in « Aa », the buttons of the header and the switch of the edit mode went past 390 and 320 pixels, the user menu or the switch off the screen (the page scrolled across on Moodle 5.2, the buttons were out of reach on the others). On phones, the header keeps the size of its buttons whatever the size of text chosen; the zoom of the browser still enlarges it.
- Content checker of the teachers, without false alerts: a decorative image (alt="", with or without role="presentation"), a table of layout (role="presentation" or "none"), headers on several rows, a short link clear in its sentence (« The programme is here. », WCAG 2.4.4, RGAA 6.1). Only a link alone in its block is reported. After « Save anyway », the next saving is checked again (a form refused for another field, then changed). Behat scenario.
- Roadmap: the pre-audit, Alt + A and the quick preferences done; the steps of the publication.

## 0.56.3 (RC) — My summary from the menu and the profile, one style for the links that stand on their own

- My summary, once enabled, is reached from the dashboard, the user menu (after the grades) and the reports of the profile of the user. Before, only from the overview of the learner on the dashboard: a user taking no course as a learner (managers of IOMAD, administrators) or with the overview turned off had no link to it.
- The links that stand on their own follow one rule, that of Épure: the colour of the brand and an arrow on the right, in a card when needed. The lists of links (administration, profile, preferences, IOMAD), the links of the blocks of the dashboard (« My courses », « Calendar », « My summary », what a list leaves out), the user menu (the link to the languages has the same arrow), the activities of the course page, the previous and next activities and the courses of My summary. Links inside text stay underlined.
- The icons of the previous and next activities take the colour of the activities, the brand or Moodle's (the H5P logo kept its own blue).
- Behat pre-audit: axe is loaded and waited for before each check (on the first page after the caches are purged, the web server of the tests served it late).

## 0.56.2 (RC) — pre-audit of accessibility of the current version, on every version

- Pre-audit (replaces that of the 0.21): the same 40 pages of a visitor, a learner, a teacher and an administrator, each in four displays (light and dark at 1300 px, light at 390 and 320 px), with axe (WCAG 2.2 A and AA) and no scroll across on phones: 160 checks on every job of the CI, Moodle 4.5 to 5.3 with PHP 8.1 to 8.4, PostgreSQL and MariaDB; on IOMAD 4.5, 5.1 and 5.2, those and 24 pages of IOMAD in addition (416 checks). Behat and the plugin Certificate now run on every job. Report in the wiki (Pré-audit d'accessibilité).
- Dark mode: overdue assignments, passed and failed grades, dates of the activities and days of the calendar, « Collapse all », « Log in » of the visitors, the fields of the login page (Moodle 5.2 and 5.3), the design system of Moodle 5.3 (navigation pills, edit switch), the overview of completion of IOMAD.
- Course index: the page being read in the fill of the brand with its readable text; hidden and locked activities readable on hover; the tree of the subsections corrected on Moodle 5.0 (script theme_epure/courseindex_tree, as Moodle 5.1).
- Phones (390 and 320 px): nothing past the edge of the screen: rows, lists of choices, text fields, buttons, fieldsets, the bar of actions of Moodle 5.3, long words, the header at 320 px (the messages stay in the bottom bar), the figures of My summary; the tables of the submissions, of the emoticons and of IOMAD scroll in their own frame; the days of the weekend as boxes with their day.
- Setting of the emoticons: each of its 155 fields named. Autocomplete fields: the invalid attribute list removed, so their role combobox is allowed. Examples of the help of the settings wrap.
- Targets of 24 px: boxes of the settings of the administration, category names of the course management, action buttons in the rows of tables (companies of IOMAD), « Download submissions in folders », reset of the tables. Links of the notice boxes underlined.
- Script theme_epure/aria_repair: aria-hidden="1" becomes "true", aria-controls keeps the elements that exist, the department list of IOMAD gets its name.
- Aa panel: Alt + A no longer taken while typing in a field (Option + A writes « å » on a Mac); the preferences saved one request at a time, the last choice kept.
- Roadmap: « Consolidation before the 1.0 ».

## 0.56.1 (RC) — text on the brand colour at 4.5:1, logo of the certificate template

- Contrast: the text on the brand colour (buttons, header, badges, application) reads at 4.5:1 at least, as normal text (WCAG 1.4.3), on the colour and on its hover, in light and dark mode, whatever the brand colour. Before, white or the dark ink only had to reach 3:1: on some mid tones (as #2D78E1 or #E13C2D) the text was at 4.3:1, and on the hover of many colours below 4.5:1. When neither reaches 4.5:1, the brand colour of the fills is moved as little as needed (on 258 of 5832 colours measured in light mode, by 31 of 255 at most on a channel); the hover goes the other way when it would lose contrast. The tests sweep the colours and fail below 4.5:1 (reported by an external audit of the 0.56).
- Certificate template: the logo of the site fits a box of 60 × 24 mm above the title, whatever its shape; a square logo no longer covers the title. Checked on the PDF of a certificate issued to a learner.
- The page of the certificate template no longer shows a notice of debugging with Moodle 4.5 and later (button), and its button no longer sits beside the preview.
- From 1200 pixels, at the end of the page, the links of the footer stop above the help button « ? », which covered one of them (WCAG 2.4.11).
- Checked with the plugin Certificate installed: the template made from the administration page and the PDF of a certificate issued to a learner. The name of the course comes from the activity Course certificate (mod_coursecertificate), as in the demo template of the plugin.

## 0.56.0 (RC) — certificate template in the colours of the brand

- Certificate template (roadmap 0.55): for the plugin Certificate (tool_certificate), a page of the administration (Appearance › Themes › « Certificate template in the colours of the brand ») creates, on a click, a template: a landscape A4 page with the frame in the colour of the brand (the one drawn for the IOMAD companies), the logo of the site, the title in the colour of the brand, the name of the learner, the name of the course, the date of issue, the QR code to check it and the name of the site. The template is then the administrator's, like any other: edited, duplicated, used in the courses with the Course certificate activity, kept if the site leaves the theme. Each click makes a new one. Without the plugin, the page says how to install it.
- The option « frame of the certificates in the colour of the brand » of the IOMAD companies is kept.
- CI: the plugin Certificate is installed on Moodle 4.5 and 5.3, to test the template.
- No new setting.

## 0.55.0 (RC) — accessibility statement step by step, shorter lines, nothing over the content

- Accessibility statement, step by step (roadmap 0.54): a guided form in five steps (who publishes it, the audit, the compliance status, the contents not accessible, the contact), with what the RGAA asks for at each step. It fills the same settings as the Accessibility tab. The status step recalls the rate of the audit and the rule of the RGAA; the theme does not compute the status. Previous step and « Save and continue » keep what was typed; the steps are links to come back to any of them.
- The statement: a button « Print the statement » (also to save it as PDF), and for the administrators a button « Fill in the statement ».
- Display preferences (button « Aa »): a new switch « Shorter lines », saved in the profile like the others: the long texts of the courses (pages, books, lessons, descriptions and instructions of the activities, summaries of the sections, posts of the forums) at 70 characters per line at most. Measured on a page of text: at most 65 characters per line at a zoom of 100 %, 200 % and 400 %, with the text at 100 % or 130 %, and never a scroll across. The administrator can set it by default for all, as the other preferences (Accessibility tab). The accessibility statement lists it among the measures of the theme.
- Nothing fixed over the content (WCAG 1.4.10, 2.4.11 and 2.4.12), on phones, tablets and with a zoom of 200 %:
  - below 1200 pixels, the help button « ? » no longer floats over the text: its contents (help of the page, support, login, data retention) show at the end of the page, as Moodle does on the smallest screens;
  - below 992 pixels, the buttons of the drawers (index of the course, blocks) go at the top of the content, in its flow, instead of at the edges of the screen over the text;
  - an element reached with the keyboard is never hidden, even in part, under the bars fixed at the top and at the bottom of the screen (header, navigation of the phones, « Continue » of the course page, buttons to save of the long forms), the text editor included: the page scrolls it into the clear (script theme_epure/focus_clear);
  - from 1200 pixels, on a long form, the help button goes above the bar of the buttons to save.
  Checked with the keyboard on an activity, a course, the dashboard and the settings of a course, at 390, 720, 1000 and 1440 pixels.
- No new setting of the site besides the default of this preference.

## 0.54.0 (RC) — My summary

- My summary (roadmap 0.53): a page each learner prints or saves as PDF, from the « Completed » part of their dashboard: the courses they take with their progress, the date of completion, their grade (when the course shows the grades to the learners, and the grade is not hidden from them) and the activities completed with their date, then their certificates (Certificate, Custom certificate, IOMAD). Their own data only. On paper, the header in the colours of the brand and no button; a note says that the summary shows what the platform recorded, and does not prove on its own attendance nor the achievement of the objectives.
- A setting « My summary » (Courses tab), off by default.

## 0.53.0 (RC) — adding a block, summary of the course

- Edit mode: the place to add a block (dashboard, block drawer of the course and of the other pages) becomes a dashed card in the colour of the brand, with « Add a block » and a sentence of what can be added (« Timeline, calendar, badges and more »); the whole card can be clicked.
- The window « Add a block »: a search at the top, which stays in view while the list scrolls and keeps the blocks whose name holds the words typed (without minding the accents nor the capitals); each block as the row of a menu, as on the preferences, with its icon and « Add ». The links that add the blocks, and the form of those that ask for settings first, stay Moodle's. In light and dark mode, on Moodle 4.5 to 5.3, IOMAD blocks included.
- Course page, for a teacher: what the course holds under its title, « 5 sections · 26 activities » (the sections of the page, the activities and resources that open a page of their own, hidden ones included; not the text and media areas). The count of the activities reads as the one of the presentation of the course in the catalogue.
- Roadmap: 0.56 (adding a block) and 0.57 (summary of the course) published in 0.53.0.

## 0.52.2 (RC) — several deadlines at once

- Dashboard of a learner, activity to continue: with no activity due in the next two days, an activity overdue now comes before the one started (the most recently overdue, the likeliest still to be accepted). The same on the banner and the page of the course.
- Dashboard, « In progress »: the courses with an activity overdue come first, then those due soon, so that a course with a deadline is never left out of the list by the others.
- Dashboard: the lists that leave items out say how many (« 2 more overdue », « 3 more this week » with a link to the calendar, « 2 more courses in progress » with a link to My courses), rather than looking complete.

## 0.52.1 (RC)

- Edit mode: the banner no longer says that every change is saved as you go. Moves, hiding and renaming are saved at once by Moodle; adding an activity and its settings are saved with their form.
- Vocabulary in French: a participle after « peut être », « doivent être »… agrees with the new word too (« Des formations peuvent être ouvertes », not « ouverts »). The vocabulary is applied again on upgrade.

## 0.52.0 (RC) — long forms, lists of links

The theme moves from beta to release candidate (MATURITY_RC).

- Long settings forms (settings of a course, of an activity, of a section, the forms of IOMAD's dashboard: company, user, course, licence, department…): the sections as cards, the fields before the first section in a card of their own, as on the preferences; the buttons to save stay at the bottom of the screen while the form goes on, above the navigation bar of the phones.
- On wide screens, a list of the sections beside the form, as the rows of a menu in a card: a click opens the section and shows it, the section being read is marked, and a section with an error gets a red dot (and the word « Error » for the screen readers). The fields, their order and the saving stay Moodle's.
- Lists of links presented everywhere as on the preferences and the profile (rows of a menu, a line between them, an arrow on the right, the colour of the text): common tasks and tabs of the site administration, pages of a category of the administration (as a card), administration of a course (its parts as cards, as the site administration), reports of a course, and the IOMAD dashboard (its groups Create, Configure, Manage… as cards of rows, with their icons). A part of a tab without pages of its own no longer shows as an empty card; the cards of the tabs go on two columns, then one, on narrower screens.
- Text editor in dark mode: the link of its status bar takes the colour of the links of the light palette, readable on its white.

## 0.51.1 (beta) — security

An audit of the security of the theme (entry points, output, access to the data, IOMAD companies). Updating is recommended, in particular with IOMAD.

- Vocabulary of an IOMAD company (high): the words typed by a company manager went unchanged into the strings of Moodle, which are trusted HTML, also in attributes; a word with quotes could inject script into the pages of the users of the company, and of a site administrator working on it. The words now keep letters, digits, spaces, apostrophes, hyphens and dots only, 40 characters at most, when saved and when read (a word saved before is cleaned too).
- Files of an IOMAD company (medium): the logo and the fonts are kept and served only with the extensions of their area (svg, png, webp, jpg; woff2, woff). An HTML page uploaded there by a company manager (the types of the file picker are not checked by the server) would have opened from the site itself.
- Key figures of the IOMAD dashboard (medium): shown only to the managers of the company and to whoever sees all the companies, no longer to its learners, its department managers or the users of other companies (IOMAD's dashboard opens to every user).
- Hidden courses (medium): no longer shown to the learners enrolled in them in My courses and on the dashboard, as in Moodle's own « My courses »; those who can see hidden courses still do.
- IOMAD dashboard (low): its company selector changes the theme of the page only for a company the user may choose.
- Quick search (low): an administration page is offered only to whoever may open it.
- The cache of the learner's week is forgotten when their progress changes.
- Separate groups: a teacher who cannot see all the groups gets the figures of their own groups only (participants, active this week, submissions and quiz answers to grade), on the course page and in My courses.
- Catalogue with IOMAD: the courses are counted and paged once IOMAD kept those of the company of the user (it filtered them after taking the page): the number of courses, of each category, and the pages hold the courses of the company only.

## 0.51.0 (beta)

- Site administration in cards. On its home, « Common tasks »: a card for Users, Courses, Appearance, Plugins, Reports and Server and, with IOMAD, for IOMAD, each with its common tasks (browse the users, add a course, the settings of Épure, the logs, the scheduled tasks…); the title of a card leads to the whole part. Under them, every tab of Moodle's administration (General, Users, Courses, Grades, Plugins, Appearance, Server, Reports, Development) shows its parts as cards in columns rather than rows separated by lines. Nothing is removed: the tabs and all their links stay. A task whose page does not exist in the version of Moodle, or that the user cannot open, is left out. On phones, the search of the administration goes under the title instead of cutting it. No setting.
- Roadmap: 0.52 (recent searches) dropped; the administration in cards becomes 0.51, « My summary » 0.53.

## 0.50.0 (beta)

- Getting started by role: three short guided tours, each opening with a welcome message and ending with how to take it again. The learner's on the dashboard (pick up where you left off, this week, quick search, display, profile); the teacher's on the course page (the course in figures, edit mode, managing the course); the manager's on the dashboard (site administration, IOMAD dashboard, quick search, account). They are user tours of Moodle, created once with the theme: the administrators can edit, disable or delete them (Site administration › Appearance › User tours); they go with the theme when it is uninstalled. They show only with Épure (filter « Theme ») and to their audience, through a new filter « Épure audience » of the tours (learner, teacher, manager). Their texts are strings of the theme, in the language and with the vocabulary of the user. A step whose element is not on the page is skipped (on phones, the menus folded away). No setting.
- User tours: the steps without a target (welcome and end messages) in the middle of the screen; on phones, after scrolling, Moodle placed them out of sight.

## 0.49.0 (beta)

- Course page in edit mode (roadmap item 0.47): a bar under the header of the course says that the edit mode is on, with a button « Finish editing » that leaves it like the switch of the header. The buttons to add content and sections outlined in the colour of the brand, filled when pointed at; on touch screens, the « + » between the activities and between the sections always shown (Moodle shows them only when pointed at). The menus of the sections and the activities with a larger button and their actions easier to read, « Delete » apart and in red. « Bulk edit » a button rather than a link, larger boxes to tick and the bar of the bulk actions in the style of the theme. « Add section » and the restrictions of access readable (contrasts AA, also in dark mode). Moodle's course editor works as before. No setting.

## 0.48.0 (beta)

- File picker and file manager: the window with rounded corners; the repositories as a side menu, the open one marked like the current page of the menus (as chips above the files on phones, instead of filling the screen); the buttons of the toolbars square and quiet, the switch of the views as a segmented control; the files as tiles with rounded corners, their names in the colour of the text; the table of the details and the tree readable in dark mode (light rows and black icons of the types of files before); the area to drop files and the button « Choose file » in the style of the theme. The windows of the details of a file and of a new folder follow. Only the look changes: the file picker of Moodle works as before. No setting.
- Roadmap: 0.49 (course sheet, Qualiopi indicators 1 and 9) moves to a separate Qualiopi plugin.

## 0.46.0 (beta)

- Accessibility of the contents, for the teachers (whoever can manage the activities): when a form is saved, the contents of its editors are read for the common mistakes, images without a text alternative, tables without headers, links whose text does not say where they go (« click here »), levels of titles skipped. When some are found, a window lists them field by field, with « Fix », « Open the accessibility checker » (the one of the editor TinyMCE, in Moodle) and « Save anyway »: nothing is ever blocked. Without mistakes, the form is saved as before. No account nor registration needed (Brickfield is not used). No setting.

## 0.45.0 (beta)

- To grade (0.44): the figure « To grade » of the teachers, on the course page and in My courses, counts the quiz attempts with answers to grade by hand (essays…) besides the submissions of the assignments. With one activity to grade, it leads to its grading page (the grader of the assignment, the manual grading report of the quiz); with several, the course page lists them under its figures (« Activities to grade »), each with its count and leading to its grading page.
- Grading of the assignments (0.45): a button « Back to the course » at the top of the header, in place of the link of the course; a sober header (the learner in ink with their picture round, the assignment and its due date in grey), the arrows to the previous and next learners as round buttons, the states of the submission as chips in the colours of the states (submitted, not graded, late), the submission and the grade in a card on the colour of the page, a larger grade field, the actions in a bar at the bottom. Only the look changes: the grader of Moodle works as before. No setting.

## 0.43.0 (beta)

- Tables read as cards on phones: the participants of a course, the grades overview, the logs, the activity report (outline), the reports of the report builder (badges, custom reports…) and the users and completion reports of IOMAD. Each row a card, its first value as the title (the name of a participant, the name of a course), then each value with the name of its column; the empty values are not shown; the tables Moodle reloads in place (filters and pages of the participants) are shown as cards again. Nothing to scroll sideways any more. On larger screens, nothing changes. No setting.
- On phones, the bar of the types of report no longer goes beyond the page.

## 0.42.0 (beta)

- Simplified view of the learner, a new setting (Navigation tab), for the site and for each IOMAD company, off by default. For whoever manages no activities (in a course: there; elsewhere: in no course): the navigation of a course reduced to « Course », « Grades » and « Badges », the other pages under « More »; in an activity, its other pages under « More »; the user menu without calendar, private files and reports (the items added by the administrator stay); the drawer of the blocks closed when a page opens; the help button discreet until pointed at. Nothing is removed: every page keeps its address and stays reachable with the keyboard. The help of the setting warns the teachers that their learners no longer see everything.
- With Épure, whatever the view: « Badges » is in the navigation of a course only when the course has an active badge (Moodle shows it even empty), or for whoever can create badges there.

## 0.41.0 (beta)

- The own profile starts with a summary: the courses in progress and completed (and those taught), the badges and the certificates as figures, each a link to its page; the previous login; « Edit profile » as a button in sight, the whole width on phones (instead of a small button in the corner of a card). On every profile, the cards that matter come first (user details, achievements, courses, administration); the others (privacy, miscellaneous, reports, login activity) are grouped under « More information », folded, none removed.
- Lessons read as a text: the title of each page as a heading rather than a fold of a form; its content at a reading width of about 70 characters, which follows the size of the text chosen in the display preferences, with room between paragraphs, lists and titles, the titles of the content under that of the page; images and wide tables kept within the page; the score so far as a discreet line.
- Lighter fonts: IBM Plex Sans, Inter, Source Sans 3, Figtree and Lexend are now variable fonts, one file for all their weights instead of four, about half the weight. Lato, Atkinson Hyperlegible and OpenDyslexic, which have no variable version, are unchanged. No setting.
- 0.39 (notifications by priority) is left out: the dashboard already shows what the learner has to do.

## 0.38.0 (beta)

- Useful empty states: an icon, what is empty in a clear sentence, what will show there, and the next step. « My courses » without courses: « No courses yet », with « Browse the catalogue »; the dashboard of a learner who completed all their courses: « You have completed all your courses », with the catalogue. Moodle's blocks: Timeline with nothing to hand in (« No activities require action », the next deadlines will show here, « See the calendar ») or without courses in progress (« My courses »), Recently accessed items (« My courses »), Recently accessed and Starred courses (creating a course when the user can, else the catalogue). The empty list of the notifications under a bell. These sentences take the vocabulary.
- The vocabulary is applied again after each upgrade of Épure, so that its new strings take it too. No setting.

## 0.37.1 (beta)

- IOMAD: each company chooses its words for « course », « student » and « teacher » too, in the Vocabulary section of its form, besides « company » and « department ». By default, those of the platform (Épure: vocabulary); a word chosen by the company replaces that of the platform, already written in the strings, for its users only. No new setting.
- Upgrade and installation: the message « Moodle is working », while the page loads, is centred at the bottom of the screen, under the progress, instead of a corner where it covered the help button.

## 0.37.0 (beta)

- My achievements. On the dashboard of the learner, a card « My achievements » under « Completed »: the latest badges they earned as thumbnails with their name (public or not, they are seen by the learner only), « All my badges » or how many more, then their latest certificates (Certificate, Custom certificate, IOMAD certificates of the completions tracked) with their date; the button « My certificates » moves there. Hidden when there is nothing to show.
- On their own profile, the same gallery in a card « My achievements », in place of the list of their public badges of Moodle. The profile of another user is unchanged: their public badges only. No setting.
- Vocabulary: the strings of Épure take the words chosen too (« Search my programs »), except those that describe the vocabulary itself (« Courses are called »); applied again on upgrade where a vocabulary is used. IOMAD's « My courses » block takes the title of Moodle's « My courses », translated and in the words of the vocabulary, instead of its own string, which the language packs often leave in English; its button « Download my certificates » is translated.
- Visitors: « Log in » in the header was in the brand colour, unreadable on a header in the brand colour; it is now a button outlined in the colour of the text of the header.

## 0.36.0 (beta)

- My grades (user report of the gradebook). Above the table, the course total in a card: the grade in large, out of its range, its percentage with a bar, and how many items are graded; its values are read from the table, so that what the gradebook hides stays hidden. The comment of the teacher in a bubble marked with the brand. On phones, each grade as a card with its values named by their column (weight, grade, range, percentage, comment, contribution), the empty values left out, the grade kept (« - » until graded): no more table to scroll sideways. No setting.

## 0.35.0 (beta)

- Reviewing a quiz attempt. At the top, its result when the marks can be seen: the grade in large, « Passed » or « Not passed » when the quiz has a grade to pass, a bar, and how many questions are right, partly right, wrong and not answered; on a page with several questions, « To look at again » shows only the questions not right. The details of the attempt (status, dates, duration, marks) as tiles instead of a table.
- In each question reviewed, its state as a chip in the colour of the result (right, partly right, wrong), the feedback in the same colour, the general feedback set apart, and the right answer highlighted. No setting.

## 0.34.0 (beta)

- Taking a quiz calmly. On the summary of the attempt, a block « Before submitting » at the top of the page: the questions not answered and flagged (when the learner can go back to them), with the buttons « Return to attempt » and « Submit all and finish », as at the bottom of the page, so that a long quiz can be submitted without scrolling down. The confirmation of the submission comes up at the bottom of the screen, near the button, above the navigation bar of the phones (no animation when the device asks for less motion).
- Each question as a card: its number, state, mark and flag in a band at the top instead of the grey column on the left, the text a little larger, the choices (multiple choice, true/false) as rows easy to hit, chosen by a click anywhere on the row and in the brand colour once chosen; in the review, the right and wrong answers in the colours of the states.
- The panel of the questions explains its marks: answered, not answered, this page, flagged.
- The timer stays in sight while scrolling the questions, as a calm chip with figures that do not jump; its last seconds, which Moodle paints red, take the colour of a deadline coming. On the summary, it is in the middle, under the button to go back. No setting.

## 0.33.1 (beta)

- IOMAD, a user of several companies: « My courses » and the dashboard showed the courses of all their companies, whatever the company they were logged in, and a course of another company opened in the company of the session (its colours, its activity counted for it). By default, as IOMAD's « My courses », only the courses of the company the user is logged in are shown. New setting « All the companies in My courses (IOMAD) » (Courses tab), off by default: a learner sees all their courses, those of their other companies with the name of the company, and a course opens in its company, the one they were enrolled in it through, as if they had logged in there. Not for the managers and the administrators, who choose their company in the IOMAD dashboard.

## 0.33.0 (beta)

- « This week » on the dashboard of the learner, in place of « Coming up »: from today to the same day next week, in all the courses they take, grouped by day (Today, Tomorrow, then the name of the day). The deadlines of the activities, with their state (due soon, in progress, done or passed once finished) and the name the calendar gives them (« Report is due »); the sessions and other events of the calendar of the courses, of the groups of the learner and their own, with their time. Above, the activities overdue still to do, the most recent first, with their date. When nothing is planned: « Nothing planned this week », with the link to the calendar. No setting.
- Moodle 5.0: the label of the state « Locked » of an activity was missing (a string of Moodle that 5.0 does not have); the theme now has its own.

## 0.32.0 (beta)

- Detail of the progress of a course, under its bar in the banner of the course and on the dashboard: « 3 completed · 1 in progress · 1 overdue · 1 not passed ».
- On phones, the button « Continue » of the course stays at the bottom of the screen, above the navigation bar, with the name of the activity, once the banner is scrolled away; the end of the page and the help buttons go above it.
- A site administrator enrolled in a course as a learner now sees it as a learner (« My courses », dashboard, course page): Moodle gives the administrators every capability everywhere, which made them teachers of all their courses. The roles in the course decide; a course they are not enrolled in as a learner is still shown to them as to a teacher, unless they switched to the role of a learner there (« Switch role to… »).

## 0.31.0 (beta)

- A smarter « Continue », the same on the dashboard, the banner of the course and its page: an activity due in the next two days comes first (the closest deadline); else the activity started and not finished; else the one after the last activity completed, rather than the first one left behind; else the first one still to do. Under it, what is left: « 4 activities left · due tomorrow » (due today, tomorrow, in a few days, or on a date).
- Deadlines in several courses: the dashboard resumes the course whose activity to continue is due the soonest, in the next two days, rather than the last one visited; the other courses tell their deadline, due soon or late, beside their name in the list of the courses in progress.
- « Start » only for a course never opened, where nothing was done yet: a course visited, or with an activity started, completed or failed, says « Continue », even at 0 %. The block of the dashboard says « Next deadline » rather than « Pick up where you left off » when it brings up a course for its deadline rather than for the last visit.

## 0.30.0 (beta)

- Progress of the sections: in the title of each section on the course page, a bar beside « 2/4 »; in the course index, a thin bar under the name of each section, green once all is done.
- The activity to continue, the first one still to do in the order of the course page (the « Next » of the banner), is highlighted on the course page (« Continue here », tinted with a band of the brand colour) and in the course index; its section, and its subsection, say « You are here ». When the learner marks an activity as done on the page, the highlight moves to the next one. On phones, the progress and « You are here » go under the name of the section.

## 0.29.0 (beta)

- States of the activities for the learner, the same everywhere: Moodle told « To do » or « Done », and a failed test showed « To do ». Each activity now has one state, with its label, icon and colour: overdue, due soon (in the two days before its deadline), in progress (a condition met, not all), not passed, passed, done, to do, locked, optional. On the course page, the states Moodle does not tell show under the name of the activity, with the date when it matters (« Overdue · 13 October »); the course index shows the icon of each state, in its colour; the next activity of a course, on the dashboard, « My courses » and the banner of the course, carries its state when it is late, due soon, started or not passed. The deadline is the closing date of the activity (due date, closing of a quiz…), else the date its completion is expected. A completion marked on the course page changes the state at once. Colours AA in light and dark mode. No setting.
- Dark mode: the activities not available yet in the course index were in a grey below the AA contrast.

## 0.28.12 (beta)

- IOMAD, a company whose address is that of the whole site (for a link with another software, for example): IOMAD gave its theme to every page, for the users of all the companies, and an administrator who chose another company in the IOMAD dashboard got it back on the next page (« My courses »...). The users of the other companies, and an administrator working on another company, now get the theme of their company; the users of the company of the address keep its theme, and IOMAD keeps using the address as before (company of new accounts, links).

## 0.28.11 (beta)

- Brand colour setting: the two squares next to the colour gradient kept showing the previous colour when a colour of the logo was chosen, a point of the logo picked, or a code typed in the field (Moodle only updates them on a click in the gradient). They now follow the field.
- Colours of the logo, when the settings page is shown under another theme (an IOMAD company with a theme of its own, for example): the colours were missing next to their codes, and the logo was shown full size. The swatches and the logo now carry their own sizes.
- « My courses » and the dashboard: the courses were read without their revision, which keys the progress kept in the caches. Since Moodle 5.2.4 and 5.3.1, which keep the progress of a course for the request, a progress read before a new activity was added in the same request could be shown again; the revision is now read with the courses.

## 0.28.10 (beta)

- IOMAD companies with a colour of their own: a small frame in the colour of the site stayed around a tab once clicked (the focus ring of Bootstrap, written at build time). The focus rings of the tabs, fields, buttons, pagination and badges, and the outlines of the edit mode, now follow the colours of the page, those of the company when it has its own. On phones, the active tab no longer gets Moodle's blue ring.

## 0.28.9 (beta)

- Display preferences panel (« Aa ») on phones in landscape and other short screens: the header, the navigation bar at the bottom, the title and the reset button left almost no room for the choices, which could hardly be reached. On screens under 500 pixels high the panel now scrolls as a whole, above the navigation bar.

## 0.28.8 (beta)

- IOMAD on phones and small tablets: with several companies, the company menu of the header pushed the user menu (preferences, logout) and the edit mode switch out of the screen. The header has no room for it there: it is left out below 768 pixels, and the company is changed from the IOMAD dashboard, which has its own selector.
- Notifications of the header (bell), in the colours of the theme: the unread ones were on a light grey background also in dark mode, under light text that could not be read; they are now on the soft brand colour, in light and dark mode. Under the pointer, a light background instead of white text on the brand colour.
- Dark mode: the Font Awesome 6 icons (fa-solid, fa-regular) were inverted like image icons and turned dark on the dark surface (the icons of the notifications, among others). They keep the colour of the text.
- Calendar: the eye of the filters of the event types, on their pastel labels, in dark grey instead of Moodle's blue, also in the block and in dark mode.

## 0.28.7 (beta)

- Dark mode, Moodle 4.5: the help bubbles of the forms (the « ? » buttons) and the other popovers showed dark text on a dark background, with a white arrow. Their text, title and arrow now follow the dark colours, on Moodle 4.5 and 5.x.

## 0.28.6 (beta)

- Error pages: instead of Moodle's red box, a card in the colours of the brand says in plain words what happened (page not found, access denied, content not available, session expired, other error), keeps Moodle's message and leads to the dashboard, or to the login page. Moodle's « page not found » page gets the same card.
- User tours: their steps in the colours of the theme, in light and dark mode, with the element shown outlined in the brand colour.
- Printed pages and their PDF: the logo and name of the site, or of the IOMAD company, over a line of the brand colour, then the title of the page (which Boost leaves out) and its content, without the menus, panels and buttons of the screen. Dark mode is now for screens only: printed pages stay light.

## 0.28.5 (beta)

- Corner style: the choices are named for what they do, « Slightly rounded (almost square corners) », « Moderately rounded » and « Very rounded », instead of « Sharp », « Soft » and « Round » (« Net », « Doux », « Arrondi » in French, where « Net » could be read as clearly rounded). A help text gives the size of the corners, in the settings of the site and in the form of the IOMAD companies.
- IOMAD: a company can use a font of its own, uploaded in its form (name, normal and bold files, woff2 or woff), or the font uploaded for the site, besides the 8 bundled fonts. Without a normal file, the font of the site is kept.

## 0.28.4 (beta)

- IOMAD: a company can set in its form (Edit company › Appearance) more of the settings of the site, each « As the site » by default:
  - identity: accent colour (with a colour picker) and corner style; on Moodle 4.5 the corners compiled into Bootstrap 4 keep those of the site;
  - e-mails in the colours of the brand, by the company of the recipient;
  - navigation: breadcrumb, quick search, activity pages, blocks and dashboard on phones;
  - pages: course catalogue, progress of the sections, My courses by role;
  - login page: layout, headline and supporting text.
- IOMAD login page of a company (its own address or its link login/index.php?id=…&code=…): it now takes the colours, logo, font, name and footer of the company, as its pages do once logged in, instead of those of the site.

## 0.28.3 (beta)

- Display preferences panel (« Aa ») on phones and small tablets: aligned on its button, which is not at the right of the screen there, it went out of the screen on the left. It now opens under the header with the same margin on both sides, and stops above the navigation bar at the bottom of the screen. Unchanged on computers.

## 0.28.2 (beta)

- Edit mode switch on phones: Moodle hides its label for lack of room, which left a switch without a word. A pencil, Moodle's icon to edit, now stands before it, in the brand colour once the edit mode is on; tapping it toggles the switch, and screen readers still read « Edit mode ». The header keeps fitting 360 pixels.
- Quick search button of the header (« Search Ctrl K »): it took the whole height of the header in a grey box that made it look disabled. It is now a search field at the height of the other controls of the header, on the surface with a light border, in light and dark mode and in the brand-coloured header.

## 0.28.1 (beta)

- Activity chooser, in the colours of the theme, in light and dark mode, on Moodle 4.5 (tabs and cards) and 5.x (categories and list):
  - the names of the activities are in the colour of the text, instead of a grey that could not be read in dark mode;
  - the active tab or category takes the brand colour instead of Moodle's blue, and the others are no longer white in dark mode;
  - the activity under the pointer or reached with the keyboard is outlined with the brand, the chosen one tinted with it;
  - the details of an activity and the footer of the window are no longer white in dark mode.
- Tabs on phones: Moodle turns them into buttons on a grey strip, the active one in its blue and the others white, also in dark mode. They take the colours of the theme, the active one the brand colour.
- Dark mode: the links of the dialogue windows (« More help »…) are readable, and the close button of the windows of Moodle 4.5 no longer disappears.
- Activity chooser of Moodle 4.5: the icon of the details of an activity follows the « Activity icons » setting, in the brand colour when it is chosen, light in dark mode otherwise.
- H5P icon: it follows the « Activity icons » setting as the other activities, instead of staying blue (Moodle leaves the logos of brands in their colours), in the activity chooser, on the course page and on the page of the activity. With the brand colour it takes it; with Moodle's colours it takes the one of the interactive contents, as the lessons and the IMS packages.
- Text editor (TinyMCE): the active buttons and menus, the chosen items, the frame of the text and the focus take the brand colour instead of TinyMCE's blue. The editor stays white in dark mode, so it takes the light version of the brand colour, of the company with IOMAD.
- Checked: on Moodle 4.5 and 5.1, about sixty pages (course, activities, calendar, messaging, profile, grades, reports, settings, forms, menus and drawers open), on computer and phone, light and dark, under the pointer and with the keyboard, no longer show Moodle's blue. The information messages and the colour of the user events of the calendar keep their own colours.

## 0.28.0 (beta)

- Messaging, on its page and in its drawer:
  - the messages are bubbles, mine in a tint of the brand on the right, the others' neutral on the left;
  - the days are small titles between two lines;
  - the conversation under the pointer or reached with the keyboard takes a tint of the brand instead of the brand colour;
  - the field to write is rounded and the send button takes the brand colour.
  On phones, the list of the conversations and the conversation open are shown one under the other, the conversation first, instead of two narrow columns side by side.
- Choice: each answer is a card that can be clicked as a whole, the chosen one marked with the brand, as the answers of the quizzes and lessons. The chart of the results keeps a readable size.
- Charts: the charts of the pages of Épure (results of a choice and others) are drawn in shades of the brand colour, of the user's company with IOMAD, readable on the light and the dark surfaces, instead of Moodle's yellow and purple. A site that sets its own colours for the charts (`$CFG->chart_colorset`) keeps them.
- Profile and preferences: the person is shown in a header on a tint of the brand. The links of the cards become the rows of a menu, with the whole row as the target and an arrow on the right, and « Edit profile » is a small button in the title of its card.
- Database: the entries are compact cards, with the names of the fields as small grey titles and the empty tags hidden. The search and sort options are grouped in a light box, and on phones the author and the dates of an entry stay readable.
- Wiki: the table of contents is a light box marked with the brand, the text keeps a readable width and the « edit » links of the sections are discreet.
- Feedback: each question is a numbered card, and the answers of a multiple choice are rows that can be clicked as a whole, the chosen one marked with the brand.
- Workshop in dark mode: the icons of the tasks (to do, done, failed, information) have light versions, readable on the dark surfaces.
- Accessibility (axe-core, WCAG 2.2 AA, light and dark): the « Clear all » link of the database search is underlined.

## 0.27.1 (beta)

- H5P: the older versions of the course presentation (before 1.26) and of the audio player (before 1.5.24), still installed on many sites, wrote their blue in their own style sheets and did not use the H5P theme colours. The progress bar of the slides, the buttons that open an element (image, text…) and the audio buttons now take the brand colour, of the company with IOMAD.

## 0.27.0 (beta)

- Corners: each level of the course page is a step less rounded than the one holding it. Sections have the large radius, their activities and subsections the medium one, and the activities of a subsection the small one. Chips, counters, badges and the search field follow the corner style too: pills with « Soft » and « Round », rounded rectangles with « Sharp » (they kept fixed corners before).
- Calendar: the events of the month are labels in a tint of the colour of their type (site, course, category, group, user) over two lines, instead of a name cut after a few letters behind a small circle. The day numbers keep the colour of the text in dark mode, and the weekends are tinted. On phones, the names of the previous and next months stay on one line and the dots under the days take the brand colour. The cards of the day and upcoming events are marked with the colour of their type, with a smaller title and the details next to their icons.
- Settings forms, lighter: the help question marks are grey instead of Moodle's teal and take the brand colour under the pointer. Section titles are smaller and their fold button has no grey square. The five lists of a date are narrower. The items chosen in an autocomplete field are labels tinted with the brand, outlined only while the list has the focus.
- Workshop: the phases in the colours of the theme, the current one in a tint of the brand instead of lime green, with task links large enough to tap. Feedback: the titles of the overview are smaller and set apart. Messaging page: a single rounded frame instead of a grey box inside the card. Profile: the titles of the cards are in bold.
- Dark mode: the user report and the gradebook setup no longer show white rows and headers. The light badges and the plugin counters are readable.
- Accessibility (axe-core, WCAG 2.2 AA, light and dark): the class names of the scheduled tasks have enough contrast. So does the highlighted cell of the quiz results, now in a tint of the brand instead of light blue. The check box next to a field of the same group (« Enable » of the word limit) is a target of 24 pixels.

## 0.26.0 (beta)

- Theme settings grouped by subject: **Identity** (logos, colours, e-mails, vocabulary), **Navigation** (main bar, quick search, breadcrumb, user menu), **Courses** (cards, progress, activities), **Accessibility** (display preferences, accessibility statement), **Appearance** (typography, shapes and density, dark mode), **Mobile** (phones, applications), then the login page, footer and advanced tabs. The names of the settings do not change: the values already saved are kept, there is nothing to migrate. The main bar and the user menu link to Moodle's custom menu items and user menu items.
- New settings: a logo for phones, shown in the phone header where Moodle shows no logo; a favicon; an accent colour for the progress bars and the completed sections (empty: the brand colour; an IOMAD company with its own colour keeps it everywhere); the breadcrumb (shown, on large screens only, or hidden); the progress of the sections, which was part of the course banner and now also shows without it; the density (comfortable or compact); the blocks on phones (in their drawer or hidden) and the dashboard on phones (overview and blocks, or overview only).
- Accessibility: the button « Aa » can be turned off, and the display by default of the site can be set for visitors and for users who did not choose theirs: text size, reading font, more text spacing, enhanced contrast, underlined links, reduced motion.
- Installable web app (Mobile tab, off by default): the platform can be added to the home screen of phones and computers from the browser, with its name, short name, brand colour (of the user's company with IOMAD) and icon (uploaded, or the initial of the site on the brand colour). A service worker only keeps a page « You are offline », shown when a page cannot load; nothing else is cached, Moodle's pages always come from the network. Turned off, or when the user no longer sees Épure, the service worker removes itself and its cache.
- Subsections (Moodle 4.5 and later): the activities of a subsection come at the place of the subsection in the course order, for the next activity (banner, My courses, dashboard), the position « Activity n of N » and the previous and next activities, which show « Section › Subsection ». A section counts the activities of its subsections in its progress, and each subsection gets its own progress; the outline of the course introduction counts them in their section. On the course page, subsections are set apart by a tinted background and a band of the brand colour, in light and dark mode.
- Performance: the progress, next activity and next deadline of the courses of a learner, and their next deadlines, are kept 5 minutes in a cache, forgotten at once when an activity or a course is completed, an assignment submitted or a quiz attempt submitted, and when the activities or the completion of the course change. On Moodle 4.5 with 3 courses, the learner overview goes from about 60–80 ms to about 5 ms once cached.
- IOMAD 5.1 moved the completion tracking from local_iomad_track into local_iomad: the frames of the certificates of the companies that left Épure are now also given back on the pages of local/iomad, local/report_users, admin/tool/redocerts and the « My courses » blocks, and in command-line scripts (scheduled tasks).
- Tests: Behat now also runs on IOMAD 4.5, 5.1 and 5.2, without two IOMAD Behat contexts that stop Behat (auth_iomadsaml2's, incompatible on IOMAD 4.5, and tool_iomadpolicy's, which defines again a step of tool_policy). A scenario checks that a learner of a company sees the colour and logo of their company, that a user without company keeps the site colour, and that IOMAD's « My courses » is Épure's page by role, with axe-core. The IOMAD scenarios (tag @theme_epure_iomad) run on IOMAD, the others on Moodle.

## 0.25.0 (beta)

- IOMAD 5.1 and 5.2: IOMAD 5.1 renamed its tables and classes, and Épure did not see IOMAD on it at all. Everything Épure uses of IOMAD now goes through one class, which knows IOMAD before and after 5.1; IOMAD's « My courses » block of 5.1 (block_iomad_mycourses) gets the page by role too. Verified on IOMAD 4.5 and 5.1; the continuous integration tests IOMAD 4.5, 5.1 and 5.2.
- Moodle 5.3: the button of the side drawer of the blocks was drawn over the content, on the left (Moodle 5.3 places the drawers from the width of the content, which Épure set to « none »). The drawers and their buttons are against the edges of the screen again.
- Format « Single activity »: no strip nor previous and next activities, as the activity is the course (« Back to the course » led to the same page).
- With IOMAD, today in the calendars and the chosen company of the selector take the colour of the company.
- Unexpected errors (theme of a company, loading of a theme) are reported in developer debugging mode instead of being silent.
- Code: the styles are split by subject in scss/epure/ (the compiled style sheet is the same).
- Tests: Behat scenarios of the course formats Topics, Weeks with a subsection, Single activity and Social, with axe-core. Performance measured against Boost (wiki, Développement).

## 0.24.0 (beta)

- Activity icons can be hidden, for the site and, with IOMAD, for each company: no icons on the course page, the dashboard blocks, the calendar and the activity pages; the activity chooser of the teachers keeps them.
- Moodle app in the colours of the brand: a setting (Pages and navigation) gives the app the style sheet of the theme, with the brand colour, the header and the font, in light and dark mode; with IOMAD, those of the company of the user. The theme sets Moodle's « CSS » of the mobile appearance and gives it a new address at each change of the appearance, for the apps to download it again.
- Forums (posts as cards, the thread shown by a line), glossaries (index as buttons, entries as cards), assignments (status as a card), SCORM (description and information as boxes, table of contents of the player in the brand colour), books and pages (current chapter, chapter arrows and quotations in the brand colour, text at a readable length).
- The sections and activities of the course page, and the squares of the activity icons, follow the corner style of the theme (Moodle gave them fixed corners).
- IOMAD certificates: in the company form, a frame in the brand colour of the company can be used for its certificates. When the company (or the site) leaves Épure, the frame it had before and its « Use border » setting come back.
- Badges: the badges of the user as cards, the page of a badge with its image on a tint of the brand.
- Reports: the row under the pointer highlighted in the grader report, the completion report and the IOMAD reports; IOMAD's tree of departments as a box of the theme.
- With IOMAD, the pages and initials of the tables, the pills, the active items of the lists and the buttons that expand the sections of the forms take the colour of the company (they kept the colour of the site).
- Accessibility: the links among text of the completion report and of the badges page are underlined, and the downloads of the completion report are large enough targets.

## 0.23.0 (beta)

- Colours of the activity icons: in the brand colour (the default) or in Moodle's colours by kind of activity, for the site and, with IOMAD, for each company.
- H5P in the colours of the brand (of the company with IOMAD): buttons, chosen answers, score and progress bars, for the recent content types (H5P theme variables) and the older ones; the green and red of the answers are kept.
- Quizzes: question on a light tint of the brand, feedback in a neutral box, clearer quiz navigation. Lessons: answers as cards one under the other, progress bar in the brand colour. Radio buttons and check boxes in the brand colour. The summary of a quiz attempt is readable in dark mode, and the progress bar of the lessons has a name for screen readers.

## 0.22.4 (beta)

- Header: on computers too, the quick search replaces Moodle's search button (two magnifying glasses side by side). When Moodle's global search is enabled, the results of the quick search end with « Search … in the whole site », which opens it.

## 0.22.3 (beta)

- IOMAD: the pages take the theme of the company being worked on, for administrators too. An administrator who selects a company in IOMAD sees the pages as its users see them, in its theme (from the page where the company is chosen); without a company, the theme of the site. A theme that IOMAD sets from the address of a company is kept.

## 0.22.2 (beta)

- IOMAD: a company can choose Épure whatever the theme of the site. When the site uses another theme (Iomad, for example), the company form now shows the Épure settings as soon as Épure is chosen for the company, with their own styles.
- A change of theme of an IOMAD company applies at once to its users already logged in: IOMAD writes the new theme on them, but Moodle kept the former one in their session until they logged in again, so they kept seeing Épure (dashboards, course pages…).
- Phones: the quick search button is a round icon like the others, and replaces Moodle's search in the header (two magnifying glasses side by side); a long company name is cut instead of pushing the user menu out.
- Mobile navigation bar: labels on up to two lines instead of being cut (« Tableau de bord », « Mes formations »), and « Messages » as a short label.
- Another theme stays as it is without Épure. In the IOMAD company form, choosing another theme for the company shows IOMAD's form exactly as IOMAD shows it (order, fields, note), without any Épure field; choosing Épure again restores the steps. The Épure settings of the company are kept for a return to Épure.
- The words of the IOMAD companies (vocabulary) only apply to the users who see the pages with Épure: with another theme (for the site, the company or the session), the words of the language pack are kept.
- The e-mails in the colours of the brand are not sent to the users of a company that uses another theme: they get the e-mails of Moodle.

## 0.22.0 (beta)

- Theme settings in a clearer order: **Brand identity** (the logos first, then the brand colour, which proposes their colours, and the header colour), **Typography and display** (font, corners, dark mode), **Pages and navigation** (what the theme adds to Moodle's pages, grouped by Navigation, Pages, Vocabulary, E-mails), then the login page, footer, accessibility and advanced tabs. The values already saved are kept. A logo just uploaded proposes its colours before it is saved.
- « Moodle is working » indicator also on the pages of Moodle's maintenance layout: validation of a plugin ZIP file, plugins check and upgrade. While the upgrade runs, a message stays in a corner of the page until it is complete.
- IOMAD company form: the Appearance part is arranged in numbered steps, organised as the tabs of the theme settings (theme, brand identity with the logos then the colours, typography and display, pages and navigation, footer, vocabulary, advanced settings), each in its own card. IOMAD's own fields (theme, logos, favicon, custom CSS and menu) are moved into these steps: the logos now come before the colours, which are proposed from the logo.
- Supports Moodle 5.2 and 5.3 (PHP 8.3 and 8.4). Continuous integration on Moodle 4.5 to 5.3 with PostgreSQL (17 for Moodle 5.3), and on Moodle 4.5 and 5.3 with MariaDB 11.4. No change in the theme code was needed.

## 0.21.0 (beta)

- Accessibility pre-audit (axe-core, WCAG 2.2 A/AA) on 240 pages of Moodle 4.5, Moodle 5.1 and IOMAD 4.5, by role, in light and dark mode, on desktop and phone; every issue found is fixed. Report (in French): [wiki](https://github.com/ericfalcon/moodle-theme-iomad/wiki/Pré-audit-d’accessibilité).
- Keyboard focus outline of the theme takes precedence over the pale rings of Bootstrap and Moodle (buttons, menus, fields); white in the brand-coloured header.
- Targets of at least 24 × 24 px for the sorting and hiding of table columns, the initials of the lists of users, the links of the administration search, the action menus and the help icons of table headers.
- Dark mode for the grader report, the grading table of assignments and the "More" menu of the course tabs.
- Theme settings tabs: valid structure for assistive technologies.
- Messages page: no longer hidden from screen readers (a Moodle issue), and named region.
- Quick search button: AA contrast in a brand-coloured company header.
- English documentation (theme README) and French documentation (GitHub wiki).
- Maturity: beta.

## 0.20.0

- E-mails in the colours of the brand: logo, brand band, footer and a link to the notification preferences; per recipient's company with IOMAD.

## 0.19.0

- Quick search (Ctrl+K / ⌘K): menu pages, my courses, activities of my courses, catalogue, administration pages.

## 0.18.0

- Mobile navigation bar: dashboard, my courses, catalogue, messages (unread count), profile; per company with IOMAD.
- IOMAD: category column on the "Manage IOMAD course settings" page.

## 0.17.0

- Course catalogue: subcategories as chips, courses as cards, search results as cards; course introduction on the enrolment page.

## 0.16.0

- Activity pages: previous and next activities as cards, strip with course, position and progress, reading mode, more visible "Mark as done".

## 0.15.0

- "Moodle is working…" indicator during installation and upgrade steps.

## 0.14.x

- Footer on every page, per company with IOMAD; key figures of the site; dark mode fixes.

## 0.13.0

- IOMAD: key figures of the company on the IOMAD dashboard.

## 0.12.0

- Dark mode: never, automatic or always, for the site, per IOMAD company and per user.

## 0.11.0

- Learner overview on the dashboard: course to resume, courses in progress, deadlines, completed courses and certificates.

## 0.10.0

- Course page: banner with image, progress, next activity, deadline and "Continue" button; figures and direct links for teachers; progress of each section.

## 0.9.0

- Accessibility: display preferences (Aa button), skip link, focus, targets, accessibility statement (RGAA), axe-core audit in continuous integration.
- IOMAD: direct access to the course categories of the company.

## 0.7.0

- My courses by role: "I teach" and "I take" sections with suited cards.

## 0.6.0

- IOMAD: appearance and vocabulary of each company in its form.

## 0.5.0

- IOMAD dashboard layout: company header, tabs, actions grouped by intent.

## 0.4.0

- Vocabulary per IOMAD company (company, department).

## 0.3.0

- Platform vocabulary (course, student, teacher), applied to the whole of Moodle.

## 0.2.x

- Logo colours in the settings, modern login page, colour and logo per IOMAD company.

## 0.1.0

- First version: Boost child theme, accessible palette computed from the brand colour, bundled fonts.
