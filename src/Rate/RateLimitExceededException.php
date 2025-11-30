<?php

declare(strict_types=1);

namespace PhilTenno\FilesyncGo\Rate;

use RuntimeException;

final class RateLimitExceededException extends RuntimeException
{
}