<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Controllers;

use Illuminate\Routing\Controller;

abstract class AbstractWebController extends Controller
{
    use HasPermissionMiddleware;
}
