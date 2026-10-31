@theme @theme_epure @theme_epure_iomad @theme_epure_preaudit @javascript @accessibility
Feature: Pre-audit of accessibility of the Épure theme on IOMAD
  In order to use the platform of my company whatever my disability, my screen or my display
  As a learner, a manager of a company or an administrator of IOMAD
  I need the pages of IOMAD to meet the WCAG 2.2 A and AA in light, in dark and on phones

  # The pages of IOMAD, in addition to the 40 pages of the pre-audit of the theme, each in the four displays, on
  # every version of IOMAD of the continuous integration.

  Background:
    Given IOMAD is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
      | manager1 | Gaëlle    | Gestion  | manager1@example.com |
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion |
      | Safety at work | SAFE      | 1                |
    And the following "activities" exist:
      | activity | course | name    | idnumber | completion |
      | page     | SAFE   | Welcome | page1    | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |
    And the following config values are set as admin:
      | mysummary | 1 | theme_epure |
    And the following IOMAD companies exist:
      | name                  | shortname | theme | headingcolor |
      | Clinique des Tilleuls | tilleuls  | epure | #36195F      |
    And the following IOMAD company users exist:
      | user     | company  | manager |
      | learner1 | tilleuls |         |
      | manager1 | tilleuls | 1       |

  Scenario: The pages of a learner of a company meet accessibility standards in the four displays
    Given I log in as "learner1"
    Then the following pages should meet accessibility standards in the four displays:
      | path                              |
      | /my/                              |
      | /my/courses.php                   |
      | /course/view.php?id={course SAFE} |
      | /mod/page/view.php?id={cm page1}  |
      | /user/profile.php                 |
      | /user/preferences.php             |
      | /theme/epure/summary.php          |

  Scenario: The pages of a manager of a company meet accessibility standards in the four displays
    Given I log in as "manager1"
    Then the following pages should meet accessibility standards in the four displays:
      | path                                                      | except     |
      | /my/                                                      |            |
      | /blocks/iomad_company_admin/index.php                     |            |
      | /blocks/iomad_company_admin/editusers.php                 |            |
      | /blocks/iomad_company_admin/company_user_create_form.php  |            |
      | /blocks/iomad_company_admin/company_departments.php       |            |
      | /blocks/iomad_company_admin/company_course_users_form.php |            |
      | /blocks/iomad_company_admin/company_license_list.php      |            |
      | /local/report_users/index.php                             |            |
      | /local/report_user_logins/index.php                       |            |
      | /local/report_completion_overview/index.php               | wide table |

  Scenario: The pages of IOMAD of an administrator meet accessibility standards in the four displays
    Given I log in as "admin"
    Then the following pages should meet accessibility standards in the four displays:
      | path                                                  |
      | /blocks/iomad_company_admin/index.php                 |
      | /blocks/iomad_company_admin/editcompanies.php         |
      | /blocks/iomad_company_admin/company_managers_form.php |
      | /blocks/iomad_company_admin/company_edit_form.php     |
      | /local/report_companies/index.php                     |
      | /local/report_license_usage/index.php                 |
      | /local/report_emails/index.php                        |
