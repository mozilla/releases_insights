<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1i1e1nfF142UfMC_48mtswStbLKj9LHkGzF9__9KfYj4/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
