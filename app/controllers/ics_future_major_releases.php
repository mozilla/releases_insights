<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Release};

// All good, we can generate an ICS
[$filename, $ics_calendar] = new Model('ics_future')->get();

header('Content-Type: text/calendar; charset=utf-8');
// header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo $ics_calendar;
exit;
