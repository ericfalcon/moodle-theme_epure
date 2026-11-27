@theme @theme_epure @javascript @accessibility
Feature: The course formats of other plugins with the Épure theme
  In order to follow my courses whatever their format, even one of another plugin
  As a learner
  I need the course page of the most installed formats to work and be accessible in every display

  # The formats are added to some jobs of the continuous integration, on the versions of Moodle their branches support
  # (.github/workflows/ci.yml, « formats »): elsewhere, the scenario is skipped.
  Scenario Outline: The course page of the format « <format> » meets accessibility standards in every display
    Given the course format "<format>" is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |
    And the following "courses" exist:
      | fullname        | shortname | format   | numsections | enablecompletion |
      | Course <format> | C1        | <format> | 3           | 1                |
    And the following "activities" exist:
      | activity | course | section | name        | completion |
      | page     | C1     | 1       | Welcome     | 1          |
      | assign   | C1     | 1       | Assignment  | 1          |
      | url      | C1     | 2       | Useful link | 0          |
      | quiz     | C1     | 3       | Final quiz  | 0          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
    When I log in as "learner1"
    And I am on "Course <format>" course homepage
    Then I should see "Course <format>"
    And the following pages should meet accessibility standards in every display:
      | path                            |
      | /course/view.php?id={course C1} |

    Examples:
      | format       |
      | grid         |
      | topcoll      |
      | flexsections |
      | tiles        |
      | sectiontabs  |
      | onetopic     |
      | buttons      |
      | multitopic   |
      | designer     |
      | remuiformat  |

  # A colour chosen for the course, that neither white nor the dark ink reads at 4.5:1: Épure moves it as little as
  # needed, in light and dark mode and under the cream overlay; yellow on black puts its own colours.
  Scenario: The colour chosen for the tabs of Section tabs is readable in every display
    Given the course format "sectiontabs" is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the following config values are set as admin:
      | theme | epure |
    And the following "courses" exist:
      | fullname           | shortname | format      | numsections | gtabsactivecolor |
      | Course coloured    | C1        | sectiontabs | 3           | #d35400          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
    When I log in as "learner1"
    And I am on "Course coloured" course homepage
    Then I should see "Course coloured"
    And the following pages should meet accessibility standards in every display:
      | path                            |
      | /course/view.php?id={course C1} |

  # On a phone, the navigation bar of Épure offers the previous and next section of the format, within reach of the
  # thumb: it follows the bar of the format, and a click opens the section the format opens.
  Scenario: On a phone, the navigation bar offers the previous and next section of Section tabs
    Given the course format "sectiontabs" is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the following config values are set as admin:
      | theme | epure |
    And the following "courses" exist:
      | fullname     | shortname | format      | numsections |
      | Course steps | C1        | sectiontabs | 3           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
    And I change viewport size to "390x900"
    When I log in as "learner1"
    And I am on "Course steps" course homepage
    Then ".epure-mobilenav-next" "css_element" should be visible
    And ".epure-mobilenav-prev" "css_element" should not be visible
    # One pair of buttons only: the bar of the format, which goes to the same pages, is not shown on phones.
    And "#region-main .gnav" "css_element" should not be visible
    And I click on ".epure-mobilenav-next" "css_element"
    And I wait until the page is ready
    And ".epure-mobilenav-prev" "css_element" should be visible
    And ".epure-mobilenav-next" "css_element" should be visible
    And "#region-main .gnav" "css_element" should not be visible
    # On a computer, the bar of the format is there, the row of Épure is not.
    And I change viewport size to "1300x900"
    And I reload the page
    And "#region-main .gnav" "css_element" should be visible
    And ".epure-mobilenav-next" "css_element" should not be visible
    And the page should meet accessibility standards, the audit waited for
