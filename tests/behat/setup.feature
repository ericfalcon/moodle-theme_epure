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
    Then I should see "Step 1 of 4: Identity"
    And I should see "Computed palette"
    And the page should meet accessibility standards
    And I set the field "Full site name" to "Clinique des Tilleuls"
    And I set the field "id_s_theme_epure_brandcolor" to "nonsense"
    And I press "Save and continue"
    And I should see "Enter a hex colour code"
    And the page should meet accessibility standards
    # Back to its colour: a new brand colour would build the stylesheet of the theme again, longer than the tests wait.
    And I set the field "id_s_theme_epure_brandcolor" to "#2559A8"
    And I press "Save and continue"
    And I should see "Step 2 of 4: Login page"
    # The file manager of the login image lists its files after the page: once it has, nothing moves under the buttons.
    And I wait until ".filemanager.fm-loaded" "css_element" exists
    And I set the field "Model" to "Company: training of the employees"
    And I press "Save and continue"
    And I should see "Step 3 of 4: Footer"
    And I set the field "id_s_theme_epure_footertext" to "Continuing education of the clinic."
    And I press "Save and continue"
    And I should see "Step 4 of 4: Accessibility statement"
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
    And I should see "Step 1 of 4: Identity"
