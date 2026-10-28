# Test coverage — format_tiles fork

Scope: `master-forked`, release 5.2.3, Moodle 5.3 LTS – 6.0. Every row records the test that now guards it, or `backlog`.

## Suite inventory

| Suite | Files | Cases |
|---|---|---|
| PHPUnit | `tests/*_test.php`, `tests/courseformat/*_test.php`, `tests/privacy/provider_test.php` (10 files) | 89 methods (102 cases with data sets) |
| Behat | `tests/behat/*.feature` (32 features) | 79 scenarios, `@format_tiles` |

## (1) Deprecated-API removal / Moodle 6 uplift

| Surface | Location | Guard |
|---|---|---|
| `stateactions::section_highlight` | `classes/courseformat/stateactions.php:45` | `tests/courseformat/stateactions_test.php::test_section_highlight*` |
| `stateactions::section_unhighlight` | `classes/courseformat/stateactions.php:85` | `tests/courseformat/stateactions_test.php::test_section_unhighlight` |
| Highlight menu item (action, data, href, capability gate) | `classes/output/courseformat/content/section/controlmenu.php:54-183` | `tests/courseformat/controlmenu_test.php`; `tests/behat/section_highlight.feature` |
| Non-JS fallback through `course/format/update.php` | `course/format/update.php:121-129` | `tests/behat/section_highlight.feature` (reactivity falsification, see section 5) |
| `section_action()` `setmarker`/`removemarker` branch | `lib.php:835-850` | backlog |
| `format.php` legacy `?marker=` handler | `format.php:47-56` | backlog |
| `editsection` string in edit buttons | `templates/section_zero.mustache:67`, `templates/single_section_content.mustache:96` | `tests/format_tiles_test.php::test_edit_button_uses_existing_core_string` |
| `course_modinfo::instance` aliases | `format.php:52`, `lib.php:690,843,856`, `db/upgradelib.php:71` | backlog |

## (2) Fork fixes and features

| Change | Location | Guard |
|---|---|---|
| 241d46d activity names escaped in HTML attributes | `templates/course_module.mustache:109,117`, `templates/local/content/section/cmitem.mustache:50`, `templates/local/content/cm/activity.mustache:65` | `tests/format_tiles_test.php::test_activity_name_is_escaped_in_html_attributes` |
| acb657c inline folder warning sanitises the folder name | `classes/output/courseformat/content.php:159` | `tests/format_tiles_test.php::test_inline_folder_warning_escapes_folder_name` |
| f3d315d outcome filter titles escaped | `templates/filterbutton.mustache:49` | `tests/format_tiles_test.php::test_filter_button_escapes_outcome_title` |
| 386e961 / 203866c above-tiles controls as core toggles | `classes/output/course_output.php:1146`, `templates/above_tiles_buttons.mustache:24` | `tests/format_tiles_test.php::test_above_tiles_controls_export`, `::test_above_tiles_control_ids_are_unique`; `tests/behat/above_tiles_controls.feature` |
| 10db2b6 section zero container closed inside its conditional | `templates/above_tiles.mustache:62` | `tests/behat/above_tiles_controls.feature` (shared multi-section parent) |
| 8531a5a modal lifecycle (single modal, icon picker guard, focus restore, width guard, no full-width toggle) | `amd/src/course_mod_modal.js:233-260`, `amd/src/edit_icon_picker.js:395-415` | `tests/behat/modal_lifecycle.feature` |
| 3e0f0c9 icon picker during course creation | `amd/src/edit_icon_picker.js:180-200`, `lib.php:571` | `tests/behat/course_create_course.feature` (icon picker scenario) |
| b3af1c4 tile overlay stays fixed | `templates/multi_section_page.mustache:117` | `tests/behat/javascript_nav.feature` (`the tiles overlay should be fixed to the viewport`) |
| cc514de course-context guard in `update_course_format_options()` | `lib.php:636` | `tests/format_tiles_test.php::test_update_course_format_options_missing_course` |
| 3b24c6e collapse panel ids keyed on the section id, `aria-controls` matches the panel | `templates/course_modules_subsection.mustache`, `templates/local/content/section/header.mustache`, `templates/local/content/section/content.mustache`, `amd/src/course.js:234,632` | `tests/behat/subsection_coursepage_tiles.feature` (`the tiles collapse toggles should control existing panels`); `section_visibility.feature` clicks the renamed toggler |
| 5b4257f course index subsection click opens, scrolls to and focuses the subsection | `amd/src/course.js` (`expandSection`, `populateAndExpandSection`, `initCourseIndexWatcher`) | `tests/behat/subsection_coursepage_tiles.feature` (two `Course index subsection link…` scenarios, desktop and small screen) |
| 764dd1e mobile activity padding rule matches the course view | `styles.css:2138` | `tests/behat/mobile_activity_padding.feature` |
| 8f72042 non-displayable modules (qbank) excluded from the tile activity list and progress | `classes/output/course_output.php:862-882` | `tests/format_tiles_test.php::test_non_displayable_module_is_not_rendered` |

