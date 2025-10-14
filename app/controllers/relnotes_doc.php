<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1SCI0tzaf79D5e2b7_quNwIcwj6U7QpbGZobcdsQE0z8/edit?tab=t.0';

header("Location: $doc", true, 302);
exit;
