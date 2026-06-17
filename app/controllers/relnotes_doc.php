<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1wX5O9BsPcBSw6cR-hbGBt9MwpN_Kz2AZpXiIdejIzoU/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
