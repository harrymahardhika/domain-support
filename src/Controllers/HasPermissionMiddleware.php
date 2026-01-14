<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Controllers;

trait HasPermissionMiddleware
{
    /**
     * @param array<string, string|array<int, string>> $permissions
     */
    protected function requiresPermissions(array $permissions): void
    {
        foreach ($permissions as $permission => $methods) {
            $methods = is_array($methods) ? $methods : [$methods];
            $this->middleware(sprintf('permission:%s', $permission))->only($methods);
        }
    }
}
