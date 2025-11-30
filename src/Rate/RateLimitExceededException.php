<?php

declare(strict_types=1);

namespace PhilTenno\FileSyncGo\Rate;

use RuntimeException;

final class RateLimitExceededException extends RuntimeException
{
}