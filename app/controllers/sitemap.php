<?php

declare(strict_types=1);

// We have a manually managed sitemap.txt file, let's redirect bots to that
header('Location: /sitemap.txt', true, 301);
exit;
