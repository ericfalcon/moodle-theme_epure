@theme @theme_epure @javascript
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
