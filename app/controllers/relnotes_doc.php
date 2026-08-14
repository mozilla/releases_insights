<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1_Pm4yelKsjGZkj8DV3X-w8WyvAN8EcmnScEEgbaFFAU/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
