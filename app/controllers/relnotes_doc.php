<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1zNeBR5IwlMp2Hi5WItwY33TekGwbPcGq5vqWyeZ6h98/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
