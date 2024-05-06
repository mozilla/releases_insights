<?php

declare(strict_types=1);

use ReleaseInsights\CalendarMonthly as CM;

test('CalendarMonthly::getMonthsToLastPlannedRelease()', function () {
  expect(CM::getMonthsToLastPlannedRelease())
        ->each->toMatch('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/');
});
