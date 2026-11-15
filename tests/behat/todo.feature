@theme @theme_epure @javascript @accessibility
Feature: The page « To do » of a learner
  In order to know what is left to do in all my courses
  As a learner
  I need my activities still to do grouped by deadline, filtered by course and by period

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
    And the following "courses" exist:
      | fullname        | shortname | enablecompletion |
      | Safety at work  | SAFE      | 1                |
      | Managing a team | TEAM      | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
      | learner1 | TEAM   | student |
    And the following "activities" exist:
      | activity | course | name            | duedate      | completion |
      | assign   | SAFE   | Risk report     | ##-3 days##  | 1          |
      | assign   | SAFE   | Fire drill quiz | ##+2 days##  | 1          |
      | assign   | TEAM   | Final essay     | ##+20 days## | 1          |
    And the following config values are set as admin:
      | theme | epure |
    And the users belong to an IOMAD company when IOMAD is installed

  Scenario: A learner sees what is left to do and filters it
    Given I log in as "learner1"
    And I visit "/my/"
    When I click on "Everything to do" "link"
    Then I should see "3 activities to do"
    And I should see "Risk report" in the ".epure-todo-late" "css_element"
    And I should see "Fire drill quiz" in the ".epure-todo-week" "css_element"
    And I should see "Final essay" in the ".epure-todo-later" "css_element"
    And I set the field "Course" to "Managing a team"
    And I press "Filter"
    And I should see "1 activity to do"
    And I should see "Final essay"
    And I should not see "Risk report"
    And the following pages should meet accessibility standards in the four displays:
      | path                    |
      | /theme/epure/todo.php   |
