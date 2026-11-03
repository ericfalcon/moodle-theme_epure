@theme @theme_epure @theme_epure_matrix @javascript @accessibility
Feature: Matrix of non-regression of the accessibility of the Épure theme
  In order to use the platform whatever the brand colour, my display preferences and my keyboard
  As a learner or a teacher
  I need the accessibility functions of the theme to keep working together

  # The pre-audit checks the pages with the default brand colour and display preferences. This matrix crosses the
  # functions: brand colours light, dark and of mid tones, the display preferences together, the elements focused with
  # the keyboard and the fixed bars, the panel « Aa » with the keyboard, the preferences from page to page and a timed
  # quiz. Each page in the four displays: light and dark at 1300 px, light at 390 and 320 px (reflow at 400 % zoom).

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
      | teacher1 | Paul      | Durand   | teacher1@example.com |
    And the users belong to an IOMAD company when IOMAD is installed
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "activities" exist:
      | activity | course | name    | idnumber | completion |
      | page     | SAFE   | Welcome | page1    | 1          |
      | page     | SAFE   | Risks   | page2    | 1          |
    And the following "activities" exist:
      | activity | course | name       | idnumber | timelimit |
      | quiz     | SAFE   | Timed test | quiz1    | 600       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | SAFE   | student        |
      | teacher1 | SAFE   | editingteacher |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |
    And the following config values are set as admin:
      | mysummary | 1 | theme_epure |

  Scenario Outline: The pages meet accessibility standards with a brand colour <kind>
    Given the brand colour of the site is "<colour>"
    When I log in as "learner1"
    Then the following pages should meet accessibility standards in the four displays:
      | path                              |
      | /my/                              |
      | /course/view.php?id={course SAFE} |
      | /mod/page/view.php?id={cm page1}  |
      | /theme/epure/summary.php          |

    Examples:
      | kind               | colour  |
      | light              | #FFD400 |
      | dark               | #111111 |
      | of mid tone, blue  | #2D78E1 |
      | of mid tone, red   | #E13C2D |

  Scenario: The pages meet accessibility standards with all the display preferences on
    Given the following "user preferences" exist:
      | user     | preference                  | value        |
      | learner1 | theme_epure_a11y_text       | 130          |
      | learner1 | theme_epure_a11y_font       | opendyslexic |
      | learner1 | theme_epure_a11y_spacing    | 1            |
      | learner1 | theme_epure_a11y_lines      | 1            |
      | learner1 | theme_epure_a11y_contrast   | 1            |
      | learner1 | theme_epure_a11y_underline  | 1            |
      | learner1 | theme_epure_a11y_motion     | 1            |
    When I log in as "learner1"
    Then the "class" attribute of "html" "css_element" should contain "epure-a11y-text-130"
    And the "class" attribute of "html" "css_element" should contain "epure-a11y-motion"
    And the following pages should meet accessibility standards in the four displays:
      | path                              |
      | /my/                              |
      | /course/view.php?id={course SAFE} |
      | /mod/page/view.php?id={cm page1}  |
      | /theme/epure/summary.php          |

  Scenario: The elements focused with the keyboard are never hidden under the fixed bars
    When I log in as "teacher1"
    Then the elements focused with the keyboard should not be hidden under a fixed bar on the pages:
      | path                                           |
      | /course/edit.php?id={course SAFE}              |
      | /course/modedit.php?update={cm page2}          |
    And I log out
    And I log in as "learner1"
    And the elements focused with the keyboard should not be hidden under a fixed bar on the pages:
      | path                              |
      | /my/                              |
      | /course/view.php?id={course SAFE} |

  Scenario: The panel « Aa » opens with Alt + A, closes with Escape and gives the focus back
    Given I log in as "learner1"
    And I am on homepage
    When I press the alt a key
    Then "#epure-a11y-panel" "css_element" should be visible
    And I press the escape key
    And "#epure-a11y-panel" "css_element" should not be visible
    And the focused element is "Display preferences" "button"
    And I click on "Display preferences" "button"
    And "#epure-a11y-panel" "css_element" should be visible
    And I press the escape key
    And the focused element is "Display preferences" "button"

  Scenario: The display preferences follow the learner from page to page
    Given I log in as "learner1"
    And I am on homepage
    When I click on "Display preferences" "button"
    And I click on "Very large" "text" in the "#epure-a11y-panel" "css_element"
    And I click on "Underline links" "checkbox"
    And I wait until "Saved in your profile." "text" exists
    And I am on "Safety at work" course homepage
    Then the "class" attribute of "html" "css_element" should contain "epure-a11y-text-130"
    And the "class" attribute of "html" "css_element" should contain "epure-a11y-underline"
    And I am on the "Welcome" "page activity" page
    And the "class" attribute of "html" "css_element" should contain "epure-a11y-text-130"

  Scenario: A timed quiz shows its timer and meets accessibility standards
    Given the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext          |
      | Test questions   | truefalse | TF1  | Gloves protect hands. |
    And quiz "Timed test" contains the following questions:
      | question | page |
      | TF1      | 1    |
    When I am on the "Timed test" "quiz activity" page logged in as "learner1"
    And I press "Attempt quiz"
    And I click on "Start attempt" "button" in the "Start attempt" "dialogue"
    Then I should see "Time left"
    And I should see "Gloves protect hands."
    And the page should meet accessibility standards
