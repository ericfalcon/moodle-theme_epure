# Contributing to Épure

Thank you for helping. Épure is a theme for Moodle 4.5 to 5.3 and IOMAD 4.5 to 5.2, free and open source (GNU GPL v3 or later). Issues and pull requests are welcome, in English or in French. The documentation in French is in the [wiki](https://github.com/ericfalcon/moodle-theme_epure/wiki) (source: `docs/`); the code, its comments and the commit messages are in English.

## Report a problem

Open an [issue](https://github.com/ericfalcon/moodle-theme_epure/issues) with:

- the versions of Épure, Moodle (or IOMAD), PHP and the database;
- the page, the role of the user and the display (light or dark, « Aa » preferences, phone or computer);
- what you expected and what you saw; for an accessibility problem, the criterion (RGAA, WCAG) when you know it.

A security problem is not reported in an issue: write to the maintainer through the contact on the Moodle Marketplace page of Épure.

## Propose a change

1. Fork the repository and create a branch from `main`.
2. Install it in a Moodle 4.5 or later site as `theme/epure` (the plugin is at the root of the repository).
3. Make the change, with its tests, its strings in English **and** French (`lang/en`, `lang/fr`), and its documentation (`README.md`, `README.fr.md`, the page of the wiki in `docs/`, and a line in `CHANGES.md`).
4. Run the checks below, then open a pull request that says what changes for the users and how you tested it.

The continuous integration runs every pull request on Moodle 4.5 to 5.3, IOMAD 4.5 to 5.2 and the development version of Moodle, with PostgreSQL and MariaDB: PHPUnit, Behat with the accessibility checks (axe-core) in every display, and the code checks of Moodle.

## Checks to run

From the root of a Moodle site where Épure is installed:

```
# Code style (moodle-cs) of the files you changed
vendor/bin/phpcs --standard=moodle theme/epure/<files>

# JavaScript: build, then lint
npx grunt amd --root=theme/epure
npx grunt stylelint --root=theme/epure

# Tests
vendor/bin/phpunit --testsuite theme_epure_testsuite
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @theme_epure
```

Commit the built files of `amd/build/` with the sources of `amd/src/`.

## Principles

- **Accessibility first.** Every change keeps the pages at RGAA 4.1.2 / WCAG 2.2 AA in every display (light, dark, yellow on black, cream, text at 200 %, phones from 320 px). New pages and components are checked with axe and with the keyboard.
- **Few settings.** Épure computes what it can (the palette from one colour, the contrasts). A new setting needs a good reason; a choice of an IOMAD company or of an entity reuses the settings of the site.
- **Moodle stays Moodle.** Épure is a child of Boost and changes no file of Moodle. Where Moodle has a defect, Épure repairs it in its templates or its styles when Moodle lets a theme replace them, else in the page; and the defect is reported to Moodle (see [Réparations de Moodle](docs/R%C3%A9parations-de-Moodle.md)). A repair still « to report » is a good first contribution: a ticket, with a patch for the development version of Moodle.
- **No outside service, no tracker.** Épure sends nothing out of the site.
- **IOMAD and the entities.** What depends on the company of a user goes through `theme_epure\iomad` and `theme_epure\company_style`; what depends on the entity of a category or of a cohort, through `theme_epure\entity`.

## Where things are

- `classes/`: the code, one class per subject; `classes/output/core_renderer.php` for the pages.
- `templates/`: the Mustache templates; `amd/src/`: the JavaScript modules; `scss/epure/`: the styles, one file per subject.
- `tests/`: PHPUnit; `tests/behat/`: Behat, with the pre-audit of accessibility (`preaudit.feature`).
- `docs/`: the wiki, published from `main`; `docs/marketplace/`: the description of the Moodle Marketplace.

More details: the page [Développement](docs/D%C3%A9veloppement.md) of the wiki.

## Licence

By contributing, you agree that your contribution is published under the GNU GPL v3 or later, as Épure.
