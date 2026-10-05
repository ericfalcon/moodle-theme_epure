@theme @theme_epure @javascript @accessibility
Feature: Accessibility of the Épure theme
  In order to use the platform whatever my needs
  As a user
  I need accessible pages, display preferences kept in my profile and an accessibility statement

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
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

  Scenario: The pages of the activities lead to the next one, with a reading mode
    Given the following "courses" exist:
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
