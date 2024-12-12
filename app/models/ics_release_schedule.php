<?php

declare(strict_types=1);

use ReleaseInsights\{Release, ReleaseCalendar, Version};

if (! isset($requested_version)) {
    $requested_version = Version::get();
}

// Get the schedule for the release requested
$releases = new Release($requested_version)->getSchedule();

$short_version = (string) (int) $releases['version'];

$release_schedule_labels = [
    'nightly_start'       => 'Nightly ' . $short_version . ' starts',
    'a11y_request_deadline' => 'Deadline to request accessibility engineering review',
    'qa_request_deadline' => 'Deadline to request manual QA in Nightly',
    'qa_feature_done'     => 'Build is feature complete for QA',
    'qa_feature_done_1'   => 'Build is feature complete for QA in Nightly',
    'qa_feature_done_2'   => 'Build is feature complete for QA in Beta',
    'soft_code_freeze'    => 'Firefox ' . $short_version . ' soft Code Freeze starts at 08:00 AM UTC',
    'qa_pre_merge_done'   => 'QA pre-merge regression testing completed',
    'qa_test_plan_due'    => 'Firefox ' . $short_version . ' QA Test Plan approval due',
    'string_freeze'       => 'String Freeze starts',
    'merge_day'           => 'Merge day',
    'beta_1'              => 'Firefox ' . $releases['version'] . 'b1',
    'beta_2'              => 'Firefox ' . $releases['version'] . 'b2 (GTB: 13:00 UTC)',
    'beta_3'              => 'Firefox ' . $releases['version'] . 'b3 (GTB: 13:00 UTC)',
    'sumo_1'              => 'User affecting changes provided to SUMO',
    'beta_4'              => 'Firefox ' . $releases['version'] . 'b4 (GTB: 13:00 UTC)',
    'beta_5'              => 'Firefox ' . $releases['version'] . 'b5 (GTB: 13:00 UTC)',
    'beta_6'              => 'Firefox ' . $releases['version'] . 'b6 (GTB: 13:00 UTC)',
    'beta_7'              => 'Firefox ' . $releases['version'] . 'b7 (GTB: 13:00 UTC)',
    'sumo_2'              => 'SUMO content localization starts',
    'beta_8'              => 'Firefox ' . $releases['version'] . 'b8 (GTB: 13:00 UTC)',
    'qa_pre_rc_signoff'   => 'QA pre-release sign-off',
    'beta_9'              => 'Firefox ' . $releases['version'] . 'b9 (GTB: 13:00 UTC)',
    'beta_10'             => 'Firefox ' . $releases['version'] . 'b10 (GTB: 13:00 UTC)',
    'beta_11'             => 'Firefox ' . $releases['version'] . 'b11 (GTB: 13:00 UTC)',
    'beta_12'             => 'Firefox ' . $releases['version'] . 'b12 (GTB: 13:00 UTC)',
    'beta_13'             => 'Firefox ' . $releases['version'] . 'b13 (GTB: 13:00 UTC)',
    'beta_14'             => 'Firefox ' . $releases['version'] . 'b14 (GTB: 13:00 UTC)',
    'beta_15'             => 'Firefox ' . $releases['version'] . 'b15 (GTB: 13:00 UTC)',
    'rc_gtb'              => 'Firefox ' . $short_version . ' go to Build',
    'rc'                  => 'RC',
    'release'             => 'Firefox ' . $short_version . ' go-live @ 6am PT',
    'planned_dot_release' => 'Firefox ' . $releases['version'] . '.x planned dot release',
];

// Add end of early betas to the schedule
if ((int) $requested_version == 134) {
    $releases['early_beta_end'] = $releases['beta_7'];
} else {
    $releases['early_beta_end'] = $releases['beta_6'];
}

$release_schedule_labels['early_beta_end'] = 'End of EARLY_BETA_OR_EARLIER (post beta 6)';

// Add draft release notes to the schedule
$draft_relnotes = new DateTime($releases['soft_code_freeze']);
$releases['draft_relnotes'] = $draft_relnotes->modify('-1 day')->format('Y-m-d H:i');
$release_schedule_labels['draft_relnotes'] = 'Draft beta release notes ready';

// Add final release notes to the schedule
$final_relnotes = new DateTime($releases['rc']);
$releases['final_relnotes'] = $final_relnotes->format('Y-m-d H:i');
$release_schedule_labels['final_relnotes'] = 'Firefox ' . $short_version . ': Release Notes Deadline';

$ics_calendar = ReleaseCalendar::getICS(
    $releases,
    $release_schedule_labels,
    'Firefox ' . $short_version
);

$filename = 'Firefox_' . $short_version . '_schedule.ics';

return [$filename, $ics_calendar];