## (3) Upgrade migrations / changeover

| Surface | Location | Guard |
|---|---|---|
| 2018080103 legacy option renames/cleanups | `db/upgrade.php:56-139` | `tests/upgrade_test.php::test_upgrade_2018080103_renames_and_cleans_options` |
| 2024020200 table creation and migration queue | `db/upgrade.php:179-219` | `tests/upgrade_test.php::test_upgrade_2024020200_queues_legacy_migration` |
| 2025060267 subsection progress default | `db/upgrade.php:241-244` | `tests/upgrade_test.php::test_upgrade_2025060267_sets_progress_default` |
| 2026102700 re-queues a lost legacy migration | `db/upgrade.php:246-265` | `tests/upgrade_test.php::test_upgrade_2026102700_requeues_lost_migration_tasks` |
| 2026102700 does not double-queue on a pre-2024020200 upgrade | `db/upgrade.php:252` | `tests/upgrade_test.php::test_upgrade_2026102700_does_not_duplicate_pre_2024020200_queueing` |
| Changeover from 5.1.0.2 (`2026012570`) keeps stored data unchanged (it only queues the legacy migration) | `db/upgrade.php` (2026102700 savepoint) | `tests/upgrade_test.php::test_changeover_from_5102_is_a_noop` |
| Adhoc legacy migration | `classes/task/migrate_legacy_data.php` | `tests/format_tiles_course_format_options_test.php::test_migrate_legacy_data_task` |
| MBZ restore of all four fixture backups | `backup/moodle2/restore_format_tiles_plugin.class.php` | `tests/format_tiles_course_format_options_test.php::test_restore_from_old_format_mbz` (4 data sets) |
| Install floor | `version.php:28` | `2026100500` (5.3) — `/admin/plugins.php` smoke on 5.3 |

## (4) Gaps inherited from the old codebase

| Surface | Location | Guard |
|---|---|---|
| Privacy metadata and preference export | `classes/privacy/provider.php` | `tests/privacy/provider_test.php` |
| `format_tiles_pluginfile()` authorization | `lib.php:995-1022` | `tests/format_tiles_test.php::test_pluginfile_rejects_wrong_requests` |
| Max sections setting and 52 fallback | `classes/local/course_section_manager.php:50-56` | `tests/format_tiles_test.php::test_get_max_sections_respects_config_and_fallback` |
| `numsections` / `maxsections` reads | `lib.php:589-599` | `tests/behat/course_create_course.feature` (form only); removal tracked as backlog (MDL-85284, MDL-85272) |
| Unreachable student/guest branches in `test_set_image` | `tests/format_tiles_externallib_test.php:129-144` | replaced by `::test_set_image_rejects_student_and_guest` |
| Orphaned data providers | `tests/format_tiles_courselib_test.php` | deleted |
| Empty "Adding sections" scenario | `tests/behat/edit_delete_sections.feature` | deleted |
| Wrong activity name in completion assertion | `tests/behat/completion.feature:94` | fixed (`Test page 1b`) |
| `@format2` tag on a tiles feature | `tests/behat/subsection_coursepage_tiles.feature:1` | fixed (`@format`) |
| Wrong string component in delete assertion | `tests/behat/behat_format_tiles.php:648` | fixed (`core_courseformat`) |
| Student/teacher scenario title mismatch | `tests/behat/pdf_modal_student.feature:67,122` | fixed |
| Renderer section-title overrides | `classes/output/renderer.php` | backlog |
| Full `course_output::export_for_template` render sweep | `classes/output/course_output.php` | backlog — `tests/format_tiles_test.php::test_non_displayable_module_is_not_rendered` renders the section-zero context end to end; the rest is covered by Behat |

