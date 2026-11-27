@theme @theme_epure @javascript @editor_tiny
Feature: The display preferences of « Aa » in the frames of the page
  In order to read every part of a page as I chose to
  As a user with display preferences
  I need them to apply in the frames of the page too: the editor, H5P, the files shown

  # TinyMCE, whose text is in a frame (the site of the tests may have Atto first, which has none).
  Scenario: The text being written in the editor takes the display preferences
    Given the following config values are set as admin:
      | theme       | epure         |
      | texteditors | tiny,textarea |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the following "user preferences" exist:
      | user     | preference              | value        |
      | learner1 | theme_epure_a11y_scheme | yellow       |
      | learner1 | theme_epure_a11y_font   | opendyslexic |
      | learner1 | theme_epure_a11y_text   | 150          |
      | learner1 | htmleditor              | tiny         |
    When I log in as "learner1"
    And I visit "/user/edit.php"
    And I wait until ".tox-edit-area__iframe" "css_element" exists
    Then the frames of the page should have the display preferences
