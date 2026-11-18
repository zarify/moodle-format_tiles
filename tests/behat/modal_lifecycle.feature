@format @format_tiles @format_tiles_mod_modal @format_tiles_modal_lifecycle
Feature: Modal lifecycle regressions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |
      | Course 1 | C1-modal  | tiles  | 0             | 5           | 1                |
    And the following "activities" exist:
      | activity | name                    | intro                 | course   | idnumber | section | visible | completion | defaultfilename                             | uploaded |
      | page     | Test page name          | Test page description | C1-modal | page1    | 1       | 1       | 0          |                                             | 0        |
      | resource | Test PDF                | File description      | C1-modal | pdf1     | 1       | 1       | 1          | course/format/tiles/tests/fixtures/test.pdf | 1        |
      | resource | Slides "Intro" & notes' | File description      | C1-modal | pdf-name | 1       | 1       | 0          | course/format/tiles/tests/fixtures/test.pdf | 1        |
    And the following "course enrolments" exist:
      | user     | course   | role           |
      | student1 | C1-modal | student        |
      | teacher1 | C1-modal | editingteacher |
    And the following config values are set as admin:
      | config                 | value    | plugin       |
      | enablecompletion       | 1        | core         |
      | modalmodules           | page     | format_tiles |
      | modalresources         | pdf,html | format_tiles |
      | assumedatastoreconsent | 1        | format_tiles |
      | reopenlastsection      | 0        | format_tiles |
      | usejavascriptnav       | 1        | format_tiles |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then I should see "Test PDF"
    And I log out tiles

  @javascript
  Scenario: Opening the same activity link twice opens one modal
    When format_tiles subtiles are off for course "Course 1"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    When I click format tiles activity "Test page name" twice with no delay
    Then there should be "1" open tiles activity modals

  @javascript
  Scenario: The PDF modal header has a close button and no full width toggle
    When format_tiles subtiles are off for course "Course 1"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    And I click format tiles activity "Test PDF"
    And I wait until the page is ready
    And I wait "1" seconds
    Then the tiles activity modal header should have a close button and no full width toggle

  @javascript
  Scenario: The completion toggle keeps focus inside the PDF modal
    When format_tiles subtiles are off for course "Course 1"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    And I click format tiles activity "Test PDF"
    And I wait until the page is ready
    And I wait "1" seconds
    And format_tiles progress for "resource" called "Test PDF" in "Course 1" is "0" in the database
    When I click on "Mark as done" "button" in the "Test PDF" "dialogue"
    Then the tiles activity modal completion toggle should have focus

  @javascript
  Scenario: The PDF modal width stays stable while the height watcher runs
    When format_tiles subtiles are off for course "Course 1"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    And I click format tiles activity "Test PDF"
    And I wait until the page is ready
    And I wait "2" seconds
    And I remember the tiles activity modal width
    When I scroll the tiles activity modal body
    Then the tiles activity modal width should not have changed

  @javascript
  Scenario: Double clicking the icon picker opens one picker
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I wait "1" seconds
    And I double click the tiles icon picker for tile "1"
    Then there should be exactly one open icon picker

  @javascript
  Scenario: Activity names survive modal title and iframe text boundaries
    # The activity is addressed by idnumber because its name carries quote characters.
    When format_tiles subtiles are off for course "Course 1"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    And I click the format tiles activity with idnumber "pdf-name"
    And I wait until the page is ready
    Then the tiles activity modal title should read
      | Slides "Intro" & notes' |
    And the tiles activity modal iframe title should read
      | Slides "Intro" & notes' |

    When format_tiles subtiles are on for course "Course 1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    And I click the format tiles activity with idnumber "pdf-name"
    And I wait until the page is ready
    Then the tiles activity modal title should read
      | Slides "Intro" & notes' |
    And the tiles activity modal iframe title should read
      | Slides "Intro" & notes' |

    When I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on tile "1"
    And I wait until the page is ready
    And I click the tiles course index activity with idnumber "pdf-name"
    And I wait until the page is ready
    Then the tiles activity modal title should read
      | Slides "Intro" & notes' |
    And the tiles activity modal iframe title should read
      | Slides "Intro" & notes' |
