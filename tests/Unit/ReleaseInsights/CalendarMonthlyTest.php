<?php

declare(strict_types=1);

use ReleaseInsights\CalendarMonthly as CM;

test('CalendarMonthly::getMonthsToLastPlannedRelease()', function () {
  expect(CM::getMonthsToLastPlannedRelease())
        ->each->toBeString();
});
