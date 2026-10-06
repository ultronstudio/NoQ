<?php

namespace App\Exceptions\Queue;

use RuntimeException;

/**
 * Base type for all queue domain invariant violations, so callers can
 * catch every such failure with a single type if they need to.
 */
abstract class QueueDomainException extends RuntimeException {}
