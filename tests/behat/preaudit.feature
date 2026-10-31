@theme @theme_epure @theme_epure_preaudit @javascript @accessibility
Feature: Pre-audit of accessibility of the Épure theme
  In order to use the platform whatever my disability, my screen or my display
  As a visitor, a learner, a teacher or an administrator
  I need every page to meet the WCAG 2.2 A and AA in light, in dark and on phones, without scrolling across

  # The same 40 pages, each in the four displays (160 checks), on every version of Moodle and IOMAD and with every
  # version of PHP of the continuous integration.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
      | teacher1 | Paul      | Durand   | teacher1@example.com |
    And the following "courses" exist:
      | fullname        | shortname | enablecompletion |
      | Safety at work  | SAFE      | 1                |
      | Managing a team | TEAM      | 1                |
    And the following "activities" exist:
      | activity | course | name            | idnumber  | completion |
      | page     | SAFE   | Welcome         | page1     | 1          |
      | forum    | SAFE   | Questions       | forum1    | 0          |
      | quiz     | SAFE   | Final test      | quiz1     | 1          |
      | book     | SAFE   | Instructions    | book1     | 1          |
      | lesson   | SAFE   | First aid       | lesson1   | 1          |
      | glossary | SAFE   | Words of safety | glossary1 | 0          |
    And the following "activities" exist:
      | activity | course | name            | idnumber | duedate        | assignsubmission_onlinetext_enabled |
      | assign   | SAFE   | Risk assessment | assign1  | ##3 days ago## | 1                                   |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | SAFE   | student        |
      | teacher1 | SAFE   | editingteacher |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |
    And the following config values are set as admin:
      | mysummary  | 1                     | theme_epure |
      | a11ystatus | partial               | theme_epure |
      | a11yentity | Clinique des Tilleuls | theme_epure |

  Scenario: The pages of a visitor meet accessibility standards in the four displays
    Then the following pages should meet accessibility standards in the four displays:
      | path                           |
      | /login/index.php               |
      | /theme/epure/accessibility.php |

  Scenario: The pages of a learner meet accessibility standards in the four displays
    Given I log in as "learner1"
    Then the following pages should meet accessibility standards in the four displays:
      | path                                          |
      | /my/                                          |
      | /my/courses.php                               |
      | /course/index.php                             |
      | /course/view.php?id={course SAFE}             |
      | /mod/page/view.php?id={cm page1}              |
      | /mod/assign/view.php?id={cm assign1}          |
      | /mod/forum/view.php?id={cm forum1}            |
      | /mod/quiz/view.php?id={cm quiz1}              |
      | /mod/book/view.php?id={cm book1}              |
      | /mod/lesson/view.php?id={cm lesson1}          |
      | /mod/glossary/view.php?id={cm glossary1}      |
      | /enrol/index.php?id={course TEAM}             |
      | /calendar/view.php?view=month                 |
      | /grade/report/overview/index.php              |
      | /grade/report/user/index.php?id={course SAFE} |
      | /badges/mybadges.php                          |
      | /user/profile.php                             |
      | /user/preferences.php                         |
      | /message/index.php                            |
      | /course/search.php?search=safety              |
      | /theme/epure/accessibility.php                |
      | /theme/epure/summary.php                      |

  Scenario: The pages of a teacher meet accessibility standards in the four displays
    Given I log in as "teacher1"
    Then the following pages should meet accessibility standards in the four displays:
      | path                                                | except     |
      | /my/courses.php                                     |            |
      | /course/view.php?id={course SAFE}                   |            |
      | /user/index.php?id={course SAFE}                    |            |
      | /grade/report/grader/index.php?id={course SAFE}     | wide table |
      | /mod/assign/view.php?id={cm assign1}&action=grading |            |
      | /course/edit.php?id={course SAFE}                   |            |
      | /course/modedit.php?update={cm page1}               |            |

  Scenario: The pages of an administrator meet accessibility standards in the four displays
    Given I log in as "admin"
    Then the following pages should meet accessibility standards in the four displays:
      | path                                          |
      | /my/                                          |
      | /admin/search.php                             |
      | /admin/category.php?category=appearance       |
      | /admin/settings.php?section=themesettingepure |
      | /admin/settings.php?section=htmlsettings      |
      | /admin/user.php                               |
      | /course/management.php                        |
      | /theme/epure/a11ystatement.php                |
      | /theme/epure/certificate.php                  |
