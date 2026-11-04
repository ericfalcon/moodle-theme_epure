@theme @theme_epure @javascript @accessibility
Feature: Error pages of the Épure theme
  In order to know what happened and where to go
  As a user
  I need error pages that say in plain words what happened, with Moodle's message and a way back

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the users belong to an IOMAD company when IOMAD is installed
    And the following config values are set as admin:
      | theme | epure |

  Scenario: A page that does not exist leads back to the dashboard
    Given I log in as "learner1"
    Then the error page of "/mod/page/view.php?id=999999" should be a card "notfound" with the link "Dashboard" to "/my/"
    And the error page of "/error/index.php" should be a card "notfound" with the link "Dashboard" to "/my/"

  Scenario: A page the user may not see says so and leads back to the dashboard
    Given I log in as "learner1"
    Then the error page of "/cohort/index.php?contextid=1" should be a card "denied" with the link "Dashboard" to "/my/"

  Scenario: Logged out, the page not found leads to the login page
    Then the error page of "/error/index.php" should be a card "notfound" with the link "Log in" to "/login/index.php"
