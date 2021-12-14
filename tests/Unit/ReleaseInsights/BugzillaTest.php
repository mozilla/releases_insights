<?php

declare(strict_types=1);

use ReleaseInsights\Bugzilla as bz;

test('Bugzilla::getBugListLink', function () {
    $this->assertEquals(
        'https://bugzilla.mozilla.org/buglist.cgi?bug_id=101%2C102%2C103',
        bz::getBugListLink([101, 102, 103])
    );
});
