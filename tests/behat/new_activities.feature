@theme @theme_epure @javascript @accessibility
Feature: The activities added since the previous visit of a learner are marked « New »
  In order to see what changed in my course
  As a learner
  I need the activities added since my last visit marked on the course page and counted on the card of the course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following "activities" exist:
      | activity | course | name             | idnumber |
      | page     | SAFE   | Welcome          | page1    |
      | page     | SAFE   | Fire procedure    | page2    |
      | page     | SAFE   | Risk assessment  | page3    |
    And the following config values are set as admin:
      | theme           | epure |
      | enablemycourses | 1     |
    And the users belong to an IOMAD company when IOMAD is installed
    And the activity "page1" was added 30 days ago
    And "learner1" last visited the course "SAFE" 7 days ago

  Scenario: A learner sees the activities added since their last visit
    Given I log in as "learner1"
    When I am on "Safety at work" course homepage
    Then I should see "New" in the "Fire procedure" "activity"
    And I should see "New" in the "Risk assessment" "activity"
    And I should not see "New" in the "Welcome" "activity"
    And the page should meet accessibility standards
    And I am on the "My courses" page
    And I should see "2 new activities since your last visit"

  Scenario: A course never visited shows no badge
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner2 | Hugo      | Petit    | learner2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner2 | SAFE   | student |
    And the users belong to an IOMAD company when IOMAD is installed
    When I log in as "learner2"
    And I am on "Safety at work" course homepage
    Then I should not see "New" in the "Fire procedure" "activity"
