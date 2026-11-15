@theme @theme_epure @javascript @accessibility
Feature: The courses a teacher teaches as a table on « My courses »
  In order to see at a glance how my courses are going
  As a teacher
  I need my courses as a table, sorted as I choose, accessible and kept from a visit to another

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Paul      | Durand   | teacher1@example.com |
      | learner1 | Camille   | Martin   | learner1@example.com |
      | learner2 | Hugo      | Petit    | learner2@example.com |
    And the following "courses" exist:
      | fullname        | shortname | enablecompletion |
      | Safety at work  | SAFE      | 1                |
      | Managing a team | TEAM      | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | SAFE   | editingteacher |
      | teacher1 | TEAM   | editingteacher |
      | learner1 | SAFE   | student        |
      | learner2 | SAFE   | student        |
      | learner1 | TEAM   | student        |
    And the following "activities" exist:
      | activity | course | name            | idnumber | duedate         |
      | assign   | SAFE   | Risk assessment | assign1  | ##+3 days##     |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |

  Scenario: A teacher shows their courses as a table, sorts it, and finds it again as a table
    Given I log in as "teacher1"
    And I am on the "My courses" page
    When I press "Table"
    Then ".epure-mycourses-table" "css_element" should be visible
    And I should see "Risk assessment" in the "Safety at work" "table_row"
    And I should see "Completion" in the ".epure-mycourses-table thead" "css_element"
    And I press "Participants"
    And the "aria-sort" attribute of "//th[button[@data-sort='learners']]" "xpath_element" should contain "ascending"
    And I press "Participants"
    And the "aria-sort" attribute of "//th[button[@data-sort='learners']]" "xpath_element" should contain "descending"
    And I reload the page
    And ".epure-mycourses-table" "css_element" should be visible
    And the following pages should meet accessibility standards in the four displays:
      | path             |
      | /my/courses.php  |
    And I press "Cards"
    And ".epure-mycourses-table" "css_element" should not be visible
