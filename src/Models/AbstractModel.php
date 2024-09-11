<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

/**
 * @template TFactory of Factory
 */
abstract class AbstractModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->perPage = Config::get('domain-support.per_page');
    }

    /**
     * @return TFactory|null
     */
    abstract protected static function newFactory(): mixed;
}
