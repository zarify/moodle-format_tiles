<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions related to Format tiles
 *
 * @package    format_tiles
 * @category   test
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Format tiles related steps definitions.
 *
 * @package    format_tiles
 * @category   test
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_format_tiles extends behat_base {
    /**
     * Set course format option for subtiles on for course.
     *
     * @Given /^format_tiles subtiles are on for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $coursefullname
     * @throws dml_exception
     */
    public function format_tiles_sub_tiles_are_on_for_course($coursefullname) {
        $this->sub_tiles_on_off($coursefullname, 1);
    }


    /**
     * Check a tile has expected colour (bg and border top).
     * @Given /^Tile "(?P<tilenumber_int>(?:[\d]|\\")*)" has colour "(?P<colour_string>(?:[^"]|\\")*)"$/
     * @param int $tilenumber
     * @param string $expectedvalue
     * @return void
     */
    public function format_tiles_tile_has_colour(int $tilenumber, string $expectedvalue) {
        $selector = "#tile-$tilenumber";
        $property = 'border-top-color';
        $value = $this->element_get_css_value($selector, $property);

        // Expected value will be in rgb format like "22, 112, 204".
        // We ignore the opacity since if the element happens to be on hover during behat test, opacity will be < 1.
        // Actual value may be like rgb(22, 112, 204) rgba(22, 112, 204, 0.5) and both are ok.
        $pattern = '/rgba?\(' . $expectedvalue . '(\)|, \d\.\d\))$/';
        if (!preg_match($pattern, $value)) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "The property '$property' for the selector '$selector' is '$value' not '$expectedvalue'",
                $this->getSession()
            );
        }

        $selector = "#tile-$tilenumber .tile-bg";
        $property = 'background-color';
        $value = $this->element_get_css_value($selector, $property);
        $expectedvaluebg = "rgba($expectedvalue, 0.05)";
        if ($value != $expectedvaluebg) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "The property '$property' for the selector '$selector' is '$value' not '$expectedvaluebg'",
                $this->getSession()
            );
        }

        $jsscript = "(() => {return document.querySelector('style#format-tiles-dynamic-css') !== null;})();";
        $stylesincluded = $this->getSession()->evaluateScript($jsscript);
        if (!$stylesincluded) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Style tag for dynamic styles not found",
                $this->getSession()
            );
        }
    }

    /**
     * Check a tile has expected icon.
     * @Given /^Tile "(?P<tilenumber_int>(?:[\d]|\\")*)" should have icon "(?P<icon_string>(?:[^"]|\\")*)"$/
     * @param int $tilenumber
     * @param string $icon
     * @return void
     */
    public function format_tiles_tile_has_icon(int $tilenumber, string $icon) {
        $script = "(() => {return document.querySelector('#tileicon_$tilenumber i').classList.contains('fa-$icon');})();";
        $result = $this->getSession()->evaluateScript($script);
        if (!$result) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Icon $icon not found on tile $tilenumber",
                $this->getSession()
            );
        }
    }

    /**
     * Check if JS config <div> appears on page.
     * @Given /^Tiles JS config element exists on page$/
     * @return void
     */
    public function format_tiles_js_config_exists_on_page() {
        $script = "(() => {return document.getElementById('format-tiles-js-config') !== null;})()";
        $result = $this->getSession()->evaluateScript($script);
        if (!$result) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Tiles JS config div not found",
                $this->getSession()
            );
        }
    }

    /**
     * Get a CSS property for an element.
     *
     * @param string $selector e.g. '#tile-1'
     * @param string $property which CSS property e.g. 'border-top-color'
     * @return string $value
     */
    private function element_get_css_value(string $selector, string $property): string {
        $script = "(() => {
            const elem = document.querySelector('$selector');
            return elem !== null ? window.getComputedStyle(elem).getPropertyValue('$property') : null;
        })();";
        $result = $this->getSession()->evaluateScript($script);
        if (gettype($result) == 'string') {
            return $result;
        }
        throw new \Behat\Mink\Exception\ExpectationException(
            "Error getting CSS property '$property' for element '$selector'",
            $this->getSession()
        );
    }

    /**
     * * Set course format option for subtiles off for course.
     *
     * @Given /^format_tiles subtiles are off for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $coursefullname
     * @throws dml_exception
     */
    public function format_tiles_sub_tiles_are_off_for_course($coursefullname) {
        $this->sub_tiles_on_off($coursefullname, 0);
    }

    /**
     * Set course format option for subtiles on or off for course.
     * @param string $coursefullname
     * @param int $onoff
     * @throws dml_exception
     */
    private function sub_tiles_on_off($coursefullname, $onoff) {
        global $DB;
        $onoff = $onoff ? 1 : 0;
        $courseid = $DB->get_field('course', 'id', ['fullname' => $coursefullname], MUST_EXIST);
        $courseformat = course_get_format($courseid);
        $courseformat->update_course_format_options(['id' => $courseid, 'courseusesubtiles' => $onoff]);
    }

    // @codingStandardsIgnoreStart.
    /**
     * Set the course format option for the progress indicator for a course as percent or fraction.
     *
     * @Given /^format_tiles progress indicator is showing as "(?P<progresstype_string>(?:[^"]|\\")*)" for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $progresstype
     * @param string $coursefullname
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws dml_exception
     */
    public function progress_indicator_showing_as($progresstype, $coursefullname) {
        // @codingStandardsIgnoreEnd.
        global $DB;
        if (strtolower($progresstype) == 'percent') {
            $numerictype = 2;
        } else if (strtolower($progresstype) == 'numeric') {
            $numerictype = 1;
        } else {
            throw new \Behat\Mink\Exception\ExpectationException("Indicator type must be percent or numeric", $this->getSession());
        }
        $courseid = $DB->get_field('course', 'id', ['fullname' => $coursefullname], MUST_EXIST);
        $courseformat = course_get_format($courseid);
        $courseformat->update_course_format_options(['id' => $courseid, 'courseshowtileprogress' => $numerictype]);
    }

    // @codingStandardsIgnoreStart.
    /**
     * For a given page, check that its progress indicator shows a certain value (i.e. complete or not).
     *
     * @Then /^format_tiles progress for "(?P<modtype_string>(?:[^"]|\\")*)" called "(?P<activitytitle_string>(?:[^"]|\\")*)" in "(?P<coursefullname_string>(?:[^"]|\\")*)" is "(?P<value>\d+)" in the database$/
     * @param string $modtype
     * @param string $activitytitle
     * @param string $coursefullname
     * @param int $value
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function progress_indicator_for_page_in_is_set_to($modtype, $activitytitle, $coursefullname, $value) {
        // @codingStandardsIgnoreEnd.
        global $DB;
        $user = $this->get_session_user();
        $courseid = $DB->get_field('course', 'id', ['fullname' => $coursefullname], MUST_EXIST);
        $modinfo = get_fast_modinfo($courseid);
        $cminfos = $modinfo->get_instances_of($modtype);
        $cms = [];
        foreach ($cminfos as $cminfo) {
            $cms[$cminfo->name] = $cminfo->id;
        }
        $this->wait_for_pending_js(); // Wait for AJAX request to complete.
        $this->getSession()->wait(1500);
        if (!isset($cms[$activitytitle])) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Activity type '$modtype' title '$activitytitle' not found in $coursefullname."
                        . "Available cms " . json_encode(array_keys($cms)),
                $this->getSession()
            );
        }
        $completionstate = $DB->get_field(
            'course_modules_completion',
            'completionstate',
            ['coursemoduleid' => $cms[$activitytitle], 'userid' => $user->id]
        );
        if (($completionstate == $value) || (!$completionstate && !$value)) {
            return;
        } else if ($completionstate === false) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Completion state should be $value but no record found for cmid $cms[$activitytitle] title $activitytitle",
                $this->getSession()
            );
        } else {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Completion state should be " . $value
                . " but found '" . $completionstate
                . "' for cm type " . $modtype . ' title ' . $activitytitle . ' cmid ' . $cms[$activitytitle],
                $this->getSession()
            );
        }
    }

    /**
     * Check that a named activity is dimmed.
     *
     * @Then /^activity in format tiles is dimmed "(?P<activityname_string>(?:[^"]|\\")*)"$/
     * @param string $activityname
     * @return bool
     */
    public function activity_in_format_tiles_is_dimmed($activityname) {
        $activityname = behat_context_helper::escape($activityname);
        // Var $xpath is to find the li (the ancestor) which contains an element where the text is activity name.
        $xpath = "//text()[contains(.," . $activityname . ")]/ancestor::*[self::li][1]";
        $activitynode = $this->find('xpath', $xpath, false);
        return $activitynode->hasClass('dimmed');
    }

    /**
     * Check that a named activity is not dimmed.
     *
     * @Then /^activity in format tiles is not dimmed "(?P<activityname_string>(?:[^"]|\\")*)"$/
     * @param string $activityname
     * @return bool
     */
    public function activity_in_format_tiles_is_not_dimmed($activityname) {
        return !$this->activity_in_format_tiles_is_dimmed($activityname);
    }

    /**
     * I click a tile (to open it)
     *
     * @Given /^I click on tile "(?P<tilenumber>\d+)"$/
     * @param string $tileumber
     * @throws Exception
     */
    public function i_click_on_tile($tileumber) {
        $tileid = behat_context_helper::escape("sectionlink-" . $tileumber);

        // Click the tile.
        $this->execute("behat_general::i_click_on", ["//a[@id=" . $tileid . "]", "xpath_element"]);
        $this->getSession()->wait(1500); // Important to wait here as page is scrolling and might click wrong thing after.
        $this->wait_for_pending_js(); // Wait for AJAX request to complete.
    }

    /**
     * I toggle expand or collapse section for edit
     *
     * @Given /^I toggle expand or collapse section "(?P<tilenumber>\d+)" for edit$/
     * @param string $tileumber
     * @throws Exception
     */
    public function i_toggle_expand_collapse_section_for_edit($tileumber) {
        global $DB;
        // The toggle id embeds the section id, not the tile number, so resolve it from the course sections.
        $courseid = $this->get_tiles_current_course_id();
        $sectionid = $DB->get_field('course_sections', 'id', [
            'course' => $courseid,
            'section' => (int)$tileumber,
        ], MUST_EXIST);
        $tileid = behat_context_helper::escape("collapsesectionid" . $sectionid);

        // Click the tile.
        $this->wait_for_pending_js();
        $this->getSession()->wait(1500);  // Just in case we did a collapse all - wait a bit.
        $this->execute("behat_general::i_click_on", ["//a[@id=" . $tileid . "]", "xpath_element"]);
        $this->wait_for_pending_js(); // Wait for AJAX request to complete.
        $this->getSession()->wait(3000); // Important to wait here as section is expanding with transition.
    }

    /**
     * I click a tile (to close it)
     * This is using the button at the top right of the content bearing section.
     *
     * @Given /^I click on close button for tile "(?P<tilenumber>\d+)"$/
     * @param string $tilenumber
     * @throws Exception
     */
    public function i_click_tile_close_button($tilenumber) {
        $tileid = behat_context_helper::escape("closesectionbtn-" . $tilenumber);

        // Click the button.
        $this->wait_for_pending_js();
        $this->execute("behat_general::i_click_on", ["//button[@id=" . $tileid . "]", "xpath_element"]);
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->getSession()->wait(2000);
        $this->wait_for_pending_js(); // Wait for AJAX request to complete.
    }
    // @codingStandardsIgnoreStart.
    /**
     * I wait until a certain activity is visible following AJAX load.
     *
     * @Given /^I wait until activity "(?P<activitytitle_string>(?:[^"]|\\")*)" exists in "(?P<format_string>(?:[^"]|\\")*)" format$/
     * @param string $activitytitle
     * @param string $format
     * @throws Exception
     */
    public function wait_until_activity_exists_in_format($activitytitle, $format) {
        // @codingStandardsIgnoreEnd.
        if ($format == 'subtile' || $format == 'subtiles') {
            $liclass = 'subtile';
        } else if ($format == 'non-subtile') {
            $liclass = 'activity';
        } else {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Invalid activity format - must be subtile or non-subtile',
                $this->getSession()
            );
        }
        // We wait until the AJAX request finishes and the activity is visible.
        // xpath is to find the li (the ancestor) which contains an element where the text is activity name.
        $xpath = "//text()[contains(.,'" . $activitytitle . "')]/ancestor::li[contains(@class, '" . $liclass . "')]";
        $this->wait_for_pending_js();
        $this->execute(
            "behat_general::wait_until_exists",
            [$this->escape($xpath), "xpath_element"]
        );
    }

    /**
     * I click a certain activity.
     *
     * @Given /^I click format tiles activity "(?P<activityname_string>(?:[^"]|\\")*)"$/
     * @param string $activityname
     * @throws Exception
     */
    public function click_format_tiles_activity($activityname) {
        // As the open tile overlay is moved when page is ready, add a short pause to ensure that is complete.
        $this->wait_for_pending_js();
        $this->getSession()->wait(100);
        $this->execute("behat_general::i_click_on_in_the", [$this->escape($activityname), 'link', '#page-content', 'css_element']);
    }

    /**
     * I click a tile's progress indicator.
     *
     * @Given /^I click format tiles progress indicator for "(?P<activitytitle_string>(?:[^"]|\\")*)"$/
     * @param string $activitytitle
     * @throws Exception
     */
    public function i_click_progress_indicator_for($activitytitle) {
        $selector = "button[data-action=toggle-manual-completion][data-activityname='{$activitytitle}']";
        $this->execute("behat_general::i_click_on", [$selector, "css_element"]);
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->wait_for_pending_js();  // Important to wait for pending JS here so as await AJAX response.
    }

    /**
     * I click a sub-tile's progress indicator.
     *
     * @Given /^I click format tiles subtile progress indicator for "(?P<activitytitle_string>(?:[^"]|\\")*)"$/
     * @param string $activitytitle
     * @throws Exception
     */
    public function i_click_subtile_progress_indicator_for($activitytitle) {
        $selector = "button[data-action=tiles-toggle-manual-completion-subtile][data-activityname='{$activitytitle}']";
        $this->execute("behat_general::i_click_on", [$selector, "css_element"]);
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->wait_for_pending_js();  // Important to wait for pending JS here so as await AJAX response.
    }

    /**
     * Progress Indicator for tile shows correct out of values e.g. 1 / 2 complete.
     *
     * @Given /^format_tiles progress indicator for tile "(?P<tilenumber>\d+)" is "(?P<numcomplete>\d+)" out of "(?P<outof>\d+)"$/
     * @param string $tilenumber
     * @param string $numcomplete
     * @param string $outof
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function progress_indicator_tile_shows_outof($tilenumber, $numcomplete, $outof) {
        $xpath = "//div[@id='tileprogress-" . $tilenumber . "']";
        $node = $this->get_selected_node("xpath_element", $xpath);
        if ($node->getAttribute('data-numcomplete') !== $numcomplete) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Tile ' . $tilenumber . ': Expected number complete ' . $numcomplete
                . ' but found ' . $node->getAttribute('data-numcomplete'),
                $this->getSession()
            );
        }
        if ($node->getAttribute('data-numoutof') !== $outof) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Tile ' . $tilenumber . ': Expected number out of ' . $numcomplete
                . ' but found ' . $node->getAttribute('data-numoutof'),
                $this->getSession()
            );
        }
    }

    /**
     * Checks if the course section exists.
     *
     * @param int $sectionnumber
     * @return string The xpath of the section.
     */
    protected function tile_exists($sectionnumber) {

        // Just to give more info in case it does not exist.
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $exception = new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), "Tile $sectionnumber ");
        $this->find('xpath', $xpath, $exception);

        return $xpath;
    }

    /**
     * Hides the specified visible tile. You need to be in the course page and on editing mode.
     *
     * @Given /^I hide tile "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     * @throws coding_exception
     */
    public function i_hide_tile($sectionnumber) {
        // Ensures the section exists.
        $xpath = $this->tile_exists($sectionnumber);
        $this->i_show_hide($sectionnumber, 'hide', $xpath);
    }

    /**
     * Hides the specified visible tile. You need to be in the course page and on editing mode.
     *
     * @Given /^I show tile "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     * @throws coding_exception
     */
    public function i_show_tile($sectionnumber) {
        // Ensures the section exists.
        $xpath = $this->tile_exists($sectionnumber);
        $this->i_show_hide($sectionnumber, 'showfromothers', $xpath);
    }

    /**
     * Show or hide a certain tile.
     *
     * @param int $sectionnumber
     * @param string $showhide
     * @param string $xpath
     * @throws coding_exception
     */
    private function i_show_hide($sectionnumber, $showhide, $xpath) {

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $fullxpath = $xpath
                . "/descendant::div[contains(@class, 'section-actions')]/descendant::a[contains(@data-bs-toggle, 'dropdown')]";
            $exception = new \Behat\Mink\Exception\ExpectationException(
                'Tile "' . $sectionnumber . '" edit menu was not found',
                $this->getSession()
            );
            $menu = $this->find('xpath', $fullxpath, $exception);
            $menu->click();
        }

        // Click on show/hide link.
        $strhide = get_string($showhide, 'format_tiles');
        $this->execute(
            'behat_general::i_click_on_in_the',
            [$strhide, "link", $this->escape($xpath), "xpath_element"]
        );

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);
        }
    }

    /**
     * Logs out of the system.
     * Copied from behat_auth to add delay as sometimes not working.
     *
     * @Given /^I log out tiles$/
     */
    public function i_log_out_tiles() {

        // Wait for page to be loaded.
        $this->wait_for_pending_js();
        $this->getSession()->wait(1000); // Additional wait.

        // Click on logout link in footer, as it's much faster.
        $this->execute('behat_general::i_click_on_in_the', [get_string('logout'), 'link', '#page-footer', "css_element"]);
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given /^course "(?P<course_name>(?:[^"]|\\")*)" tile "(?P<section_number>\d+)" should show photo "(?P<photo_name>(?:[^"]|\\")*)"$/
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $coursename
     * @param int $sectionnumber
     * @param string $photoname
     * @return bool
     */
    public function tile_should_show_photo($coursename, $sectionnumber, $photoname) {
        // @codingStandardsIgnoreEnd.
        global $CFG, $DB;
        $courseid = $DB->get_field('course', 'id', ['fullname' => $coursename], IGNORE_MISSING);
        if (!$courseid) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Cannot find course ID for course $coursename",
                $this->getSession()
            );
        }
        $context = context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Cannot find course context for course $coursename, ID $courseid",
                $this->getSession()
            );
        }
        $sectionid = $DB->get_field(
            'course_sections',
            'id',
            ['course' => $courseid, 'section' => $sectionnumber],
            MUST_EXIST
        );

        $tilephoto = new \format_tiles\local\tile_photo($context, $sectionid);
        if (!$tilephoto->get_file()) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "File not found in files table for course $coursename | course ID $courseid "
                . "context ID $context->id | tile $sectionnumber | photo $photoname | element ID $sectionid",
                $this->getSession()
            );
        }

        $imageurl = $CFG->wwwroot . "/pluginfile.php/" . $context->id
            . '/format_tiles/tilephoto/' . $sectionid . '/tilephoto/' . $photoname;

        $tilestyle = get_config('format_tiles', 'tilestyle');
        $xpath = in_array($tilestyle, ["1", "2"])
            ? "//li[@id='tile-" . $sectionnumber . "']"
            : "//li[@id='tile-" . $sectionnumber . "']//div[contains(@class, 'photo-overlay')]";
        $node = $this->get_selected_node("xpath_element", $xpath);
        $nodestyle = $node->getAttribute('style');

        // File name will have _xxx added to end before extension, where xxx is random string.
        // E.g. apple_xeb.jpg.
        $imageextension = '.' . pathinfo($imageurl, PATHINFO_EXTENSION);
        $imagebaseurl = substr($imageurl, 0, stripos($imageurl, $imageextension));
        if (!$nodestyle || strpos($nodestyle, $imagebaseurl) === false || strpos($nodestyle, $imageextension) === false) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Tile $sectionnumber :Photo not displaying as background tile $sectionnumber course $coursename"
                . " could not find image base URL '$imagebaseurl' or extension '$imageextension'"
                . "in style string '$nodestyle' for tile style '$tilestyle'",
                $this->getSession()
            );
        }
        return true;
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given /^course "(?P<course_name>(?:[^"]|\\")*)" tile "(?P<section_number>\d+)" should show no photo$/
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $coursename
     * @param int $sectionnumber
     * @return bool
     */
    public function tile_should_show_no_photo($coursename, $sectionnumber) {
        // @codingStandardsIgnoreEnd.
        global $DB;
        $courseid = $DB->get_field('course', 'id', ['fullname' => $coursename], MUST_EXIST);
        $sectionid = $DB->get_field(
            'course_sections',
            'id',
            ['course' => $courseid, 'section' => $sectionnumber],
            MUST_EXIST
        );
        $photo = \format_tiles\local\format_option::get(
            $courseid,
            format_tiles\local\format_option::OPTION_SECTION_PHOTO,
            $sectionid
        );
        if ($photo) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Photo unexpectedly found for course $coursename tile $sectionnumber photo $photo",
                $this->getSession()
            );
        }
        return true;
    }

    // @codingStandardsIgnoreStart.
    /**
     * Check the "This will delete Tile x" message.
     * Moodle 4.1 and earlier has a different message, so we have this function to avoid separate plugin versions.
     * E.g. "This will delete Tile 5 and all the activities it contains".
     * @Then /^I should see section confirm delete message for "(?P<text_string>(?:[^"]|\\")*)"$/
     * @param string $sectionname
     * @return bool
     * @throws coding_exception
     */
    public function should_see_section_confirm_delete($sectionname) {
        // @codingStandardsIgnoreEnd.
        $moodlerelease = \format_tiles\local\util::get_moodle_release();
        $expectedstring = $moodlerelease < 4.2
            ? get_string('confirmdeletesection', 'moodle', $sectionname)
            : get_string('sectiondelete_info', 'core_courseformat', (object)['name' => $sectionname]);

        $this->execute('behat_general::assert_page_contains_text', [$expectedstring]);
        return true;
    }

    /**
     * Sets the completion tracking field to manual depending on Moodle version.
     * (To avoid having to have multiple Tiles plugin versions).
     *
     * @Given /^I set activity completion tracking form field to manual$/
     * @return void
     */
    public function i_set_completion_tracking_to_manual() {
        // Moodle 42 version: And I set the field "Completion tracking" to "Students can manually mark the activity as completed".
        // Moodle 43 version: And I set the field "Students must manually mark the activity as done" to "1".
        $moodlerelease = \format_tiles\local\util::get_moodle_release();
        $field = $moodlerelease <= 4.2
            ? "Completion tracking"
            : "Students must manually mark the activity as done";
        $value = $moodlerelease <= 4.2
            ? "Students can manually mark the activity as completed"
            : "1";
        $behatforms = behat_context_helper::get('behat_forms');
        $behatforms->i_set_the_field_to($field, $value);
    }

    /**
     * Get the id of the course currently being viewed.
     *
     * @return int
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    private function get_tiles_current_course_id(): int {
        $url = $this->getSession()->getCurrentUrl();
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query ?? '', $params);
        if (empty($params['id'])) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Could not determine the course from the current URL $url",
                $this->getSession()
            );
        }
        return (int)$params['id'];
    }

    /**
     * Evaluate a multi-statement script body in the page, returning its value.
     *
     * The Mink driver only prefixes "return " to the whole script, so a multi-statement
     * body must be wrapped in an immediately invoked function.
     *
     * @param string $body JavaScript statements ending with a return.
     * @return mixed
     */
    private function evaluate_tiles_js(string $body) {
        return $this->evaluate_script('return (() => {' . $body . '})();');
    }

    /**
     * Open the section actions menu for the given tile and return its xpath.
     *
     * @param int $sectionnumber
     * @return string
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function open_tile_section_actions($sectionnumber): string {
        // Close any dropdown left open by a previous step, otherwise it can cover the toggle.
        $this->execute_script('document.body.click();');
        $this->getSession()->wait(200);
        $xpath = $this->tile_exists($sectionnumber);
        $menuxpath = $xpath
            . "/descendant::div[contains(@class, 'section-actions')]/descendant::a[contains(@data-bs-toggle, 'dropdown')]";
        $exception = new \Behat\Mink\Exception\ElementNotFoundException(
            $this->getSession(),
            "Tile $sectionnumber section actions menu"
        );
        $menu = $this->find('xpath', $menuxpath, $exception);
        $menu->click();
        $this->wait_for_pending_js();
        $this->getSession()->wait(500);
        return $xpath;
    }

    /**
     * Click the highlight/unhighlight action in the given tile's section menu.
     *
     * @param int $sectionnumber
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function click_tile_highlight_action($sectionnumber): void {
        $xpath = $this->open_tile_section_actions($sectionnumber);
        $linkxpath = $xpath . "/descendant::a[contains(@class, 'editing_highlight')]";
        $exception = new \Behat\Mink\Exception\ElementNotFoundException(
            $this->getSession(),
            "Tile $sectionnumber highlight menu action"
        );
        $this->find('xpath', $linkxpath, $exception)->click();
        $this->wait_for_pending_js();
        $this->getSession()->wait(1000);
    }

    /**
     * Click the highlight action in a tile's section menu. You need to be on the course page in editing mode.
     *
     * @Given /^I highlight tile "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_highlight_tile($sectionnumber) {
        $this->click_tile_highlight_action($sectionnumber);
    }

    /**
     * Click the unhighlight action in a tile's section menu. You need to be on the course page in editing mode.
     *
     * @Given /^I unhighlight tile "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_unhighlight_tile($sectionnumber) {
        $this->click_tile_highlight_action($sectionnumber);
    }

    /**
     * Checks the course marker points at the given tile.
     *
     * @Then /^tile "(?P<section_number>\d+)" should be the highlighted section$/
     * @param int $sectionnumber
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function tile_should_be_the_highlighted_section($sectionnumber) {
        global $DB;
        $courseid = $this->get_tiles_current_course_id();
        $marker = (int)$DB->get_field('course', 'marker', ['id' => $courseid], MUST_EXIST);
        if ($marker !== (int)$sectionnumber) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected tile $sectionnumber to be highlighted, but the course marker is $marker",
                $this->getSession()
            );
        }
    }

    /**
     * Checks the course marker does not point at the given tile.
     *
     * @Then /^tile "(?P<section_number>\d+)" should not be the highlighted section$/
     * @param int $sectionnumber
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function tile_should_not_be_the_highlighted_section($sectionnumber) {
        global $DB;
        $courseid = $this->get_tiles_current_course_id();
        $marker = (int)$DB->get_field('course', 'marker', ['id' => $courseid], MUST_EXIST);
        if ($marker === (int)$sectionnumber) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected tile $sectionnumber not to be highlighted, but the course marker is $marker",
                $this->getSession()
            );
        }
    }

    /**
     * Set a window variable which a later step uses to prove the page did not navigate.
     *
     * @Given /^I set the tiles page-sentinel$/
     */
    public function i_set_the_tiles_page_sentinel() {
        $this->execute_script('window.__tilesPageSentinel = 1;');
    }

    /**
     * Check the page sentinel is still set. A full page navigation clears it.
     *
     * @Then /^the tiles page-sentinel should still be set$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_page_sentinel_should_still_be_set() {
        $this->wait_for_pending_js();
        $sentinel = $this->evaluate_script('return window.__tilesPageSentinel || 0;');
        if (empty($sentinel)) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'The course page was reloaded, so the highlight action was not dispatched reactively',
                $this->getSession()
            );
        }
    }

    /**
     * Check the text of the highlight menu action for a tile.
     *
     * @Then /^the tile "(?P<section_number>\d+)" menu action should now read "(?P<expected>(?:[^"]|\\")*)"$/
     * @param int $sectionnumber
     * @param string $expected
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function tile_menu_action_should_now_read($sectionnumber, $expected) {
        $xpath = $this->open_tile_section_actions($sectionnumber);
        $linkxpath = $xpath . "/descendant::a[contains(@class, 'editing_highlight')]";
        $exception = new \Behat\Mink\Exception\ElementNotFoundException(
            $this->getSession(),
            "Tile $sectionnumber highlight menu action"
        );
        $actual = trim($this->find('xpath', $linkxpath, $exception)->getText());
        if ($actual !== $expected) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected the tile $sectionnumber menu action to read '$expected', it reads '$actual'",
                $this->getSession()
            );
        }
    }

    /**
     * Click one of the above-tiles control toggles (the copy shown on desktop).
     *
     * @Given /^I toggle the "(?P<control>animatednav|highcontrast)" tiles control$/
     * @param string $control
     */
    public function i_toggle_the_tiles_control($control) {
        $this->execute_script(
            "document.getElementById('format-tiles-control-$control-sm').click();"
        );
        $this->wait_for_pending_js();
        $this->getSession()->wait(1000);
    }

    /**
     * Check an above-tiles control is checked.
     *
     * @Then /^the "(?P<control>animatednav|highcontrast)" tiles control should be checked$/
     * @param string $control
     */
    public function tiles_control_should_be_checked($control) {
        $this->assert_tiles_control_checked($control, true);
    }

    /**
     * Check an above-tiles control is not checked.
     *
     * @Then /^the "(?P<control>animatednav|highcontrast)" tiles control should not be checked$/
     * @param string $control
     */
    public function tiles_control_should_not_be_checked($control) {
        $this->assert_tiles_control_checked($control, false);
    }

    /**
     * Assert the checked state of an above-tiles control.
     *
     * @param string $control
     * @param bool $expected
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    private function assert_tiles_control_checked(string $control, bool $expected): void {
        $checked = (bool)$this->evaluate_script(
            "return document.getElementById('format-tiles-control-$control-sm')?.checked || false;"
        );
        if ($checked !== $expected) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected the $control tiles control " . ($expected ? 'to be' : 'not to be') . ' checked',
                $this->getSession()
            );
        }
    }

    /**
     * Check the four above-tiles switch controls are rendered and wired to their toggle URLs.
     *
     * @Then /^the format tiles above tiles controls should be complete$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_above_tiles_controls_should_be_complete() {
        $result = $this->evaluate_tiles_js('
            const expected = {
                "format-tiles-control-animatednav": "toggleanimatednav",
                "format-tiles-control-highcontrast": "togglehighcontrast",
            };
            let problem = "";
            for (const [base, action] of Object.entries(expected)) {
                for (const suffix of ["-xs", "-sm"]) {
                    const id = base + suffix;
                    const input = document.getElementById(id);
                    if (!input) {
                        problem = "missing " + id;
                        break;
                    }
                    if (!input.closest(".form-switch")) {
                        problem = id + " is not inside a form-switch";
                        break;
                    }
                    if (!document.querySelector(`label[for="${id}"]`)) {
                        problem = "missing label for " + id;
                        break;
                    }
                    const url = input.dataset.formatTilesControlUrl || "";
                    if (!url.includes("format-tiles-action=" + action)) {
                        problem = id + " has url " + url;
                        break;
                    }
                }
            }
            return problem ? problem : "ok";
        ');
        if ($result !== 'ok') {
            throw new \Behat\Mink\Exception\ExpectationException($result, $this->getSession());
        }
    }

    /**
     * Check the section zero container and the above tiles bar are siblings of the multi-section page.
     *
     * @Then /^the above tiles and section zero containers should share the multi-section page parent$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_above_tiles_containers_should_share_the_page_parent() {
        $result = $this->evaluate_tiles_js('
            const page = document.getElementById("format-tiles-multi-section-page");
            const zero = document.getElementById("section-zero-container");
            const above = document.getElementById("abovetiles");
            if (!page || !zero || !above) {
                return "missing element";
            }
            return (zero.parentElement === page && above.parentElement === page) ? "ok" : "parent mismatch";
        ');
        if ($result !== 'ok') {
            throw new \Behat\Mink\Exception\ExpectationException($result, $this->getSession());
        }
    }

    /**
     * Click the same activity link twice within one page task, so both clicks race each other.
     *
     * @Given /^I click format tiles activity "(?P<activityname_string>(?:[^"]|\\")*)" twice with no delay$/
     * @param string $activityname
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function click_format_tiles_activity_twice($activityname) {
        $this->wait_for_pending_js();
        $name = json_encode($activityname);
        $result = $this->evaluate_tiles_js("
            const links = Array.from(document.querySelectorAll('#page-content a'));
            const link = links.find((candidate) => candidate.textContent.trim() === $name);
            if (!link) {
                return 'not found';
            }
            link.click();
            link.click();
            return 'clicked';
        ");
        if ($result !== 'clicked') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Could not find the activity link $activityname",
                $this->getSession()
            );
        }
        $this->getSession()->wait(self::get_timeout() * 1000, "document.querySelector('.modal.show.embed_cm_modal') !== null");
        $this->wait_for_pending_js();
        $this->getSession()->wait(1000);
    }

    /**
     * Check how many activity modals are open.
     *
     * @Then /^there should be "(?P<count>\d+)" open tiles activity modals?$/
     * @param int $count
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function there_should_be_open_tiles_activity_modals($count) {
        $actual = (int)$this->evaluate_script(
            "return document.querySelectorAll('.modal.show.embed_cm_modal').length;"
        );
        if ($actual !== (int)$count) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected $count open activity modals, found $actual",
                $this->getSession()
            );
        }
    }

    /**
     * Click a tile's icon picker launcher twice within one page task.
     *
     * @Given /^I double click the tiles icon picker for tile "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function i_double_click_the_tiles_icon_picker($sectionnumber) {
        $this->wait_for_pending_js();
        $result = $this->evaluate_tiles_js("
            const launcher = document.getElementById('tileicon_$sectionnumber');
            if (!launcher) {
                return 'not found';
            }
            launcher.click();
            launcher.click();
            return 'clicked';
        ");
        if ($result !== 'clicked') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Could not find the icon picker launcher for tile $sectionnumber",
                $this->getSession()
            );
        }
        $this->getSession()->wait(self::get_timeout() * 1000, "document.getElementById('icon_picker_modal') !== null");
        $this->wait_for_pending_js();
        $this->getSession()->wait(1000);
    }

    /**
     * Check exactly one icon picker modal is open and it lists icons.
     *
     * @Then /^there should be exactly one open icon picker$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function there_should_be_exactly_one_open_icon_picker() {
        $actual = $this->evaluate_tiles_js('
            return document.querySelectorAll("#icon_picker_modal").length + ":"
                + document.querySelectorAll("#icon_picker_modal .pickericon").length;
        ');
        [$modals, $icons] = array_map('intval', explode(':', (string)$actual));
        if ($modals !== 1 || $icons < 1) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected one icon picker with icons, found $modals modals and $icons icons",
                $this->getSession()
            );
        }
    }

    /**
     * Check the activity modal header has a close button and no full width toggle.
     *
     * @Then /^the tiles activity modal header should have a close button and no full width toggle$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_activity_modal_header_should_have_a_close_button() {
        $result = $this->evaluate_tiles_js('
            const modal = document.querySelector(".modal.show.embed_cm_modal");
            if (!modal) {
                return "no modal";
            }
            const close = modal.querySelector(".modal-header button.btn-close");
            const toggle = modal.querySelector(".button_toggle_fullwidth");
            return (close ? "close" : "noclose") + ":" + (toggle ? "toggle" : "notoggle");
        ');
        if ($result !== 'close:notoggle') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Unexpected activity modal header: $result",
                $this->getSession()
            );
        }
    }

    /**
     * Check focus is on the manual completion toggle inside the open activity modal.
     *
     * @Then /^the tiles activity modal completion toggle should have focus$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_activity_modal_completion_toggle_should_have_focus() {
        $this->wait_for_pending_js();
        $this->getSession()->wait(1000);
        $result = $this->evaluate_tiles_js('
            const modal = document.querySelector(".modal.show.embed_cm_modal");
            if (!modal) {
                return "no modal";
            }
            const active = document.activeElement;
            if (!active) {
                return "no active element";
            }
            if (!modal.contains(active)) {
                return "focus outside modal: " + active.tagName;
            }
            if (!active.matches("button[data-action=toggle-manual-completion]")) {
                return "focus on " + active.tagName + "." + active.className;
            }
            return "ok";
        ');
        if ($result !== 'ok') {
            throw new \Behat\Mink\Exception\ExpectationException($result, $this->getSession());
        }
    }

    /**
     * Remember the current activity modal width.
     *
     * @Given /^I remember the tiles activity modal width$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function i_remember_the_tiles_activity_modal_width() {
        $width = $this->evaluate_tiles_js('
            const dialog = document.querySelector(".modal.show.embed_cm_modal .modal-dialog");
            if (!dialog) {
                return -1;
            }
            return Math.round(dialog.getBoundingClientRect().width);
        ');
        if ((int)$width <= 0) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Could not measure the activity modal width',
                $this->getSession()
            );
        }
        $this->execute_script('window.__tilesModalWidth = ' . (int)$width . ';');
    }

    /**
     * Scroll the activity modal body, so any height watcher gets a chance to fire.
     *
     * @When /^I scroll the tiles activity modal body$/
     */
    public function i_scroll_the_tiles_activity_modal_body() {
        $this->execute_script('
            const body = document.querySelector(".modal.show.embed_cm_modal .modal-body");
            if (body) {
                body.scrollTop = body.scrollHeight;
            }
        ');
        $this->getSession()->wait(2000);
    }

    /**
     * Check the activity modal width has not changed since it was remembered.
     *
     * @Then /^the tiles activity modal width should not have changed$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_activity_modal_width_should_not_have_changed() {
        $actual = (int)$this->evaluate_tiles_js('
            const dialog = document.querySelector(".modal.show.embed_cm_modal .modal-dialog");
            return dialog ? Math.round(dialog.getBoundingClientRect().width) : -1;
        ');
        $expected = (int)$this->evaluate_script('return window.__tilesModalWidth || -1;');
        if ($actual !== $expected) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "The activity modal width changed from $expected to $actual",
                $this->getSession()
            );
        }
    }

    /**
     * Check the course settings default tile icon select holds the given icon filename.
     *
     * @Then /^the tiles default icon field should hold "(?P<icon_string>(?:[^"]|\\")*)"$/
     * @param string $icon
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_default_icon_field_should_hold($icon) {
        $value = $this->evaluate_script(
            "return document.getElementById('id_defaulttileicon')?.value || '';"
        );
        if ($value !== $icon) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected the default tile icon field to hold '$icon', it holds '$value'",
                $this->getSession()
            );
        }
    }

    /**
     * Click a course index entry by its visible text.
     *
     * @Given /^I click on the course index entry "(?P<name>(?:[^"]|\\")*)"$/
     * @param string $name
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function i_click_on_the_course_index_entry($name) {
        $result = $this->evaluate_tiles_js('
            const wanted = ' . json_encode($name) . ';
            const links = Array.from(document.querySelectorAll(
                "nav#courseindex a.courseindex-link[data-action=\'togglecourseindexsection\']"
            ));
            const match = links.find((link) => link.textContent.trim() === wanted);
            if (!match) {
                return "missing";
            }
            match.click();
            return "clicked";
        ');
        if ($result !== 'clicked') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Could not find a course index entry named '$name'",
                $this->getSession()
            );
        }
        $this->wait_for_pending_js();
    }

    /**
     * Check the activity item inside a tile carries no padding on a narrow screen.
     *
     * @Then /^the tiles activity item should have no padding$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_activity_item_should_have_no_padding() {
        $padding = $this->element_get_css_value('body.format-tiles.path-course-view li.activity > div', 'padding');
        if ($padding !== '0px') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected the activity item to have no padding, it has '$padding'",
                $this->getSession()
            );
        }
    }

    /**
     * Check a tile is open and that a named subsection within it was scrolled to and focused.
     *
     * expandSection() opens the tile, then moves the focus into the subsection 300 ms later, once the slide down
     * and the scroll animation have finished, so this polls for the settled state instead of sampling once.
     *
     * @Then /^the tile "(?P<tilenumber>\d+)" should be open and the "(?P<name>(?:[^"]|\\")*)" subsection should have focus$/
     * @param int $tilenumber
     * @param string $name
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tile_should_be_open_and_the_subsection_should_have_focus($tilenumber, $name) {
        $problems = '';
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $problems = $this->evaluate_tiles_js('
                const problems = [];
                const tile = document.getElementById("tile-' . (int)$tilenumber . '");
                if (!tile) {
                    return "tile-' . (int)$tilenumber . ' is missing";
                }
                if (!tile.classList.contains("selected")) {
                    problems.push("tile-' . (int)$tilenumber . ' is not selected");
                }
                const wanted = ' . json_encode($name) . ';
                const titles = Array.from(document.querySelectorAll(\'#region-main [data-for="section_title"]\'))
                    .filter((title) => title.textContent.trim() === wanted && title.dataset.id);
                if (!titles.length) {
                    return `no section title named ${wanted}`;
                }
                const panel = document.getElementById("coursecontentcollapseid" + titles[0].dataset.id);
                if (!panel) {
                    return `no panel for ${wanted}`;
                }
                if (!panel.classList.contains("show")) {
                    problems.push(`${wanted} is still collapsed`);
                }
                const container = panel.closest("li") || panel;
                const margin = window.getComputedStyle(container).scrollMarginTop;
                if (!margin || margin === "0px") {
                    problems.push(`${wanted} has no scroll margin, it is ${margin}`);
                }
                if (!container.contains(document.activeElement)) {
                    problems.push(`${wanted} does not hold the focus`);
                }
                return problems.join("; ");
            ');
            if ($problems === '') {
                return;
            }
            $this->getSession()->wait(200);
        }
        throw new \Behat\Mink\Exception\ExpectationException(
            "Subsection navigation did not settle: $problems",
            $this->getSession()
        );
    }

    /**
     * Check the tiles overlay is fixed to the viewport.
     *
     * @Then /^the tiles overlay should be fixed to the viewport$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_overlay_should_be_fixed_to_the_viewport() {
        $position = $this->evaluate_tiles_js('
            const overlay = document.getElementById("format_tiles_overlay");
            return overlay ? window.getComputedStyle(overlay).position : "missing";
        ');
        if ($position !== 'fixed') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected the tiles overlay to be fixed, its position is $position",
                $this->getSession()
            );
        }
    }

    /**
     * Check every section toggler points at a panel that exists, and that panel ids are unique.
     *
     * @Then /^the tiles collapse toggles should control existing panels$/
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_collapse_toggles_should_control_existing_panels() {
        $problems = $this->evaluate_tiles_js('
            const problems = [];
            const claimants = {};
            document.querySelectorAll(\'[id^="coursecontentcollapse"]\').forEach((panel) => {
                claimants[panel.id] = (claimants[panel.id] || 0) + 1;
            });
            Object.keys(claimants).forEach((id) => {
                if (claimants[id] > 1) {
                    problems.push(`duplicate panel id ${id} (${claimants[id]}x)`);
                }
            });
            document.querySelectorAll(\'a[data-for="sectiontoggler"]\').forEach((toggle) => {
                const target = toggle.getAttribute("aria-controls");
                if (!target) {
                    problems.push(`toggler ${toggle.id || "(no id)"} has no aria-controls`);
                } else if (!document.getElementById(target)) {
                    problems.push(`toggler ${toggle.id || "(no id)"} controls missing panel ${target}`);
                }
            });
            return problems.join("; ");
        ');
        if ($problems !== '') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Collapse toggles and panels do not line up: $problems",
                $this->getSession()
            );
        }
    }

    /**
     * Visit the section page the tile's breadcrumb link points at, i.e. /course/section.php?id=<sectionid>.
     *
     * @When /^I visit the section page for tile "(?P<tilenumber>\d+)"$/
     * @param int $tilenumber
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function i_visit_the_section_page_for_tile($tilenumber) {
        $url = $this->evaluate_tiles_js('
            const tile = document.getElementById("tile-' . (int)$tilenumber . '");
            const sectionid = tile ? tile.dataset.trueSectionid : "";
            return sectionid ? (M.cfg.wwwroot + "/course/section.php?id=" + sectionid) : "";
        ');
        if ($url === '') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "tile-" . (int)$tilenumber . " is missing or has no data-true-sectionid",
                $this->getSession()
            );
        }
        $this->getSession()->visit($url);
    }

    /**
     * Check a tile is presented the way a tile click presents it: content visible, backdrop up, body marked open.
     *
     * Polls because the tile is presented through a jQuery slide down and fade in, not synchronously.
     *
     * @Then /^tile "(?P<tilenumber>\d+)" content should be presented$/
     * @param int $tilenumber
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function tile_content_should_be_presented($tilenumber) {
        $problems = '';
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $problems = $this->evaluate_tiles_js('
                const n = ' . (int)$tilenumber . ';
                const problems = [];
                const tile = document.getElementById("tile-" + n);
                if (!tile) {
                    return "tile-" + n + " is missing";
                }
                if (!tile.classList.contains("selected")) {
                    problems.push("tile-" + n + " is not selected");
                }
                const section = document.getElementById("section-" + n);
                if (!section) {
                    problems.push("section-" + n + " is missing");
                } else if (window.getComputedStyle(section).display === "none") {
                    problems.push("section-" + n + " is hidden");
                }
                const overlay = document.getElementById("format_tiles_overlay");
                if (!overlay) {
                    problems.push("the overlay is missing");
                } else if (window.getComputedStyle(overlay).display === "none") {
                    problems.push("the overlay is not shown");
                }
                if (!document.body.classList.contains("format-tiles-tile-open")) {
                    problems.push("the body lacks format-tiles-tile-open");
                }
                return problems.join("; ");
            ');
            if ($problems === '') {
                return;
            }
            $this->getSession()->wait(200);
        }
        throw new \Behat\Mink\Exception\ExpectationException(
            "Tile " . (int)$tilenumber . " is not presented: $problems",
            $this->getSession()
        );
    }

    /**
     * Click an activity name, selecting it by course module idnumber.
     *
     * Step arguments cannot carry double quotes unambiguously, so activities whose names contain
     * quotes are addressed by idnumber.
     *
     * @Given /^I click the format tiles activity with idnumber "(?P<idnumber>(?:[^"]|\\")*)"$/
     * @param string $idnumber the course module idnumber
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function i_click_the_format_tiles_activity_with_idnumber($idnumber) {
        $cmid = $this->get_tiles_cm_id_by_idnumber($idnumber);
        $this->wait_for_pending_js();
        $this->getSession()->wait(100);
        $result = $this->evaluate_tiles_js('
            const container = document.querySelector("#page-content [data-cmid=\'' . $cmid . '\']");
            if (!container) {
                return "missing";
            }
            const link = container.matches("a") ? container : container.querySelector("a");
            if (!link) {
                return "nolink";
            }
            link.click();
            return "clicked";
        ');
        $this->wait_for_tiles_activity_modal($result);
    }

    /**
     * Click an activity entry in the course index, selected by course module idnumber.
     *
     * @Given /^I click the tiles course index activity with idnumber "(?P<idnumber>(?:[^"]|\\")*)"$/
     * @param string $idnumber the course module idnumber
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function i_click_the_tiles_course_index_activity_with_idnumber($idnumber) {
        $cmid = $this->get_tiles_cm_id_by_idnumber($idnumber);
        $this->wait_for_pending_js();
        $result = $this->evaluate_tiles_js('
            const link = document.querySelector(
                "nav#courseindex li.courseindex-item[data-id=\'' . $cmid . '\'] a.courseindex-link"
            );
            if (!link) {
                return "missing";
            }
            link.click();
            return "clicked";
        ');
        $this->wait_for_tiles_activity_modal($result);
    }

    /**
     * Check the open activity modal heading shows the plain activity name.
     *
     * The expected name is given as a table row so it may contain any quote character.
     *
     * @Then /^the tiles activity modal title should read$/
     * @param \Behat\Gherkin\Node\TableNode $expected the expected name, one cell
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_activity_modal_title_should_read(\Behat\Gherkin\Node\TableNode $expected) {
        $name = $this->single_expected_name($expected);
        $actual = $this->evaluate_tiles_js('
            const modals = document.querySelectorAll(".modal.show.embed_cm_modal");
            if (modals.length !== 1) {
                return "modals:" + modals.length;
            }
            const title = modals[0].querySelector(".modal-title");
            return (title ? title.textContent : "").trim();
        ');
        if ($actual !== $name) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected the activity modal title '$name', found '$actual'",
                $this->getSession()
            );
        }
    }

    /**
     * Check the open activity modal iframe title is the plain activity name.
     *
     * The expected name is given as a table row so it may contain any quote character.
     *
     * @Then /^the tiles activity modal iframe title should read$/
     * @param \Behat\Gherkin\Node\TableNode $expected the expected name, one cell
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_activity_modal_iframe_title_should_read(\Behat\Gherkin\Node\TableNode $expected) {
        $name = $this->single_expected_name($expected);
        $actual = $this->evaluate_tiles_js('
            const modals = document.querySelectorAll(".modal.show.embed_cm_modal");
            if (modals.length !== 1) {
                return "modals:" + modals.length;
            }
            const iframe = modals[0].querySelector("iframe");
            return iframe ? iframe.getAttribute("title") : "noiframe";
        ');
        if ($actual !== $name) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected the activity modal iframe title '$name', found '$actual'",
                $this->getSession()
            );
        }
    }

    /**
     * Read the single expected name from a one-cell table.
     *
     * @param \Behat\Gherkin\Node\TableNode $expected the expected name
     * @return string
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    private function single_expected_name(\Behat\Gherkin\Node\TableNode $expected): string {
        $names = $expected->getColumn(0);
        if (count($names) !== 1 || trim((string)$names[0]) === '') {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Expected exactly one activity name in the table',
                $this->getSession()
            );
        }
        return $names[0];
    }

    /**
     * Look up the course module id for an idnumber in the course being viewed.
     *
     * @param string $idnumber the course module idnumber
     * @return int
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    private function get_tiles_cm_id_by_idnumber(string $idnumber): int {
        global $DB;
        $courseid = $this->get_tiles_current_course_id();
        $cmid = $DB->get_field('course_modules', 'id', ['idnumber' => $idnumber, 'course' => $courseid]);
        if (!$cmid) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "No course module with idnumber '$idnumber' in course $courseid",
                $this->getSession()
            );
        }
        return (int)$cmid;
    }

    /**
     * Wait for an activity modal to open after a click.
     *
     * @param string $clickresult the result reported by the click script
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    private function wait_for_tiles_activity_modal($clickresult) {
        if ($clickresult !== 'clicked') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Could not click the activity ($clickresult)",
                $this->getSession()
            );
        }
        $this->getSession()->wait(self::get_timeout() * 1000, "document.querySelector('.modal.show.embed_cm_modal') !== null");
        $this->wait_for_pending_js();
        $this->getSession()->wait(1000);
    }

    /**
     * Check a section wrapper exposes core's parsed return options and no legacy empty attribute.
     *
     * @Then /^the tiles section "(?P<section_number>\d+)" should expose core return options$/
     * @param int $sectionnumber the section number
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_section_should_expose_core_return_options($sectionnumber) {
        $this->assert_tiles_return_options('#section-' . (int)$sectionnumber);
    }

    /**
     * Check a delegated subsection wrapper exposes core's parsed return options.
     *
     * @Then /^the tiles delegated section "(?P<section_number>\d+)" should expose core return options$/
     * @param int $sectionnumber the section number
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function the_tiles_delegated_section_should_expose_core_return_options($sectionnumber) {
        $this->assert_tiles_return_options('#section-' . (int)$sectionnumber . '.delegated-section');
    }

    /**
     * Assert a section wrapper carries usable core return options.
     *
     * @param string $selector the wrapper selector
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    private function assert_tiles_return_options(string $selector) {
        $result = $this->evaluate_tiles_js('
            const section = document.querySelector(' . json_encode($selector) . ');
            if (!section) {
                return "missing";
            }
            if (section.hasAttribute("data-sectionreturnid")) {
                return "legacy attribute present";
            }
            const raw = section.getAttribute("data-returnoptions");
            if (!raw) {
                return "no return options";
            }
            let parsed;
            try {
                parsed = JSON.parse(raw);
            } catch (e) {
                return "unparsable return options: " + raw;
            }
            if (typeof parsed !== "object" || parsed === null) {
                return "return options are not an object: " + raw;
            }
            return "ok";
        ');
        if ($result !== 'ok') {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Section $selector return options: $result",
                $this->getSession()
            );
        }
    }
}
