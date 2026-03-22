<?php
defined("MOODLE_INTERNAL") || die();

/**
 * Extend global navigation — adds SoftSys Video setup link for admins
 */
function local_softsysvideo_extend_navigation(global_navigation $nav) {
    // No top-level nav item needed; managed via admin settings
}

/**
 * Extend settings navigation
 */
function local_softsysvideo_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    // No per-course settings in Phase 1
}
