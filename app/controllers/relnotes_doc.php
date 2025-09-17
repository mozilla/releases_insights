<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1LOMOgr3E2cfvOeznHZwIZohvb2BbFpbBf0wCyshEEWA/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
