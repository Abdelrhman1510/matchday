<?php

namespace App\Exceptions;

use RuntimeException;

class MoyasarApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status, public readonly ?string $type = null)
    {
        parent::__construct($message);
    }

    /** Worth resending with the same given_id: Moyasar treats that as idempotent. */
    public function isTransient(): bool
    {
        return $this->status >= 500 || $this->status === 429;
    }
}
