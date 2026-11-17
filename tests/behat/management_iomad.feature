@theme @theme_epure @theme_epure_iomad @javascript @accessibility
Feature: The management dashboard of an IOMAD company
  In order to follow the activity of my company
  As a manager of an IOMAD company
  I need the management dashboard of my company, from the IOMAD dashboard

  Background:
    Given IOMAD is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Gaëlle    | Gestion  | manager1@example.com |
      | manager2 | Hugo      | Hôtel    | manager2@example.com |
    And the following config values are set as admin:
      | theme | epure |
    And the following IOMAD companies exist:
      | name                  | shortname | theme |
      | Clinique des Tilleuls | tilleuls  | epure |
      | Hôtel du Lac          | lac       | epure |
    And the following IOMAD company users exist:
      | user     | company  | manager |
      | manager1 | tilleuls | 1       |
      | manager2 | lac      | 1       |

  Scenario: A manager reads the management dashboard of their company from the IOMAD dashboard
    Given I log in as "manager1"
    And I visit "/blocks/iomad_company_admin/index.php"
    When I click on "Management dashboard of the company" "link"
    Then I should see "Clinique des Tilleuls" in the ".epure-management h2" "css_element"
    And I should see "The activity of the users of the company"
    And I should see "Last 30 days"
    And "#epure-management-companyid" "css_element" should not exist
    And the page should meet accessibility standards

  Scenario: An administrator chooses the company from the list
    Given I log in as "admin"
    And I visit "/theme/epure/management.php"
    And I should see "The activity of the platform"
    When I set the field "Dashboard of" to "Hôtel du Lac"
    And I press "Show"
    Then I should see "Hôtel du Lac" in the ".epure-management h2" "css_element"
    And the page should meet accessibility standards
