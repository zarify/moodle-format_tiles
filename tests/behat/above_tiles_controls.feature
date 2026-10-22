@format @format_tiles @format_tiles_above_tiles_controls
Feature: Above tiles controls

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | tiles  | 0             | 3           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following config values are set as admin:
      | config                 | value | plugin       |
      | usejavascriptnav       | 1     | format_tiles |
      | highcontrastmodeallow  | 1     | format_tiles |
      | reopenlastsection      | 0     | format_tiles |

  @javascript
  Scenario: Controls are rendered as switches wired to their toggle URLs
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    Then the format tiles above tiles controls should be complete
    And the above tiles and section zero containers should share the multi-section page parent

  @javascript
  Scenario: Toggling animated navigation navigates and persists
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    Then the "animatednav" tiles control should be checked
    When I toggle the "animatednav" tiles control
    And I wait until the page is ready
    Then the "animatednav" tiles control should not be checked
    When I toggle the "animatednav" tiles control
    And I wait until the page is ready
    Then the "animatednav" tiles control should be checked
