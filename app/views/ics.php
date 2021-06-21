<?php

declare(strict_types=1);

header('Content-Type: text/calendar; charset=utf-8');
// header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

print $ics_calendar;
