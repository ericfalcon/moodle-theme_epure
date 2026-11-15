@theme @theme_epure @javascript @accessibility
Feature: Getting started with Épure
  In order to set up a new platform quickly
  As an administrator
  I need a few steps that fill the settings that matter most, offered until I finish or dismiss them

  Background:
    Given the following config values are set as admin:
      | theme | epure |

  Scenario: An administrator goes through the getting started, which is then not offered any more
    Given I log in as "admin"
    And I visit "/admin/search.php"
    And I should see "Getting started with Épure" in the ".epure-admin-setup" "css_element"
    When I click on "Start" "link" in the ".epure-admin-setup" "css_element"
    Then I should see "Step 1 of 5: Name and logo"
    And the page should meet accessibility standards
    And I set the field "Full site name" to "Clinique des Tilleuls"
    And I press "Save and continue"
    And I should see "Step 2 of 5: Colours"
    And I should see "Add a logo at step 1: its main colours will be proposed here."
    And I set the field "id_brandcolor" to "nonsense"
    And I press "Save and continue"
    And I should see "Enter a hex colour code"
    And I set the field "id_brandcolor" to "#1E6B52"
    And I press "Save and continue"
    And I should see "Step 3 of 5: Login page"
    And I set the field "Model" to "Company: training of the employees"
    And I press "Save and continue"
    And I should see "Step 4 of 5: Footer"
    And I set the field "id_footertext" to "Continuing education of the clinic."
    And I press "Save and continue"
    And I should see "Step 5 of 5: Accessibility statement"
    And I should see "Fill the accessibility statement"
    And the page should meet accessibility standards
    And I press "Finish"
    And I should see "Épure is set up."
    And ".epure-admin-setup" "css_element" should not exist
    And I log out
    And I visit "/login/index.php"
    And I should see "Learn at your own pace"
    And I should see "Continuing education of the clinic."

  Scenario: An administrator dismisses the getting started
    Given I log in as "admin"
    And I visit "/admin/search.php"
    When I click on "Do not show again" "link" in the ".epure-admin-setup" "css_element"
    Then ".epure-admin-setup" "css_element" should not exist
    And I visit "/theme/epure/setup.php"
    And I should see "Step 1 of 5: Name and logo"
