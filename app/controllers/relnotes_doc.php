<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1UPlYw5Kxs9-b6s381MjI9IO6pob4AWnUT_zCwglbLKQ/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
