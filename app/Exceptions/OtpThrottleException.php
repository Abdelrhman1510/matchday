<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when an OTP send is requested again before the per-identifier cooldown
 * has elapsed, or after the hourly cap is reached. Controllers map it to HTTP 429.
 */
class OtpThrottleException extends Exception
{
}
