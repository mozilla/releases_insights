<?php

declare(strict_types=1);

namespace ReleaseInsights;

enum ReleaseStatus
{
    case Past;
    case Current;
    case Future;
}
