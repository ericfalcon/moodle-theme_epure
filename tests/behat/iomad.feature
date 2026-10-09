@theme @theme_epure @theme_epure_iomad @javascript @accessibility
Feature: The Épure theme on IOMAD
  In order to recognise my company on the platform
  As a learner of an IOMAD company
  I need the platform in the colour and with the logo of my company

  Background:
    Given IOMAD is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
      | learner2 | Leo       | Learner  | learner2@example.com |
    And the following "courses" exist:
      | fullname         | shortname | enablecompletion |
      | Safety at work   | C1        | 1                |
    And the following "activities" exist:
      | activity | course | name         | completion |
      | page     | C1     | Introduction | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |
    And the following IOMAD companies exist:
      | name             | shortname | theme | headingcolor |
      | Clinique Tilleul | tilleul   | epure | #9B2335      |
    And the following IOMAD company users exist:
      | user     | company |
      | learner1 | tilleul |

  Scenario: A learner of a company sees the platform in the colour and with the logo of their company
    Given the IOMAD company "tilleul" has the logo "lib/tests/fixtures/gd-logo.png"
    When I log in as "learner1"
    Then the brand colour of the page should come from "#9B2335"
    And the logo of the IOMAD company "tilleul" should be shown
    And the page should meet accessibility standards
    And I am on "Safety at work" course homepage
    And I should see "Introduction"
    And the brand colour of the page should come from "#9B2335"
    And the page should meet accessibility standards

  Scenario: A user without company keeps the colour of the platform
    When I log in as "learner2"
    Then "#epure-company-style" "css_element" should not exist
    And the brand colour of the page should not come from "#9B2335"

  Scenario: The « My courses » page of IOMAD is the page by role of Épure
    When I log in as "learner1"
    And I am on the "My courses" page
    Then "[data-region='epure-mycourses']" "css_element" should exist
    And I should see "Safety at work"
    And the page should meet accessibility standards
