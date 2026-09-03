<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a card cannot be verified/stored. The message is customer-readable
 * — the app shows it to the user verbatim — so never put internal detail in it.
 */
class PaymentMethodException extends RuntimeException
{
}
