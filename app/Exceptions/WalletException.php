<?php

namespace App\Exceptions;

use RuntimeException;

/** A business-rule failure whose localized message is safe to show the user. */
class WalletException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, array $params = [], public readonly int $status = 422)
    {
        parent::__construct(__("wallet.errors.{$errorCode}", $params));
    }
}
