@theme @theme_epure @theme_epure_iomad @javascript @accessibility
Feature: Getting started with Épure for an IOMAD company
  In order to set up my company quickly
  As a manager of an IOMAD company
  I need the sections of Épure of the form of my company one after the other, offered until I save them or dismiss them

  Background:
    Given IOMAD is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Gaëlle    | Gestion  | manager1@example.com |
    And the following config values are set as admin:
      | theme | epure |
    And the following IOMAD companies exist:
      | name                  | shortname | theme |
      | Clinique des Tilleuls | tilleuls  | epure |
    And the following IOMAD company users exist:
      | user     | company  | manager |
      | manager1 | tilleuls | 1       |

  Scenario: A manager goes through the getting started of the company, which is then not offered any more
    Given I log in as "manager1"
    And I visit "/blocks/iomad_company_admin/index.php"
    And I should see "Getting started with Épure for Clinique des Tilleuls" in the ".epure-admin-setup" "css_element"
    When I click on "Start" "link" in the ".epure-admin-setup" "css_element"
    Then I should see "Step 1 of 4: Identity"
    And the page should meet accessibility standards
    And I press "Next step"
    And I should see "Step 2 of 4: Login page"
    And I press "Next step"
    And I should see "Step 3 of 4: Footer"
    And I press "Previous step"
    And I should see "Step 2 of 4: Login page"
    And I press "Next step"
    And I press "Next step"
    And I should see "Step 4 of 4: Vocabulary"
    And I should see "Last step: save the company below to apply all your choices."
    And the page should meet accessibility standards
    And I press "Save changes"
    And I visit "/blocks/iomad_company_admin/index.php"
    And ".epure-admin-setup" "css_element" should not exist

  Scenario: A manager dismisses the getting started of the company
    Given I log in as "manager1"
    And I visit "/blocks/iomad_company_admin/index.php"
    When I click on "Do not show again" "link" in the ".epure-admin-setup" "css_element"
    Then ".epure-admin-setup" "css_element" should not exist
