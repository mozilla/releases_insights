<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1j5Mo8cOgZemMFv50pYq1SvnynyRCCjYVDse8SQtHKYg/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