## (5) Regressions from the new APIs

| Surface | Location | Guard |
|---|---|---|
| Reactive highlight dispatch (5.3 ↔ 6.0) | `amd/src/mutations.js`, `amd/src/section.js`, `classes/local/util.php:285-286` | `tests/behat/section_highlight.feature` |
| No full-page navigation on highlight | `amd/src/mutations.js:103` (`CourseActions.addActions`) | `section_highlight.feature` page-sentinel steps |
| In-place Highlight ↔ Unhighlight swap | `amd/src/section.js:64-70` | `section_highlight.feature` menu-text steps |
| No duplicate mutation registration | `classes/local/util.php:285-286` (single `js_call_amd` hook) | backlog (covered indirectly by the same feature) |

## (6) Hardening for the staging install (5.2.3)

| Surface | Location | Guard |
|---|---|---|
| Dark-mode tokens on tiles, section cards, subtiles, icon picker, filter bar and tooltips | `styles.css` (sites listed in the 5.2.3 changelog), `styles_adaptable.css:16-19` | visual proof on 6.0 with `data-bs-theme="dark"` (light + edit mode); `@format_tiles` Behat unchanged |
| Dark palette counterpart for the high contrast text colour | `templates/dynamic_styles.mustache:71-74` | `tests/format_tiles_dynamic_styles_test.php::test_dark_mode_palette_repoints_high_contrast_text` |
| Custom CSS cannot close its `<style>` element or a script | `classes/local/dynamic_styles.php:97-105` | `tests/format_tiles_dynamic_styles_test.php::test_customcss_cannot_break_out_of_style_tag` |
| `set_image` draftfile: caller's own draft area only, photo stored in the course context | `classes/external/external.php:147-162` | `tests/format_tiles_externallib_test.php::test_set_image_draftfile_rejects_foreign_draft`, `::test_set_image_draftfile_stores_in_course_context` |
| `set_image` image name uses `PARAM_FILE` | `classes/external/external.php:364` | `tests/format_tiles_externallib_test.php::test_set_image`, `::test_set_image_allows_editing_teacher` (icon names still accepted) |
| `log_tile_click` accepts a course context and a non-negative section number only | `classes/external/external.php:411-420` | `tests/format_tiles_externallib_test.php::test_log_tile_click_rejects_non_course_context` |
| `skipcheck` link requires a sesskey | `classes/local/util.php:199-205`, `templates/multi_section_page.mustache:56` | code review — the escape link is only rendered on the loading page, not exercised by Behat |
| `canceljssession` requires a sesskey | `classes/output/renderer.php:131-136` | code review — no in-plugin link generates this parameter |
| `require_login()` in both fragment callbacks | `lib.php:1034,1096` | `tests/format_tiles_fragment_test.php` (non-enrolled user); `@format_tiles_mod_modal` Behat |
| Fragment callback gates and rendering (login, hidden section, non-tiles course, context level, page body) | `lib.php:1032-1142` | `tests/format_tiles_fragment_test.php` (6 cases) |
| Install sample-image copy skips an existing file | `db/install.php:46-56` | not unit testable (install runs before the PHPUnit site exists); mirrors the guarded upgrade block |
| Restore section path check uses `==` | `backup/moodle2/restore_format_tiles_plugin.class.php:169` | `tests/format_tiles_course_format_options_test.php::test_restore_from_old_format_mbz` (4 data sets) |
| Completion data fetched once per module per render | `classes/output/course_output.php:105-112,722-734` | behaviour: `tests/format_tiles_test.php::test_non_displayable_module_is_not_rendered` (section progress); call count: measured at 2.55 us per call |
| Dynamic CSS template source read once per request | `classes/local/dynamic_styles.php:84-93` | `tests/format_tiles_dynamic_styles_test.php` renders through the same template; page output unchanged |
