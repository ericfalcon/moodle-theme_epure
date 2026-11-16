@theme @theme_epure @javascript @accessibility
Feature: How many questions are answered during a quiz attempt
  In order to know what is left before I submit a quiz
  As a learner
  I need the panel of the questions to tell how many questions are answered

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
    And the users belong to an IOMAD company when IOMAD is installed
    And the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following "activities" exist:
      | activity | course | name        | idnumber |
      | quiz     | SAFE   | Safety test | quiz1    |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext            |
      | Test questions   | truefalse | TF1  | Gloves protect hands.   |
      | Test questions   | truefalse | TF2  | Helmets protect knees.  |
    And quiz "Safety test" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 2    |

  Scenario: The panel of the questions tells how many questions are answered
    Given I am on the "Safety test" "quiz activity" page logged in as "learner1"
    When I press "Attempt quiz"
    Then "//*[contains(@class, 'epure-quiz-progress-text')][normalize-space(.) = '0 of 2 questions answered']" "xpath_element" should exist
    And I click on "True" "radio" in the "Gloves protect hands." "question"
    And I press "Next page"
    And "//*[contains(@class, 'epure-quiz-progress-text')][normalize-space(.) = '1 of 2 questions answered']" "xpath_element" should exist
    And I click on "False" "radio" in the "Helmets protect knees." "question"
    And I press "Previous page"
    And ".epure-quiz-progress-done" "css_element" should exist
    And "//*[contains(@class, 'epure-quiz-progress-text')][normalize-space(.) = '2 of 2 questions answered']" "xpath_element" should exist
    And the page should meet accessibility standards
