<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1X8FwFbP-bBHw5WVD4FU1VELXxOOGkd6L5pU2KLNOEcY/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
