@theme @theme_epure @javascript @accessibility
Feature: Accessibility of the Épure theme
  In order to use the platform whatever my needs
  As a user
  I need accessible pages, display preferences kept in my profile and an accessibility statement

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the users belong to an IOMAD company when IOMAD is installed
    And the following config values are set as admin:
      | theme | epure |

  Scenario: The dashboard meets accessibility standards
    Given I log in as "learner1"
    When I am on homepage
    Then the page should meet accessibility standards

  Scenario: Display preferences are applied and kept in the profile
    Given I log in as "learner1"
    And I am on homepage
    When I click on "Display preferences" "button"
    Then the page should meet accessibility standards
    And I click on "Very large" "text" in the "#epure-a11y-panel" "css_element"
    And I click on "Underline links" "checkbox"
    And the "class" attribute of "html" "css_element" should contain "epure-a11y-text-130"
    And I wait until "Saved in your profile." "text" exists
    And I reload the page
    And the "class" attribute of "html" "css_element" should contain "epure-a11y-text-130"
    And the "class" attribute of "html" "css_element" should contain "epure-a11y-underline"

  Scenario: The animated images stop and the scroll bars stay shown, from the display preferences
    Given the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following "activities" exist:
      | activity | course | name  | content                                                                               |
      | page     | SAFE   | Exits | <p><img src="/theme/epure/tests/fixtures/anim.gif" alt="Exits of the building"></p> |
    And I log in as "learner1"
    And I am on the "Exits" "page activity" page
    And ".epure-still-play" "css_element" should not exist
    When I click on "Display preferences" "button"
    And I click on "Stop the animated images" "checkbox"
    And I click on "Always show the scroll bars" "checkbox"
    Then "Play the animation: Exits of the building" "button" should exist
    And the "class" attribute of "html" "css_element" should contain "epure-a11y-scrollbars"
    And I wait until "Saved in your profile." "text" exists
    And I reload the page
    And the "aria-pressed" attribute of "Play the animation: Exits of the building" "button" should contain "false"
    And I click on "Play the animation: Exits of the building" "button"
    And the "aria-pressed" attribute of "Play the animation: Exits of the building" "button" should contain "true"
    And the page should meet accessibility standards

  Scenario: The accessibility statement is published with a mention on every page
    Given the following config values are set as admin:
      | a11ystatus | partial               | theme_epure |
      | a11yentity | Clinique des Tilleuls | theme_epure |
    And I log in as "learner1"
    When I click on "Accessibility: partially compliant" "link"
    Then I should see "Accessibility statement" in the "region-main" "region"
    And I should see "Clinique des Tilleuls is committed"
    And the page should meet accessibility standards

  Scenario: The course page shows the progress of the learner and meets accessibility standards
    Given the following config values are set as admin:
      | enablecompletion | 1 |
    And the following "courses" exist:
      | fullname          | shortname | enablecompletion |
      | Safety at work    | SAFE      | 1                |
    And the following "activities" exist:
      | activity | course | name    | completion |
      | page     | SAFE   | Welcome | 1          |
      | page     | SAFE   | Risks   | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    When I am on the "Safety at work" "course" page logged in as "learner1"
    Then I should see "Next: Welcome"
    And I should see "0/2" in the "General" "section"
    And the page should meet accessibility standards
    # Marked on the page, the progress of the section and of the banner follows without reloading.
    And I press "Mark as done"
    And I should see "1/2" in the "General" "section"
    And I should see "50 %" in the ".epure-course-banner-progress" "css_element"

  Scenario: The dashboard shows the overview of the learner and meets accessibility standards
    Given the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "activities" exist:
      | activity | course | name    | completion |
      | page     | SAFE   | Welcome | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    When I log in as "learner1"
    And I follow "Dashboard"
    Then I should see "Pick up where you left off"
    And I should see "Safety at work" in the ".epure-learner-resume" "css_element"
    And the page should meet accessibility standards

  # From Moodle 6.0, Moodle's own bar of the previous and next activities (« linear navigation » of the format) takes
  # the place of the cards of Épure: turned off here, for the cards to be tested on every version.
  Scenario: The pages of the activities lead to the next one, with a reading mode
    Given the following config values are set as admin:
      | enablelinearnav | 0 | format_topics |
    And the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "activities" exist:
      | activity | course | name    | idnumber |
      | page     | SAFE   | Welcome | welcome  |
      | page     | SAFE   | Risks   | risks    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    When I am on the "Welcome" "page activity" page logged in as "learner1"
    Then I should see "Activity 1 of 2"
    And the page should meet accessibility standards
    And I click on "Risks" "link" in the ".epure-activity-nav" "css_element"
    And I should see "Activity 2 of 2"
    And I should see "Back to the course" in the ".epure-activity-nav" "css_element"
    And I click on "Reading mode" "button"
    And the "class" attribute of "html" "css_element" should contain "epure-focus"
    And the page should meet accessibility standards

  Scenario: « Mark as done » is in the cards of the previous and next activities, once
    Given the following config values are set as admin:
      | enablecompletion | 1 |
    And the following config values are set as admin:
      | enablelinearnav | 0 | format_topics |
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "activities" exist:
      | activity | course | name    | idnumber | completion |
      | page     | SAFE   | Welcome | welcome  | 1          |
      | page     | SAFE   | Risks   | risks    | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    When I am on the "Welcome" "page activity" page logged in as "learner1"
    Then "Mark as done" "button" should exist in the ".epure-activity-nav-completion" "css_element"
    And ".activity-header [data-action='toggle-manual-completion']" "css_element" should not be visible
    And the page should meet accessibility standards
    And I click on "Mark as done" "button" in the ".epure-activity-nav-completion" "css_element"
    And I wait until the page is ready
    And "Done" "button" should exist in the ".epure-activity-nav-completion" "css_element"
    And I reload the page
    And "Done" "button" should exist in the ".epure-activity-nav-completion" "css_element"
    And the following pages should meet accessibility standards in every display:
      | path                               |
      | /mod/page/view.php?id={cm welcome} |

  # Moodle 6.0: its bar at the bottom of the page, with « Mark as done », instead of the cards of Épure; on a phone,
  # the navigation bar of Épure under it.
  Scenario: With Moodle's bar of the previous and next activities, the cards of Épure give way to it
    Given Moodle has its own bar of the previous and next activities
    And the following config values are set as admin:
      | enablecompletion | 1 |
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "activities" exist:
      | activity | course | name    | idnumber | completion |
      | page     | SAFE   | Welcome | welcome  | 1          |
      | page     | SAFE   | Risks   | risks    | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And I change viewport size to "390x900"
    When I am on the "Welcome" "page activity" page logged in as "learner1"
    Then ".course-linear-navigation" "css_element" should be visible
    And ".epure-activity-nav" "css_element" should not exist
    And ".epure-mobilenav" "css_element" should be visible
    And the following pages should meet accessibility standards in every display:
      | path                               |
      | /mod/page/view.php?id={cm welcome} |

  Scenario: The catalogue shows the courses as cards and presents a course before enrolment
    Given the following "categories" exist:
      | name   | category | idnumber |
      | Health | 0        | HEALTH   |
    And the following "courses" exist:
      | fullname       | shortname | category | summary                    |
      | Safety at work | SAFE      | HEALTH   | The risks of the workplace |
    And the following "activities" exist:
      | activity | course | name    |
      | page     | SAFE   | Welcome |
    And I log in as "learner1"
    When I am on course index
    Then I should see "Safety at work" in the ".epure-catalogue-grid" "css_element"
    And the page should meet accessibility standards
    And I click on "Safety at work" "link" in the ".epure-catalogue-grid" "css_element"
    And I should see "About this course"
    And I should see "The risks of the workplace" in the ".epure-presentation" "css_element"
    And the page should meet accessibility standards

  Scenario: The quick search finds the activities of my courses
    Given the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "activities" exist:
      | activity | course | name               |
      | page     | SAFE   | Risks of the place |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And I log in as "learner1"
    When I click on ".epure-qs-button" "css_element"
    And I set the field with xpath "//input[contains(@class, 'epure-qs-input')]" to "risks"
    Then I should see "Risks of the place" in the "#epure-qs" "css_element"
    And the page should meet accessibility standards

  Scenario: A visitor who is not logged in searches the catalogue, the categories and the public pages
    # A site open without logging in (required by default with IOMAD and from Moodle 5.2), on the list of the courses:
    # from Moodle 5.2, the home page of the site sends the visitors to the login page. With IOMAD, a visitor searches
    # the public pages only (the next scenario).
    Given IOMAD is not installed
    And the following config values are set as admin:
      | forcelogin | 0 |
    And the following "categories" exist:
      | name     | category | idnumber |
      | Training | 0        | TRAIN    |
    And the following "courses" exist:
      | fullname       | shortname | category |
      | Safety at work | SAFE      | TRAIN    |
    When I am on course index
    And I click on ".epure-qs-button" "css_element"
    And I set the field with xpath "//input[contains(@class, 'epure-qs-input')]" to "safety"
    Then I should see "Safety at work" in the "#epure-qs" "css_element"
    And the page should meet accessibility standards
    And I set the field with xpath "//input[contains(@class, 'epure-qs-input')]" to "training"
    And I should see "Training" in the "#epure-qs-list" "css_element"
    And I set the field with xpath "//input[contains(@class, 'epure-qs-input')]" to "log in"
    And I should see "Log in" in the "#epure-qs-list" "css_element"

  Scenario: A visitor who is not logged in searches the public pages, with or without IOMAD
    Given the following config values are set as admin:
      | forcelogin | 0 |
    When I am on course index
    And I click on ".epure-qs-button" "css_element"
    And I set the field with xpath "//input[contains(@class, 'epure-qs-input')]" to "log in"
    Then I should see "Log in" in the "#epure-qs-list" "css_element"
    And the page should meet accessibility standards

  Scenario: The key « ? » opens the help of the keyboard shortcuts, which can be turned off
    Given I log in as "learner1"
    When I press the ? key
    Then "#epure-shortcuts" "css_element" should be visible
    And I should see "Open the quick search" in the "#epure-shortcuts" "css_element"
    And the focused element is "#epure-shortcuts-title" "css_element"
    And the page should meet accessibility standards
    And I press the escape key
    And "#epure-shortcuts" "css_element" should not be visible
    # Turned off (WCAG 2.1.4): « ? » no longer opens it, kept in the preferences of the user.
    And I press the ? key
    And I click on "Open this help with the key « ? »" "checkbox"
    And I press the escape key
    And I wait until the page is ready
    And I reload the page
    And I press the ? key
    And "#epure-shortcuts" "css_element" should not be visible

  Scenario: The keys of navigation, off by default, move between the sections and the activities once turned on
    Given the following "courses" exist:
      | fullname       | shortname | numsections |
      | Safety at work | SAFE      | 2           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following "activities" exist:
      | activity | course | name    | section |
      | page     | SAFE   | Welcome | 1       |
      | page     | SAFE   | Rules   | 1       |
    And I log in as "learner1"
    And I am on "Safety at work" course homepage
    # Off by default (WCAG 2.1.4): J does nothing.
    When I press the j key
    Then the focused element is "body" "css_element"
    And I press the ? key
    And I click on "Turn on the keys of navigation (N, P, J, K, G)" "checkbox"
    And I press the escape key
    And I wait until the page is ready
    And I reload the page
    And I press the j key
    And the focused element is "#region-main h3[data-for='section_title']" "css_element"
    And I am on the "Welcome" "page activity" page
    And I press the n key
    And I should see "Rules" in the "h1" "css_element"

  Scenario: The quick search works with the keyboard alone
    Given the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "activities" exist:
      | activity | course | name               |
      | page     | SAFE   | Risks of the place |
      | page     | SAFE   | Rules of the place |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And I log in as "learner1"
    When I press the ctrl k key
    Then "#epure-qs" "css_element" should be visible
    And the focused element is ".epure-qs-input" "css_element"
    And I set the field with xpath "//input[contains(@class, 'epure-qs-input')]" to "place"
    And I wait until "Rules of the place" "text" exists
    # The first result is chosen for Enter, without moving the screen readers to it while the user types.
    And the "aria-activedescendant" attribute of ".epure-qs-input" "css_element" should not be set
    And the "aria-selected" attribute of "#epure-qs-option-0" "css_element" should contain "true"
    # One result at a time with the arrows, followed by the screen readers.
    And I press the down key
    And the "aria-activedescendant" attribute of ".epure-qs-input" "css_element" should contain "epure-qs-option-1"
    And the "aria-selected" attribute of "#epure-qs-option-1" "css_element" should contain "true"
    And the "aria-selected" attribute of "#epure-qs-option-0" "css_element" should contain "false"
    # Tab stays in the dialogue.
    And I press the tab key
    And the focused element is ".epure-qs-close" "css_element"
    And I press the tab key
    And the focused element is ".epure-qs-input" "css_element"
    And I press the shift tab key
    And the focused element is ".epure-qs-close" "css_element"
    And I press the escape key
    And "#epure-qs" "css_element" should not be visible
    And the focused element is ".epure-qs-button" "css_element"

  Scenario: My summary as a PDF with a code of verification, which anyone can check
    Given the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following config values are set as admin:
      | mysummary | 1 | theme_epure |
    And I log in as "learner1"
    When I visit "/theme/epure/summary.php"
    Then "Download the PDF" "link" should exist
    And the user "learner1" has a summary document with the code "ABCD-EFGH-JKMN"
    And I log out
    # Logged out, with the code as typed: the document as issued.
    And I visit "/theme/epure/verify.php?code=abcd-efgh-jkmn"
    And I should see "Lea Learner" in the ".epure-summary-verify-document" "css_element"
    And I should see "Safety at work" in the ".epure-summary-verify-document" "css_element"
    And the page should meet accessibility standards
    # A code no document has.
    And I set the field "Code of verification" to "ABCD-EFGH-JKMP"
    And I press "Verify"
    And I should see "No document has the code ABCD-EFGH-JKMP"
    And the page should meet accessibility standards
    # The learner revokes it: the page of verification says only that it was issued and revoked.
    And I log in as "learner1"
    And I visit "/theme/epure/summary.php"
    And I should see "Valid" in the "ABCD-EFGH-JKMN" "table_row"
    And the page should meet accessibility standards
    And I click on "Revoke" "link" in the "ABCD-EFGH-JKMN" "table_row"
    And I press "Revoke"
    And I should see "The document ABCD-EFGH-JKMN was revoked."
    And I should see "Revoked on" in the "ABCD-EFGH-JKMN" "table_row"
    And I log out
    And I visit "/theme/epure/verify.php?code=ABCD-EFGH-JKMN"
    And I should see "revoked by its holder"
    And I should not see "Safety at work"

  Scenario: The long settings forms list their sections and keep the buttons to save in view
    Given the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
      | First aid      | AID       |
    And I log in as "admin"
    And I change window size to "large"
    When I am on the "SAFE" "course editing" page
    Then ".epure-longform-actions" "css_element" should exist
    And I keep the values of the long form
    And the long form should tell "none"
    And "Completion tracking" "link" should exist in the ".epure-longform-nav" "css_element"
    And I click on "Completion tracking" "link" in the ".epure-longform-nav" "css_element"
    And "Enable completion tracking" "field" should be visible
    And the long form should tell "none"
    And I should see "No changes to save" in the ".epure-longform-actions" "css_element"
    And I set the field "Course full name" to "Safety at work, 2027"
    And I should see "Changes not saved" in the ".epure-longform-actions" "css_element"
    And I set the field "Course full name" to "Safety at work"
    And the long form should tell "none"
    And I should see "No changes to save" in the ".epure-longform-actions" "css_element"
    And I set the field "Course short name" to "AID"
    And I press "Save and display"
    And I should see "Not saved: 1 field to correct" in the ".epure-longform-actions" "css_element"
    And the page should meet accessibility standards

  Scenario: The pages of settings of the administration, in cards, meet accessibility standards
    Given I log in as "admin"
    When I navigate to "Security > Site security settings" in site administration
    Then I should see "Protect usernames"
    And the page should meet accessibility standards
    And I visit "/admin/settings.php?section=themesettingepure"
    And I should see "Logos" in the "#adminsettings" "css_element"
    And the page should meet accessibility standards
    And I visit "/admin/search.php?query=password"
    And I should see "Search results"
    And the page should meet accessibility standards

  Scenario: The window to add a block searches the blocks and meets accessibility standards
    Given I log in as "admin"
    And I visit "/my/"
    And I turn editing mode on
    Then I should see "Timeline, calendar, badges and more" in the "[data-region='add-block-button']" "css_element"
    And I click on "a[data-key='addblock'][data-blockregion='content']" "css_element"
    And I set the field "Search for a block" to "badges"
    And I should see "Latest badges" in the "Add a block" "dialogue"
    And I should not see "Comments" in the "Add a block" "dialogue"
    And the page should meet accessibility standards
    And I click on "Latest badges" "link" in the "Add a block" "dialogue"
    And "Latest badges" "block" should exist

  Scenario: My summary lists the courses of the learner and meets accessibility standards
    Given the following config values are set as admin:
      | mysummary | 1 | theme_epure |
    And the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And I log in as "learner1"
    And I follow "Dashboard"
    When I click on "My summary" "link" in the ".epure-learner" "css_element"
    Then I should see "Courses taken"
    And I should see "Safety at work" in the ".epure-summary" "css_element"
    And "Print the page" "button" should exist
    And the page should meet accessibility standards
    # The summary is also in the user menu and in the reports of the profile.
    And I follow "Profile" in the user menu
    And "My summary" "link" should exist in the ".epure-profile-more" "css_element"
    And I follow "My summary" in the user menu
    And I should see "Courses taken"

  Scenario: The accessibility statement is filled in step by step and published
    Given I log in as "admin"
    When I visit "/theme/epure/a11ystatement.php"
    Then I should see "Step 1 of 5: Who publishes the statement"
    And I set the field "Entity" to "Clinique des Tilleuls"
    And I press "Save and continue"
    And I should see "Step 2 of 5: The audit"
    And I set the field "Compliance rate" to "78 %"
    And I press "Save and continue"
    And I should see "Rate of compliance entered at the previous step: 78 %."
    And the page should meet accessibility standards
    And I set the field "Compliance status" to "partially compliant"
    And I press "Save and continue"
    And I press "Save and continue"
    And I press "Save changes"
    And I should see "The statement is saved and published."
    And I should see "Clinique des Tilleuls is committed"
    And "Print the statement" "button" should exist

  Scenario: The fields of the setting of the emoticons have a name
    Given I log in as "admin"
    When I visit "/admin/settings.php?section=htmlsettings"
    Then the page should meet accessibility standards

  Scenario: The defects of Moodle found by an audit RGAA are repaired in the page
    Given I log in as "admin"
    # The month of the calendar: its caption, its headers of columns.
    When I visit "/calendar/view.php?view=month"
    Then "table.calendarmonth caption" "css_element" should exist
    And "table.calendarmonth thead th[scope='col']" "css_element" should exist
    # A required field says so in its label.
    And I visit "/course/edit.php?category=1"
    And I should see "(required)" in the "label[for='id_fullname']" "css_element"
    And the "aria-hidden" attribute of ".fdescription.required" "css_element" should not be set
    # The course index has a name; the key Space presses a link made button.
    And the following "courses" exist:
      | fullname     | shortname | numsections |
      | Audit course | audit     | 2           |
    And I am on "Audit course" course homepage
    And "nav#courseindex[aria-label='Course index']" "css_element" should exist
    And the "aria-expanded" attribute of "li#section-1 a.icons-collapse-expand" "css_element" should contain "true"
    And I set the focus on the "li#section-1 a.icons-collapse-expand" "css_element"
    And I press the space key
    And the "aria-expanded" attribute of "li#section-1 a.icons-collapse-expand" "css_element" should contain "false"
    And the page should meet accessibility standards
    # The participants: the letters that filter by first name and by last name, the choice of the page.
    And I navigate to course participants
    And "nav.initialbargroups[aria-label='First name']" "css_element" should exist
    And "nav.initialbargroups[aria-label='Last name']" "css_element" should exist
    And "nav.tertiary-navigation-selector[aria-label='Choice of the page']" "css_element" should exist
    And the page should meet accessibility standards

  Scenario: The title of a link starts with the text shown, without its hidden texts for screen readers
    Given the following "courses" exist:
      | fullname     | shortname |
      | Rules course | RULES     |
    And the following "activities" exist:
      | activity | course | name         | content                                                                                                                                                               |
      | page     | RULES  | Centre rules | <p><a id="rules" href="https://example.com/rules" title="Internal regulations of the centre">Rules <span class="accesshide">(read before the training)</span></a></p><details><summary>More</summary><a id="charter" href="https://example.com/charter" title="Rules signed by every learner">Charter <span class="accesshide">(signed at the start)</span></a></details> |
    When I am on the "Centre rules" "page activity" page logged in as "admin"
    Then the "title" attribute of "#rules" "css_element" should contain "Rules – Internal regulations of the centre"
    And the "title" attribute of "#rules" "css_element" should not contain "read before"
    # A link not shown yet (in a closed part of the page) gets its title as well.
    And the "title" attribute of "#charter" "css_element" should contain "Charter – Rules signed by every learner"
    And the "title" attribute of "#charter" "css_element" should not contain "signed at"

  Scenario: Before saving, the content checker warns of a link alone that says nothing, never of what is right
    Given the following config values are set as admin:
      | courseaccessibility | 1 | theme_epure |
    And the following "courses" exist:
      | fullname | shortname |
      | Hygiene  | HYG       |
    And the following "activities" exist:
      | activity | course | name  | idnumber |
      | page     | HYG    | Rules | rules1   |
    When I am on the "Rules" "page activity editing" page logged in as admin
    # A short link clear in its sentence, a decorative image, a table of layout and headers on two rows: all right.
    And I set the field "Page content" to "<p>The programme is <a href=\"https://example.com\">here</a>.</p><p><img src=\"https://example.com/a.png\" alt=\"\"></p><table role=\"presentation\"><tr><td>Layout</td></tr></table><table><thead><tr><th colspan=\"2\">Week</th></tr><tr><th>Day</th><th>Topic</th></tr></thead><tbody><tr><td>Monday</td><td>Gloves</td></tr></tbody></table>"
    And I press "Save and display"
    Then "Before saving: accessibility" "dialogue" should not exist
    And I should see "Monday"
    And I am on the "Rules" "page activity editing" page
    And I set the field "Page content" to "<p><a href=\"https://example.com\">here</a></p>"
    # As it is written, the line under the editor says it.
    And I wait until "Accessibility: links that say nothing: 1" "text" exists
    And I press "Save and display"
    And "Before saving: accessibility" "dialogue" should exist
    And I should see "whose text does not say where they go" in the "Before saving: accessibility" "dialogue"
    And I click on "Save anyway" "button" in the "Before saving: accessibility" "dialogue"
    And I should see "here" in the "region-main" "region"

  Scenario: Without the guide « Accessibility of the course », the content checker says nothing
    Given the following "courses" exist:
      | fullname | shortname |
      | Hygiene  | HYG       |
    And the following "activities" exist:
      | activity | course | name  | idnumber |
      | page     | HYG    | Rules | rules1   |
    When I am on the "Rules" "page activity editing" page logged in as admin
    And I set the field "Page content" to "<p><a href=\"https://example.com\">here</a></p>"
    And I press "Save and display"
    Then "Before saving: accessibility" "dialogue" should not exist
    And I should not see "Accessibility: links that say nothing"
    And I should see "here" in the "region-main" "region"

  Scenario: The guide « Accessibility of the course » lists what to fix and to confirm, and meets accessibility standards
    Given the following config values are set as admin:
      | courseaccessibility | 1 | theme_epure |
    And the following "courses" exist:
      | fullname | shortname | summary |
      | Hygiene  | HYG       |         |
    And the following "activities" exist:
      | activity | course | name       | idnumber | content                                         |
      | page     | HYG    | Document 2 | doc2     | <p><img src="https://example.com/gloves.png"></p> |
    And the following "activities" exist:
      | activity | course | name       | idnumber | timelimit |
      | quiz     | HYG    | Final quiz | quiz1    | 1800      |
    When I am on the "Hygiene" course page logged in as admin
    And I turn editing mode on
    # What is left, with the score of the course.
    Then I should see "Accessibility: 2 points to fix, 2 to confirm (score "
    And I should see "2 points of accessibility" in the "Document 2" "activity"
    And I click on "2 points of accessibility" "link" in the "Document 2" "activity"
    And I should see "Images without a text alternative: 1."
    And I should see "The name does not say what the activity holds."
    And the page should meet accessibility standards
    And I bring "Learners who need more time can get it" "field" to the middle of the screen
    And I set the field "Learners who need more time can get it" to "1"
    And I press "Save changes"
    And I should see "The confirmations are saved."
    And I should see "Confirmed by Admin User"
    And I should see "2 points to fix, 1 to confirm"
    And I follow "The reminder to print"
    And I should see "Making my courses accessible"
    And I should see "Back to the accessibility of « Hygiene »"
    And the page should meet accessibility standards

  Scenario: A certificate of the activity Course certificate, on the template of Épure, meets accessibility standards
    Given the certificate template of Épure exists
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Paul      | Durand   | teacher1@example.com |
    And the following "courses" exist:
      | fullname                                                                                                   | shortname |
      | Prevention of the risks linked to physical activity in health and social care establishments, initial session | PRAP      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | PRAP   | student        |
      | teacher1 | PRAP   | editingteacher |
    And the following "activities" exist:
      | activity          | course | name        | idnumber | template                              |
      | coursecertificate | PRAP   | Certificate | cert1    | Certificate — Acceptance test site |
    When I log in as "learner1"
    Then the following pages should meet accessibility standards in every display:
      | path                                            |
      | /mod/coursecertificate/view.php?id={cm cert1}   |
    And I log out
    And I log in as "teacher1"
    And the following pages should meet accessibility standards in every display:
      | path                                            | except     |
      | /mod/coursecertificate/view.php?id={cm cert1}   | wide table |
