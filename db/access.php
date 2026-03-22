<?php
defined("MOODLE_INTERNAL") || die();

$capabilities = [
    // Manage plugin settings and run setup wizard
    "local/softsysvideo:manage" => [
        "riskbitmask"  => RISK_CONFIG,
        "captype"      => "write",
        "contextlevel" => CONTEXT_SYSTEM,
        "archetypes"   => [
            "manager" => CAP_ALLOW,
        ],
    ],
    // View analytics (meetings, usage) — for teachers/managers
    "local/softsysvideo:viewanalytics" => [
        "captype"      => "read",
        "contextlevel" => CONTEXT_COURSE,
        "archetypes"   => [
            "editingteacher" => CAP_ALLOW,
            "teacher"        => CAP_ALLOW,
            "manager"        => CAP_ALLOW,
        ],
    ],
    // View credit balance and billing info
    "local/softsysvideo:viewcredits" => [
        "captype"      => "read",
        "contextlevel" => CONTEXT_SYSTEM,
        "archetypes"   => [
            "manager" => CAP_ALLOW,
        ],
    ],
];
