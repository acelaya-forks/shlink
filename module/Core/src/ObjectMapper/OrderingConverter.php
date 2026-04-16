<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ObjectMapper;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use Shlinkio\Shlink\Common\ObjectMapper\MappingError;
use Shlinkio\Shlink\Core\Model\Ordering;

use function implode;
use function Shlinkio\Shlink\Common\parseOrderBy;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function sprintf;

#[AsConverter]
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class OrderingConverter
{
    /**
     * @param string[] $validFields
     */
    public function __construct(private array|null $validFields = null)
    {
    }

    public function map(string|null $value): Ordering
    {
        if ($value === null || $value === '') {
            return Ordering::none();
        }

        [$field, $dir] = parseOrderBy($value);
        if ($this->validFields !== null && ! contains($field, $this->validFields)) {
            throw MappingError::withBody(
                sprintf('Resolved order field is not one of ["%s"]', implode('", "', $this->validFields)),
            );
        }

        if ($dir !== null && ! contains($dir, Ordering::VALID_ORDER_DIRS)) {
            throw MappingError::withBody('Resolved order direction has to be one of ["ASC", "DESC"]');
        }

        return Ordering::fromTuple([$field, $dir]);
    }
}
