@theme @theme_epure @javascript @accessibility
Feature: The course formats of Moodle with the Épure theme
  In order to follow my courses whatever their format
  As a learner
  I need the course pages of every standard format to work and be accessible

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |

  Scenario Outline: The course page of a format with sections meets accessibility standards
    Given I enable "subsection" "mod" plugin
    And the following "courses" exist:
      | fullname      | shortname | format   | numsections | enablecompletion |
      | Course <name> | C1        | <format> | 3           | 1                |
    And the following "activities" exist:
      | activity   | course | section | name        | completion |
      | page       | C1     | 1       | Welcome     | 1          |
      | assign     | C1     | 1       | Assignment  | 1          |
      | url        | C1     | 2       | Useful link | 0          |
      | subsection | C1     | 2       | Part A      | 0          |
      | quiz       | C1     | 3       | Final quiz  | 0          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
    When I am on the "Course <name>" "course" page logged in as "learner1"
    Then I should see "Welcome"
    And I should see "Part A"
    And I should see "Final quiz"
    And the page should meet accessibility standards

    Examples:
      | name   | format |
      | topics | topics |
      | weeks  | weeks  |

  Scenario: In the format « Single activity », the activity is the course, without previous and next activities
    Given the following "courses" exist:
      | fullname      | shortname | format         | activitytype |
      | Single course | C1        | singleactivity | page         |
    And the following "activities" exist:
      | activity | course | name          | content                  |
      | page     | C1     | The only page | Welcome to this course.  |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
    When I am on the "Single course" "course" page logged in as "learner1"
    Then I should see "Welcome to this course."
    And "Back to the course" "text" should not exist
    And ".epure-activity-strip" "css_element" should not exist
    And the page should meet accessibility standards

  Scenario: The course page of the format « Social » meets accessibility standards
    Given the following "courses" exist:
      | fullname      | shortname | format |
      | Social course | C1        | social |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
    When I am on the "Social course" "course" page logged in as "learner1"
    Then I should see "Add discussion topic"
    And the page should meet accessibility standards
