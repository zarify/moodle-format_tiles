@format @format_tiles @format_tiles_mod_modal @format_tiles_url_modal_student
Feature: Student can open a URL activity in a modal

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |
      | Course 1 | C1-url    | tiles  | 0             | 5           | 1                |
    And the following "activities" exist:
      | activity | name     | intro                 | course | idnumber | section | visible |
      | url      | Test url | Test url description  | C1-url | url1     | 1       | 1       |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1-url | student |
    And the following config values are set as admin:
      | config                 | value    | plugin       |
      | enablecompletion       | 1        | core         |
      | modalmodules           | url      | format_tiles |
      | assumedatastoreconsent | 1        | format_tiles |
      | reopenlastsection      | 0        | format_tiles |
      | usejavascriptnav       | 1        | format_tiles |

  @javascript
  Scenario: Open a URL activity in a modal as student
    When format_tiles subtiles are off for course "Course 1"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    And I click format tiles activity "Test url"
    And I wait until the page is ready
    Then "Test url" "dialogue" should be visible
    And I click on "Close" "button" in the "Test url" "dialogue"
    And I wait until the page is ready
    And "Test url" "dialogue" should not be visible
