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
    And the description of the activity "act1" has an image without a text alternative
    When I log in as "admin"
    And I visit the Épure page "/theme/epure/courseaccessibility.php?id={course HYG}"
    Then I should see "Unit <modname>"
    And I should see "<guide>"

    # Reengagement is hidden from the learners by design (its form keeps it hidden): the guide does not read it.
    Examples:
      | modname              | guide                                  |
      | attendance           | Images without a text alternative: 1.  |
      | attendanceregister   | Images without a text alternative: 1.  |
      | booking              | Images without a text alternative: 1.  |
      | certificatebeautiful | Images without a text alternative: 1.  |
      | checklist            | Images without a text alternative: 1.  |
      | choicegroup          | Images without a text alternative: 1.  |
      | coursecertificate    | Images without a text alternative: 1.  |
      | customcert           | Images without a text alternative: 1.  |
      | dialogue             | Images without a text alternative: 1.  |
      | diary                | Images without a text alternative: 1.  |
      | facetoface           | Images without a text alternative: 1.  |
      | game                 | Images without a text alternative: 1.  |
      | geogebra             | Images without a text alternative: 1.  |
      | hotpot               | Images without a text alternative: 1.  |
      | hsuforum             | Images without a text alternative: 1.  |
      | icontent             | Images without a text alternative: 1.  |
      | interactivevideo     | Images without a text alternative: 1.  |
      | jitsi                | Images without a text alternative: 1.  |
      | journal              | Images without a text alternative: 1.  |
      | learningmap          | Images without a text alternative: 1.  |
      | lightboxgallery      | Images without a text alternative: 1.  |
      | mediagallery         | Images without a text alternative: 1.  |
      | mindmap              | Images without a text alternative: 1.  |
      | offlinequiz          | Images without a text alternative: 1.  |
      | organizer            | Images without a text alternative: 1.  |
      | pdfannotator         | Images without a text alternative: 1.  |
      | pdfprotect           | Images without a text alternative: 1.  |
      | publication          | Images without a text alternative: 1.  |
      | pulse                | Images without a text alternative: 1.  |
      | questionnaire        | Images without a text alternative: 1.  |
      | realtimequiz         | Images without a text alternative: 1.  |
      | reengagement         | Unit reengagement                      |
      | scheduler            | Images without a text alternative: 1.  |
      | simplecertificate    | Images without a text alternative: 1.  |
      | studentquiz          | Images without a text alternative: 1.  |
      | subcourse            | Images without a text alternative: 1.  |
      | supervideo           | Images without a text alternative: 1.  |
      | threesixo            | Images without a text alternative: 1.  |
      | unilabel             | Images without a text alternative: 1.  |
      | videotime            | Images without a text alternative: 1.  |
      | vpl                  | Images without a text alternative: 1.  |
      | zoom                 | Images without a text alternative: 1.  |

  # H5P (mod_hvp) cannot show an activity without its package of H5P, which the step does not create, nor Turnitin without
  # an account of the service; MS Teams calls a function that Moodle removed (url_print_heading); the scripts of Board and
  # Sticky Notes never tell Behat they are done (their pages are checked by hand, on a site with them): their page is not
  # checked here, the guide reads their description.
  Scenario Outline: The guide reads the description of the activity « <modname> », which cannot be shown here
    Given the activity module "<modname>" is installed
    And the following config values are set as admin:
      | courseaccessibility | 1 | theme_epure |
    And the following config values are set as admin:
      | theme | epure |
    And the following "courses" exist:
      | fullname | shortname | numsections |
      | Hygiene  | HYG       | 1           |
    And the activity "<modname>" "Unit <modname>" exists in "HYG" as "act1"
    And the description of the activity "act1" has an image without a text alternative
    When I log in as "admin"
    And I visit the Épure page "/theme/epure/courseaccessibility.php?id={course HYG}"
    Then I should see "Unit <modname>"
    And I should see "Images without a text alternative: 1."

    Examples:
      | modname         |
      | board           |
      | hvp             |
      | msteams         |
      | stickynotes     |
      | turnitintooltwo |
