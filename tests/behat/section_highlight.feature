@format @format_tiles @format_tiles_section_highlight
Feature: Highlight and unhighlight tiles from the section menu

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | tiles  | 0             | 3           |
    And the following "activities" exist:
      | activity | name        | intro                   | course | section | idnumber |
      | page     | Test page 1 | Test page 1 description | C1     | 1       | page1    |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | config           | value | plugin       |
      | usejavascriptnav | 1     | format_tiles |

  @javascript
  Scenario: Highlight a tile without a page reload
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I set the tiles page-sentinel
    When I highlight tile "2"
    Then tile "2" should be the highlighted section
    And the tiles page-sentinel should still be set
    And the tile "2" menu action should now read "Unhighlight"

  @javascript
  Scenario: Unhighlight a highlighted tile
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I highlight tile "2"
    And tile "2" should be the highlighted section
    When I unhighlight tile "2"
    Then tile "2" should not be the highlighted section
    And the tile "2" menu action should now read "Highlight"

  @javascript
  Scenario: Highlighting another tile moves the marker
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I highlight tile "2"
    And tile "2" should be the highlighted section
    When I highlight tile "3"
    Then tile "3" should be the highlighted section
    And tile "2" should not be the highlighted section
    And the tile "2" menu action should now read "Highlight"
    And the tile "3" menu action should now read "Unhighlight"
