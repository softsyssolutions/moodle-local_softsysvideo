<?php
namespace local_softsysvideo\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for a plugin that does not store personal data in phase 1.
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Describe why the plugin does not store personal data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return get_string('privacy:metadata', 'local_softsysvideo');
    }
}
