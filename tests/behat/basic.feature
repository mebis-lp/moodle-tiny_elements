@editor  @tiny @editor_tiny  @tiny_elements
Feature: Tiny editor components for learning
  Write text with the elements editor plugin
  Background:
    Given the following "courses" exist:
        | shortname | fullname |
        | C1        | Course 1 |
    And the following "users" exist:
        | username | firstname | lastname | email                |
        | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
        | activity | name      | intro     | introformat | course | contentformat | idnumber |
        | page     | PageName1 | PageDesc1 | 1           | C1     | 1             | 1        |
  @javascript @external
  Scenario: TinyMCE can be used to embed an Elements Content
    And I am on the PageName1 "page activity editing" page logged in as admin
    And I click on the "Elements" button for the "Page content" TinyMCE editor
    And I click on "Tip" "button"
    And I press "Save and display"
    And I should see "Lorem ipsum"
