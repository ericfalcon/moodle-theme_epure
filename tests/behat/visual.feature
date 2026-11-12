@theme @theme_epure @theme_epure_visual @javascript
Feature: Visual regression of the Épure theme
  In order to change the styles of the theme without changing what the pages look like by mistake
  As the maintainer of the theme
  I need the pages to look like their reference images, pixel by pixel

  # One job of the continuous integration runs these scenarios (the variable EPURE_VISUAL names the folder of the
  # images): the fonts and the browser draw the pixels, which differ from a machine to another. The references are in
  # tests/behat/visual; a page without its reference is saved in the artifact « visual », to be added to them.
  # Everything is named and fixed (no date): the same page gives the same image from a run to another.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
      | teacher1 | Paul      | Durand   | teacher1@example.com |
    And the following "courses" exist:
      | fullname       | shortname | enablecompletion | startdate | enddate |
      | Safety at work | SAFE      | 1                | 0         | 0       |
    And the following "activities" exist:
      | activity | course | name         | idnumber | completion | intro                                      |
      | page     | SAFE   | Welcome      | page1    | 1          | The rules of safety in the workshop.       |
      | forum    | SAFE   | Questions    | forum1   | 0          | Ask your questions here.                   |
      | assign   | SAFE   | Your plan    | assign1  | 0          | Write your plan of prevention.             |
    # The answers in a fixed order.
    And the following "activities" exist:
      | activity | course | name       | idnumber | intro                                      | shuffleanswers |
      | quiz     | SAFE   | Final test | quiz1    | Ten minutes to check what you have learnt. | 0              |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | SAFE   | student        |
      | teacher1 | SAFE   | editingteacher |
    And the following config values are set as admin:
      | theme            | epure |
      | enablecompletion | 1     |

  Scenario: The pages of a visitor and of a learner look like their references
    Given the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name       | template    |
      | Test questions   | multichoice | One choice | one_of_four |
      | Test questions   | ddwtos      | Drag words | fox         |
    And quiz "Final test" contains the following questions:
      | question   | page |
      | One choice | 1    |
      | Drag words | 1    |
    And user "learner1" has started an attempt at quiz "Final test"
    Then the following pages should look like their references:
      | name           | path             | display    |
      | login          | /login/index.php | light 1300 |
      | login-phone    | /login/index.php | light 390  |
    And I log in as "learner1"
    And the following pages should look like their references:
      | name                | path                                                       | display    | mask                       |
      | course              | /course/view.php?id={course SAFE}                          | light 1300 |                            |
      | course-dark         | /course/view.php?id={course SAFE}                          | dark 1300  |                            |
      | course-phone        | /course/view.php?id={course SAFE}                          | light 390  |                            |
      | page                | /mod/page/view.php?id={cm page1}                           | light 1300 |                            |
      | quiz-attempt        | /mod/quiz/attempt.php?attempt={attempt quiz1}              | light 1300 | #quiz-timer-wrapper        |
      | quiz-attempt-dark   | /mod/quiz/attempt.php?attempt={attempt quiz1}              | dark 1300  | #quiz-timer-wrapper        |
      | forum-post          | /mod/forum/post.php?forum={instance forum1}                | light 1300 |                            |
      | assign-submission   | /mod/assign/view.php?id={cm assign1}&action=editsubmission | light 1300 | .tox-statusbar__wordcount  |
      | preferences         | /user/preferences.php                                      | light 1300 |                            |
      | preferences-dark    | /user/preferences.php                                      | dark 1300  |                            |

  Scenario: The pages of a teacher look like their references
    Given I log in as "teacher1"
    Then the following pages should look like their references:
      | name             | path                                        | display    |
      | teacher-course   | /course/view.php?id={course SAFE}           | light 1300 |
      | participants     | /user/index.php?id={course SAFE}            | light 1300 |
      | gradebook-setup  | /grade/edit/tree/index.php?id={course SAFE} | light 1300 |
      | course-settings  | /course/edit.php?id={course SAFE}           | light 1300 |
