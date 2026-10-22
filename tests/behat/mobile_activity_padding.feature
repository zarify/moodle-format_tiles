@format @format_tiles @format_tiles_mobile_padding
Feature: Activities sit flush with the tile edges on a narrow screen
  In order to see the activity name without wasted space
  As a user on a mobile device
  I need the activity item to have no horizontal padding

  Background:
    Given the following "users" exist:
      | username | firstname    | lastname  | email                 |
      | student1 | Student      | 1         | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category | numsections | initsections | format |
      | Course 1 | C1        | 0        | 3           | 1            | tiles  |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name    | course | idnumber | section |
      | page     | Page1   | C1     | page1    | 1       |

  @javascript
  Scenario: Activity padding is removed on a narrow screen
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on tile "1"
    And I wait until the page is ready
    And I change window size to "mobile"
    Then I should see "Page1" in the "region-main" "region"
    And the tiles activity item should have no padding
