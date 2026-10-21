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
 * Handle the toggles in the above-tiles controls menu.
 *
 * @module      format_tiles/above_tiles_controls
 * @copyright   2026 David Watson {@link http://evolutioncode.uk}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    "use strict";

    return {
        /**
         * Navigate to the URL stored on the control that was toggled.
         */
        init: function() {
            document.addEventListener('change', function(event) {
                const input = event.target.closest('[data-format-tiles-control-url]');
                if (!input) {
                    return;
                }
                window.location.assign(input.dataset.formatTilesControlUrl);
            });
        }
    };
});
