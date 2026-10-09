<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1YXQyrId8Mxcor_6MNvH7MX6KreoaPBFLFf-K1J9RrR8/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
