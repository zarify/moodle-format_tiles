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
 * Format tiles section extra logic component.
 *
 * Swaps the Highlight/Unhighlight menu action in place when the section marker changes,
 * because core's own section menu updater only handles Hide/Show.
 *
 * Adapted from the core format_topics section module (copyright 2022 Ferran Recio,
 * GPL v3 or later) so that the tiles format does not depend on the format_topics plugin.
 *
 * @module      format_tiles/section
 * @copyright   2025 David Watson {@link http://evolutioncode.uk}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/reactive',
    'core_courseformat/courseeditor',
    'core/templates',
], function(Reactive, CourseEditor, Templates) {
    "use strict";

    class HighlightSection extends Reactive.BaseComponent {

        /**
         * Constructor hook.
         */
        create() {
            // Optional component name for debugging.
            this.name = 'format_tiles_section';
            // Default query selectors.
            this.selectors = {
                SETMARKER: '[data-action="sectionHighlight"]',
                REMOVEMARKER: '[data-action="sectionUnhighlight"]',
                ACTIONTEXT: '.menu-action-text',
                ICON: '.icon',
            };
            // The tiles format section specific actions.
            this.formatActions = {
                HIGHLIGHT: 'sectionHighlight',
                UNHIGHLIGHT: 'sectionUnhighlight',
            };
        }

        /**
         * Component watchers.
         *
         * @returns {Array} of watchers
         */
        getWatchers() {
            return [
                {watch: 'section.current:updated', handler: this._refreshHighlight},
            ];
        }

        /**
         * Update the section highlight menu action using the state information.
         *
         * @param {object} param
         * @param {Object} param.element details the update details.
         */
        async _refreshHighlight({element}) {
            let selector;
            let newAction;
            if (element.current) {
                selector = this.selectors.SETMARKER;
                newAction = this.formatActions.UNHIGHLIGHT;
            } else {
                selector = this.selectors.REMOVEMARKER;
                newAction = this.formatActions.HIGHLIGHT;
            }
            // Find the affected action.
            const affectedAction = this.getElement(`${selector}`, element.id);
            if (!affectedAction) {
                return;
            }
            // Change action, text and icon.
            affectedAction.dataset.action = newAction;
            const actionText = affectedAction.querySelector(this.selectors.ACTIONTEXT);
            if (affectedAction.dataset?.swapname && actionText) {
                const oldText = actionText?.innerText;
                actionText.innerText = affectedAction.dataset.swapname;
                affectedAction.dataset.swapname = oldText;
            }
            const icon = affectedAction.querySelector(this.selectors.ICON);
            if (affectedAction.dataset?.swapicon && icon) {
                const newIcon = affectedAction.dataset.swapicon;
                if (newIcon) {
                    const pixHtml = await Templates.renderPix(newIcon, 'core');
                    Templates.replaceNode(icon, pixHtml, '');
                    affectedAction.dataset.swapicon = affectedAction.dataset.icon;
                    affectedAction.dataset.icon = newIcon;
                }
            }
        }
    }

    return {
        /**
         * Add the highlight component to the course page.
         */
        init: function() {
            const courseEditor = CourseEditor.getCurrentCourseEditor();
            if (courseEditor.supportComponents && courseEditor.isEditing) {
                new HighlightSection({
                    element: document.getElementById('page'),
                    reactive: courseEditor,
                });
            }
        }
    };
});
