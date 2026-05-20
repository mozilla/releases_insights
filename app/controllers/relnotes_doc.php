<?php

declare(strict_types=1);

// Link to the current Release Notes draft doc. Update that link every cycle.
$doc = 'https://docs.google.com/document/d/1ZQT0UWHkITXrZ9_CQ8Bwv184i_ImzMPcggP7BCTxpVo/edit?usp=sharing';

header("Location: $doc", true, 302);
exit;
