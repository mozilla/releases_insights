<?php declare(strict_types=1);

// Sentry test: Force a 500 by adding an int and a string
/* @phpstan-ignore-next-line */
echo 10 + 'A';
