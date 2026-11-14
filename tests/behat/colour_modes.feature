@theme @theme_epure @javascript @accessibility
Feature: The colour modes of Boost on the pages of Épure
  In order to read the pages in the colours of the theme or the ones I chose
  As a learner
  I need the colour scheme of Épure to decide, whatever the colour modes of Boost

  # Moodle 5.3 gives Boost colour modes (an experimental setting), which apply to the child themes of Boost. Their
  # dark mode mixed with the light palette of Épure: Épure keeps Bootstrap light and hides their menu. Before
  # Moodle 5.3, the settings do nothing, and the scenario checks the same.

  Scenario: The dark colour mode of Boost does not change a page of Épure
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "activities" exist:
      | activity | course | name    | idnumber | completion |
      | page     | SAFE   | Welcome | page1    | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following config values are set as admin:
      | theme | epure |
    And the following config values are set as admin:
      | enablecolourmodes | 1    | theme_boost |
      | defaultcolourmode | dark | theme_boost |
    When I am on the "Safety at work" "Course" page logged in as "learner1"
    Then "[data-region='colourmode-menu']" "css_element" should not exist
    And the page should not be in the dark colour mode of Boost
    And the page should meet accessibility standards
