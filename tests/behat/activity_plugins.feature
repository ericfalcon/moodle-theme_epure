@theme @theme_epure @javascript @accessibility
Feature: The activities of other plugins read by the guide, with the Épure theme
  In order to follow and to fix the activities of other plugins as those of Moodle
  As a learner and as a teacher
  I need the page of each activity to be accessible in every display, and the guide to read what the teacher wrote

  # The 47 modules of other plugins most installed that the guide « Accessibility of the course » knows
  # (theme_epure\content_sources::KNOWN). They are added to some jobs of the continuous integration, each on the versions of
  # Moodle its branch supports (.github/workflows/ci.yml, « activities »): elsewhere, the scenario is skipped.
  Scenario Outline: The activity « <modname> » meets accessibility standards, and the guide reads its description
    Given the activity module "<modname>" is installed
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Lea       | Learner  | learner1@example.com |
    And the following config values are set as admin:
      | courseaccessibility | 1 | theme_epure |
    And the following config values are set as admin:
      | theme | epure |
    And the following "courses" exist:
      | fullname | shortname | numsections |
      | Hygiene  | HYG       | 1           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | HYG    | student |
    And the activity "<modname>" "Unit <modname>" exists in "HYG" as "act1"
    When I log in as "learner1"
    Then the following pages should meet accessibility standards in every display:
      | path                                 |
      | /mod/<modname>/view.php?id={cm act1} |
    And I log out
    When I log in as "admin"
    And I visit the Épure page "/theme/epure/courseaccessibility.php?id={course HYG}"
    Then I should see "Unit <modname>"
    And I should see "Images without a text alternative: 1."

    Examples:
      | modname              |
      | attendance           |
      | attendanceregister   |
      | board                |
      | booking              |
      | certificatebeautiful |
      | checklist            |
      | choicegroup          |
      | coursecertificate    |
      | customcert           |
      | dialogue             |
      | diary                |
      | facetoface           |
      | game                 |
      | geogebra             |
      | hotpot               |
      | hsuforum             |
      | hvp                  |
      | icontent             |
      | interactivevideo     |
      | jitsi                |
      | journal              |
      | learningmap          |
      | lightboxgallery      |
      | mediagallery         |
      | mindmap              |
      | msteams              |
      | offlinequiz          |
      | organizer            |
      | pdfannotator         |
      | pdfprotect           |
      | publication          |
      | pulse                |
      | questionnaire        |
      | realtimequiz         |
      | reengagement         |
      | scheduler            |
      | simplecertificate    |
      | stickynotes          |
      | studentquiz          |
      | subcourse            |
      | supervideo           |
      | threesixo            |
      | turnitintooltwo      |
      | unilabel             |
      | videotime            |
      | vpl                  |
      | zoom                 |
