@theme @theme_epure @javascript @accessibility
Feature: The management dashboard of the platform
  In order to follow the activity of the platform
  As an administrator or a manager of the site
  I need the indicators of the last 30 days, the courses most followed and the completion of the courses

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
      | manager1 | Maud      | Gestion  | manager1@example.com |
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  Scenario: An administrator reads the management dashboard from the reports
    Given I log in as "admin"
    When I navigate to "Reports > Management dashboard" in site administration
    Then I should see "Last 30 days"
    And I should see "New enrolments"
    And I should see "Safety at work" in the ".epure-management-table" "css_element"
    And I should see "1 enrolment in the courses that track completion:"
    And I should see "Not started: 1 (100 %)"
    And the page should meet accessibility standards, the audit waited for

  Scenario: A manager of the site reads it without configuring the site
    Given I log in as "manager1"
    When I visit "/theme/epure/management.php"
    Then I should see "Management dashboard"
    And I should see "Courses most followed"
    And the page should meet accessibility standards, the audit waited for
