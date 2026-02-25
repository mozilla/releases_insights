<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1nY6XrLsp_jkJVZzEKbHZXtpt7TwjdnyjnB1EofftU8o/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
