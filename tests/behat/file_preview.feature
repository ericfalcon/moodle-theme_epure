@theme @theme_epure @javascript @accessibility
Feature: The files of a course open in a preview, without leaving the course
  In order to read the documents of a course quickly
  As a learner
  I need the file of a resource in a window on the page of the course, with the previous and next files

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Camille   | Martin   | learner1@example.com |
    And the following "courses" exist:
      | fullname       | shortname |
      | Safety at work | SAFE      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | SAFE   | student |
    And the following "activities" exist:
      | activity | course | name           | defaultfilename | display |
      | resource | SAFE   | Reading notes  | notes.txt       | 5       |
      | resource | SAFE   | Safety rules   | rules.txt       | 5       |
      | resource | SAFE   | Printed sheet  | sheet.txt       | 4       |
    And the following "activities" exist:
      | activity | course | name           | defaultfilename                              | uploaded | display |
      | resource | SAFE   | Workshop rules | theme/epure/tests/fixtures/safety_rules.docx | 1        | 5       |
    And the following config values are set as admin:
      | theme | epure |
    # IOMAD sends a user without company away from the courses.
    And the users belong to an IOMAD company when IOMAD is installed

  Scenario: A learner opens a file, goes to the next one and closes the preview
    Given I log in as "learner1"
    And I am on "Safety at work" course homepage
    When I click on "Reading notes" "link" in the "region-main" "region"
    Then "dialog.epure-preview" "css_element" should be visible
    And I should see "Reading notes" in the "dialog.epure-preview" "css_element"
    And I should see "File 1 of 3" in the "dialog.epure-preview" "css_element"
    And "Previous" "button" should not be visible
    And the page should meet accessibility standards
    And I click on "Next" "button" in the "dialog.epure-preview" "css_element"
    And I should see "Safety rules" in the ".epure-preview-title" "css_element"
    And I should see "File 2 of 3" in the "dialog.epure-preview" "css_element"
    And "Previous" "button" in the "dialog.epure-preview" "css_element" should be visible
    And I press the escape key
    And "dialog.epure-preview" "css_element" should not be visible
    And I should see "Printed sheet" in the "region-main" "region"

  Scenario: A learner reads the text of a document of Word in the preview
    Given I log in as "learner1"
    And I am on "Safety at work" course homepage
    When I click on "Workshop rules" "link" in the "region-main" "region"
    Then I should see "File 3 of 3" in the "dialog.epure-preview" "css_element"
    And I should see "Text version of the document" in the "dialog.epure-preview" "css_element"
    And I should see "Protective equipment" in the "dialog.epure-preview h4" "css_element"
    And I should see "North door" in the "dialog.epure-preview table" "css_element"
    And the page should meet accessibility standards
