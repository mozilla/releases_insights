<?php

declare(strict_types=1);

use ReleaseInsights\Version;

if ((int) Version::get() < BETA) {
    exit("We don't provide schedule calendars for past releases.");
}

[$filename, $ics_calendar] = require_once MODELS . 'ics_release_schedule.php';

header('Content-Type: text/calendar; charset=utf-8');
// header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

print $ics_calendar;
