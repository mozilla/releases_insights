<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1sAJI-rEij02DJ8W5H-pqKzq_myqM9WQk-UzArvQht4Y/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
