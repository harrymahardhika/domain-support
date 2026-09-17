<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Exceptions;

final class UnmappedCriteriaException extends AbstractException
{
    public function __construct(string $property, string $repository)
    {
        parent::__construct(
            sprintf(
                'Criteria property "%s" has no matching method on repository "%s". Add a public %s() method or remove the property from the criteria.',
                $property,
                $repository,
                $property,
            ),
            500,
        );
    }
}
