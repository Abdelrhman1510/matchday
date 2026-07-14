<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCafeContext;
use App\Traits\ApiResponse;

abstract class Controller
{
    use ApiResponse;
    use ResolvesCafeContext;
}
