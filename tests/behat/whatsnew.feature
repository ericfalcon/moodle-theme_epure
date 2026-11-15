@theme @theme_epure @javascript @accessibility
Feature: What is new in Épure, after an update
  In order to know what changed for my site
  As an administrator
  I need the notes of the versions installed since the previous one, until I close them

  Background:
    Given the following config values are set as admin:
      | theme | epure |
    # As after an update from 1.22.0 to 1.23.0; the getting started already finished.
    And the following config values are set as admin:
      | whatsnewbase   | 1.22.0 | theme_epure |
      | currentrelease | 1.23.0 | theme_epure |
      | setupdone      | 1      | theme_epure |

  Scenario: An administrator reads what is new, then closes it
    Given I log in as "admin"
    When I visit "/admin/search.php"
    Then I should see "What is new in Épure since 1.22.0" in the ".epure-whatsnew" "css_element"
    And I should see "1.23.0" in the ".epure-whatsnew" "css_element"
    And I should not see "1.22.0 –" in the ".epure-whatsnew" "css_element"
    And the page should meet accessibility standards
    And I click on "Read, close" "link" in the ".epure-whatsnew" "css_element"
    And ".epure-whatsnew" "css_element" should not exist
