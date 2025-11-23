<?php

declare(strict_types=1);

namespace ReleaseInsights;

enum URL: string
{
    case Bugzilla       = 'https://bugzilla.mozilla.org/';
    case BuildHub       = 'https://buildhub.moz.tools/api/search';
    case ProductDetails = 'https://product-details.mozilla.org/1.0/';
    case Mercurial      = 'https://hg.mozilla.org/';
    case Balrog         = 'https://aus-api.mozilla.org/api/v1/';
    case Socorro        = 'https://crash-stats.mozilla.org/api/';
    case Archive        = 'https://archive.mozilla.org/';
    case Pollbot        = 'https://prod.pollbot.prod.webservices.mozgcp.net/v1/';

    public function target(): string
    {
        if (defined('TESTING_CONTEXT')) {
            return TEST_FILES;
        }

        return $this->value; // @codeCoverageIgnore
    }
}
