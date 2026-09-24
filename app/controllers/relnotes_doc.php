<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/17aB7a2fDnHSEgcPq-1ibC0euwU_OVsjFyAT8Fk47vmI/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
